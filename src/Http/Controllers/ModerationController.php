<?php

declare(strict_types=1);

namespace Lvt\Http\Controllers;

use Lvt\Audit;
use Lvt\Auth;
use Lvt\Database;
use Lvt\HttpException;
use Lvt\Request;
use Lvt\Response;
use Lvt\Session;
use Lvt\View;

final class ModerationController extends Controller
{
    public function reviews(): Response
    {
        $status = Request::current()->string('status', 'pending');
        if (!in_array($status, ['pending', 'approved', 'rejected', 'all'], true)) {
            $status = 'pending';
        }
        $sql = 'SELECT * FROM reviews';
        $params = [];
        if ($status !== 'all') {
            $sql .= ' WHERE status = ?';
            $params[] = $status;
        }
        $sql .= ' ORDER BY created_at DESC LIMIT 200';
        return View::page('admin/reviews', [
            'title' => 'Reviews',
            'description' => 'Review moderation',
            'reviews' => Database::all($sql, $params),
            'status' => $status,
        ], 'layouts/admin');
    }

    public function review(array $params): Response
    {
        $this->csrf();
        $review = Database::one('SELECT * FROM reviews WHERE id = ?', [(int) $params['id']]);
        if (!$review) {
            throw new HttpException(404, 'That review does not exist.');
        }
        $decision = Request::current()->string('decision');
        if (!in_array($decision, ['approved', 'rejected', 'pending'], true)) {
            throw new HttpException(422, 'Choose approve or reject.');
        }
        Database::updateRow('reviews', [
            'status' => $decision,
            'moderated_by' => Auth::user()['id'] ?? null,
            'moderated_at' => now_dt(),
        ], 'id = ?', [(int) $review['id']]);
        Audit::write((int) (Auth::user()['id'] ?? 0), 'review.' . $decision, 'review', (int) $review['id'], 'Moderated a review');
        Session::flash('success', 'Review ' . $decision . '.');
        return Response::redirect('/admin/reviews');
    }

    public function inquiries(): Response
    {
        $rows = Database::all(
            'SELECT * FROM inquiries ORDER BY FIELD(status, "new", "contacted", "converted", "closed"), created_at DESC LIMIT 200'
        );
        return View::page('admin/inquiries', [
            'title' => 'Inbox',
            'description' => 'Proposal requests',
            'inquiries' => $rows,
        ], 'layouts/admin');
    }

    public function inquiry(array $params): Response
    {
        $this->csrf();
        $row = Database::one('SELECT * FROM inquiries WHERE id = ?', [(int) $params['id']]);
        if (!$row) {
            throw new HttpException(404, 'That request does not exist.');
        }
        $status = Request::current()->string('status');
        if (!in_array($status, ['new', 'contacted', 'converted', 'closed'], true)) {
            $status = (string) $row['status'];
        }
        Database::updateRow('inquiries', ['status' => $status], 'id = ?', [(int) $row['id']]);
        Audit::write((int) (Auth::user()['id'] ?? 0), 'inquiry.update', 'inquiry', (int) $row['id'], 'Status ' . $status);
        Session::flash('success', 'Request updated.');
        return Response::redirect('/admin/inquiries');
    }

    public function applications(): Response
    {
        $role = Request::current()->string('role', 'all');
        $status = Request::current()->string('status', 'all');
        if (!array_key_exists($role, job_roles())) {
            $role = 'all';
        }
        if (!in_array($status, job_statuses(), true)) {
            $status = 'all';
        }
        $where = [];
        $params = [];
        if ($role !== 'all') {
            $where[] = 'role = ?';
            $params[] = $role;
        }
        if ($status !== 'all') {
            $where[] = 'status = ?';
            $params[] = $status;
        }
        $sql = 'SELECT id, role, full_name, phone, city, status, created_at FROM job_applications';
        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY created_at DESC LIMIT 200';
        return View::page('admin/applications', [
            'title' => 'Applications',
            'description' => 'Job applications',
            'applications' => Database::all($sql, $params),
            'filterRole' => $role,
            'filterStatus' => $status,
        ], 'layouts/admin');
    }

    public function application(array $params): Response
    {
        $row = $this->applicationRow((int) $params['id']);
        return View::page('admin/application', [
            'title' => 'Application',
            'description' => 'Job application',
            'application' => $row,
        ], 'layouts/admin');
    }

    public function applicationUpdate(array $params): Response
    {
        $this->csrf();
        $row = $this->applicationRow((int) $params['id']);
        $req = Request::current();
        $status = $req->string('status');
        $notes = $req->string('notes');
        $errors = [];
        if (!in_array($status, job_statuses(), true)) {
            $errors['status'] = 'Choose a status.';
        }
        if (mb_strlen($notes) > 4000) {
            $errors['notes'] = 'Keep notes under 4,000 characters.';
        }
        if ($errors) {
            return $this->invalid('admin/application', $errors, [
                'title' => 'Application',
                'description' => 'Job application',
                'application' => $row,
            ], 'layouts/admin');
        }
        Database::updateRow('job_applications', [
            'status' => $status,
            'notes' => blank_to_null($notes),
        ], 'id = ?', [(int) $row['id']]);
        Audit::write((int) (Auth::user()['id'] ?? 0), 'application.update', 'job_application', (int) $row['id'], 'Status ' . $status);
        Session::flash('success', 'Application updated.');
        return Response::redirect('/admin/applications/' . (int) $row['id']);
    }

    /** @return array<string,mixed> */
    private function applicationRow(int $id): array
    {
        $row = Database::one(
            'SELECT id, role, full_name, phone, email, city, is_adult, has_transportation, days_available, can_lift, experience, start_date, heard_about, consent, status, notes, created_at FROM job_applications WHERE id = ?',
            [$id]
        );
        if (!$row) {
            throw new HttpException(404, 'That application does not exist.');
        }
        return $row;
    }
}
