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
}
