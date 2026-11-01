<?php

declare(strict_types=1);

namespace Lvt\Http\Controllers;

use Lvt\Audit;
use Lvt\Auth;
use Lvt\Database;
use Lvt\HttpException;
use Lvt\Junk\Quote;
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

    public function outreach(): Response
    {
        $messages = Database::all('SELECT * FROM contact_inquiries ORDER BY created_at DESC, id DESC LIMIT 200');
        $estimates = Database::all('SELECT * FROM junk_estimates ORDER BY created_at DESC, id DESC LIMIT 200');
        $rows = [];
        foreach ($messages as $row) {
            $rows[] = $this->outreachListRow(
                '/admin/outreach/' . (int) $row['id'],
                outreach_need_label((string) $row['need']),
                (string) $row['status'],
                (string) $row['name'],
                service_area_label((string) $row['service_area']),
                (string) $row['created_at'],
                (int) $row['id']
            );
        }
        foreach ($estimates as $row) {
            $rows[] = $this->outreachListRow(
                '/admin/junk-estimates/' . (int) $row['id'],
                'Junk pickup estimate',
                (string) $row['status'],
                (string) $row['name'],
                Quote::selectionLabel(
                    (string) $row['mode'],
                    $row['items_json'] !== null ? (string) $row['items_json'] : null,
                    $row['load_size'] !== null ? (string) $row['load_size'] : null
                ),
                (string) $row['created_at'],
                (int) $row['id']
            );
        }
        usort($rows, static fn (array $a, array $b): int => strcmp((string) $b['sort'], (string) $a['sort']));
        $rows = array_slice($rows, 0, 200);
        return View::page('admin/outreach', [
            'title' => 'Inquiries',
            'description' => 'Contact inquiries and junk pickup estimates',
            'rows' => $rows,
            'newCount' => outreach_new_count(),
        ], 'layouts/admin');
    }

    public function outreachShow(array $params): Response
    {
        $row = $this->outreachRow((int) $params['id']);
        return View::page('admin/outreach-inquiry', [
            'title' => 'Inquiry',
            'description' => 'Contact inquiry',
            'inquiry' => $row,
        ], 'layouts/admin');
    }

    public function outreachUpdate(array $params): Response
    {
        $this->csrf();
        $row = $this->outreachRow((int) $params['id']);
        $req = Request::current();
        $status = $req->string('status');
        $notes = $req->string('notes');
        $errors = [];
        if (!in_array($status, ['new', 'contacted', 'closed'], true)) {
            $errors['status'] = 'Choose a status.';
        }
        if (mb_strlen($notes) > 4000) {
            $errors['notes'] = 'Keep notes under 4,000 characters.';
        }
        if ($errors) {
            return $this->invalid('admin/outreach-inquiry', $errors, [
                'title' => 'Inquiry',
                'description' => 'Contact inquiry',
                'inquiry' => $row,
            ], 'layouts/admin');
        }
        Database::updateRow('contact_inquiries', [
            'status' => $status,
            'notes' => blank_to_null($notes),
        ], 'id = ?', [(int) $row['id']]);
        Audit::write((int) (Auth::user()['id'] ?? 0), 'outreach.update', 'contact_inquiry', (int) $row['id'], 'Status ' . $status);
        Session::flash('success', 'Inquiry updated.');
        return Response::redirect('/admin/outreach/' . (int) $row['id']);
    }

    public function junkShow(array $params): Response
    {
        $row = $this->junkRow((int) $params['id']);
        return View::page('admin/junk-estimate', [
            'title' => 'Junk pickup estimate',
            'description' => 'Junk pickup estimate',
            'estimate' => $row,
        ], 'layouts/admin');
    }

    public function junkUpdate(array $params): Response
    {
        $this->csrf();
        $row = $this->junkRow((int) $params['id']);
        $req = Request::current();
        $status = $req->string('status');
        $notes = $req->string('notes');
        $errors = [];
        if (!in_array($status, ['new', 'quoted', 'booked', 'closed'], true)) {
            $errors['status'] = 'Choose a status.';
        }
        if (mb_strlen($notes) > 4000) {
            $errors['notes'] = 'Keep notes under 4,000 characters.';
        }
        if ($errors) {
            return $this->invalid('admin/junk-estimate', $errors, [
                'title' => 'Junk pickup estimate',
                'description' => 'Junk pickup estimate',
                'estimate' => $row,
            ], 'layouts/admin');
        }
        Database::updateRow('junk_estimates', [
            'status' => $status,
            'notes' => blank_to_null($notes),
        ], 'id = ?', [(int) $row['id']]);
        Audit::write((int) (Auth::user()['id'] ?? 0), 'junk_estimate.update', 'junk_estimate', (int) $row['id'], 'Status ' . $status);
        Session::flash('success', 'Estimate updated.');
        return Response::redirect('/admin/junk-estimates/' . (int) $row['id']);
    }

    public function junkPhoto(array $params): Response
    {
        $row = $this->junkRow((int) $params['id']);
        $name = (string) ($row['photo_path'] ?? '');
        if ($name === '' || str_contains($name, '/') || str_contains($name, '\\') || str_contains($name, '..')) {
            throw new HttpException(404, 'No photo is attached.');
        }
        $path = LVT_ROOT . '/storage/uploads/' . $name;
        if (!is_file($path)) {
            throw new HttpException(404, 'That photo file is missing.');
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($path) ?: 'application/octet-stream';
        return Response::text((string) file_get_contents($path), 200, $mime);
    }

    public function junkRates(): Response
    {
        return View::page('admin/junk-rates', [
            'title' => 'Junk pickup rates',
            'description' => 'Junk pickup rates',
            'rates' => $this->junkRateRow(),
        ], 'layouts/admin');
    }

    public function junkRatesSave(): Response
    {
        $this->csrf();
        $req = Request::current();
        $errors = [];
        $updates = ['updated_at' => now_dt()];
        foreach (Quote::codes() as $code) {
            $low = $this->rateCents($req->string($code . '_low'), $code . '_low', $errors);
            $high = $this->rateCents($req->string($code . '_high'), $code . '_high', $errors);
            if ($low !== null && $high !== null && $high < $low) {
                $errors[$code . '_high'] = 'The high amount cannot be less than the low amount.';
            }
            $updates[$code . '_low_cents'] = $low;
            $updates[$code . '_high_cents'] = $high;
        }
        if ($errors) {
            return $this->invalid('admin/junk-rates', $errors, [
                'title' => 'Junk pickup rates',
                'description' => 'Junk pickup rates',
                'rates' => $this->junkRateRow(),
            ], 'layouts/admin');
        }
        Database::updateRow('junk_rates', $updates, 'id = ?', [1]);
        Audit::write((int) (Auth::user()['id'] ?? 0), 'junk_rates.update', 'junk_rates', 1, 'Updated junk pickup rates');
        Session::flash('success', 'Junk pickup rates saved.');
        return Response::redirect('/admin/junk-rates');
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

    /** @return array{href:string,type:string,status:string,title:string,meta:string,created_at:string,sort:string} */
    private function outreachListRow(string $href, string $type, string $status, string $title, string $meta, string $createdAt, int $id): array
    {
        return [
            'href' => $href,
            'type' => $type,
            'status' => $status,
            'title' => $title,
            'meta' => $meta,
            'created_at' => $createdAt,
            'sort' => $createdAt . '-' . str_pad((string) $id, 8, '0', STR_PAD_LEFT),
        ];
    }

    /** @return array<string,mixed> */
    private function outreachRow(int $id): array
    {
        $row = Database::one('SELECT * FROM contact_inquiries WHERE id = ?', [$id]);
        if (!$row) {
            throw new HttpException(404, 'That inquiry does not exist.');
        }
        return $row;
    }

    /** @return array<string,mixed> */
    private function junkRow(int $id): array
    {
        $row = Database::one('SELECT * FROM junk_estimates WHERE id = ?', [$id]);
        if (!$row) {
            throw new HttpException(404, 'That estimate does not exist.');
        }
        return $row;
    }

    /** @return array<string,mixed> */
    private function junkRateRow(): array
    {
        $row = Database::one('SELECT * FROM junk_rates WHERE id = ?', [1]);
        if (!$row) {
            throw new HttpException(500, 'Junk pickup rates are not set up.');
        }
        return $row;
    }

    /** @param array<string,string> $errors */
    private function rateCents(string $raw, string $key, array &$errors): ?int
    {
        if (trim($raw) === '') {
            return null;
        }
        $cents = dollars_to_cents($raw);
        if ($cents === null) {
            $errors[$key] = 'Enter a dollar amount, or leave it blank.';
            return null;
        }
        return $cents;
    }
}
