<?php

declare(strict_types=1);

namespace Lvt\Http\Controllers;

use Lvt\Audit;
use Lvt\Auth;
use Lvt\Database;
use Lvt\HttpException;
use Lvt\Pdf;
use Lvt\Request;
use Lvt\Response;
use Lvt\Session;
use Lvt\View;

final class IssueController extends Controller
{
    public function index(): Response
    {
        $status = Request::current()->string('status', 'open');
        $sql = 'SELECT i.*, p.name AS property_name FROM issues i
                LEFT JOIN properties p ON p.id = i.property_id WHERE 1=1';
        $params = [];
        if ($status === 'open') {
            $sql .= ' AND i.status <> "resolved"';
        } elseif (in_array($status, ['open', 'scheduled_return', 'resolved'], true)) {
            $sql .= ' AND i.status = ?';
            $params[] = $status;
        }
        $sql .= ' ORDER BY i.created_at DESC LIMIT 200';
        return View::page('admin/issues', [
            'title' => 'Issues',
            'description' => 'Missed pickups and violations',
            'issues' => Database::all($sql, $params),
            'status' => $status,
            'properties' => Database::all('SELECT id, name FROM properties ORDER BY name'),
        ], 'layouts/admin');
    }

    public function show(array $params): Response
    {
        $issue = $this->issue((int) $params['id']);
        return View::page('admin/issue', [
            'title' => 'Issue #' . $issue['id'],
            'description' => 'Issue detail',
            'issue' => $issue,
            'properties' => Database::all('SELECT id, name FROM properties ORDER BY name'),
        ], 'layouts/admin');
    }

    public function update(array $params): Response
    {
        $this->csrf();
        $issue = $this->issue((int) $params['id']);
        $req = Request::current();
        $status = $req->string('status', (string) $issue['status']);
        if (!in_array($status, ['open', 'scheduled_return', 'resolved'], true)) {
            $status = (string) $issue['status'];
        }
        $propertyId = $req->int('property_id', 0);
        Database::updateRow('issues', [
            'status' => $status,
            'property_id' => $propertyId > 0 ? $propertyId : null,
            'description' => blank_to_null($req->string('description')),
            'resolved_at' => $status === 'resolved' ? ($issue['resolved_at'] ?: now_dt()) : null,
        ], 'id = ?', [(int) $issue['id']]);
        Audit::write((int) (Auth::user()['id'] ?? 0), 'issue.update', 'issue', (int) $issue['id'], 'Status ' . $status);
        Session::flash('success', 'Issue updated.');
        return Response::redirect('/admin/issues/' . $issue['id']);
    }

    public function photo(array $params): Response
    {
        $issue = $this->issue((int) $params['id']);
        $name = (string) ($issue['photo_path'] ?? '');
        if ($name === '' || str_contains($name, '/') || str_contains($name, '\\')) {
            throw new HttpException(404, 'No photo is attached.');
        }
        $path = LVT_ROOT . '/storage/uploads/' . $name;
        if (!is_file($path)) {
            throw new HttpException(404, 'That photo file is missing.');
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($path) ?: 'application/octet-stream';
        $body = (string) file_get_contents($path);
        return Response::text($body, 200, $mime);
    }

    public function report(): Response
    {
        [$property, $start, $end, $issues] = $this->reportData();
        return View::page('admin/report', [
            'title' => 'Weekly report',
            'description' => 'Printable property report',
            'property' => $property,
            'properties' => Database::all('SELECT id, name FROM properties ORDER BY name'),
            'start' => $start,
            'end' => $end,
            'issues' => $issues,
        ], 'layouts/admin');
    }

    public function pdf(): Response
    {
        [$property, $start, $end, $issues] = $this->reportData();
        if (!$property) {
            throw new HttpException(422, 'Choose a property for the PDF.');
        }
        $lines = [
            'Weekly report: ' . $property['name'],
            $start . ' through ' . $end,
            'Loaded Valet Trash',
            '',
        ];
        if ($issues === []) {
            $lines[] = 'No missed pickups or violations were logged in this week.';
        }
        foreach ($issues as $issue) {
            $lines[] = strtoupper((string) $issue['type']) . ' · unit ' . ($issue['unit_label'] ?: 'n/a') . ' · ' . $issue['status'];
            $lines[] = 'Opened ' . $issue['created_at'] . ($issue['photo_path'] ? ' · photo on file' : '');
            $desc = trim((string) ($issue['description'] ?? ''));
            if ($desc !== '') {
                $lines[] = mb_substr($desc, 0, 180);
            }
            $lines[] = '';
        }
        $pdf = Pdf::document('Loaded Valet Trash weekly report', $lines);
        $slug = preg_replace('/[^a-z0-9]+/i', '-', (string) $property['name']) ?: 'property';
        return Response::text($pdf, 200, 'application/pdf')
            ->withHeader('Content-Disposition', 'attachment; filename="report-' . trim((string) $slug, '-') . '-' . $start . '.pdf"');
    }

    /** @return array{0:?array,1:string,2:string,3:list<array<string,mixed>>} */
    private function reportData(): array
    {
        $start = Request::current()->string('start', date('Y-m-d', strtotime('monday this week')));
        if (!valid_date($start)) {
            $start = date('Y-m-d', strtotime('monday this week'));
        }
        $end = date('Y-m-d', strtotime($start . ' +6 days'));
        $propertyId = Request::current()->int('property_id', 0);
        $property = $propertyId > 0 ? Database::one('SELECT * FROM properties WHERE id = ?', [$propertyId]) : null;
        $issues = [];
        if ($property) {
            $issues = Database::all(
                'SELECT * FROM issues WHERE property_id = ? AND created_at >= ? AND created_at < ? ORDER BY created_at',
                [(int) $property['id'], $start . ' 00:00:00', date('Y-m-d', strtotime($end . ' +1 day')) . ' 00:00:00']
            );
        }
        return [$property, $start, $end, $issues];
    }

    private function issue(int $id): array
    {
        $row = Database::one(
            'SELECT i.*, p.name AS property_name FROM issues i LEFT JOIN properties p ON p.id = i.property_id WHERE i.id = ?',
            [$id]
        );
        if (!$row) {
            throw new HttpException(404, 'That issue does not exist.');
        }
        return $row;
    }
}
