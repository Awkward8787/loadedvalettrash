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
use Lvt\Uploader;
use Lvt\View;

final class PorterController extends Controller
{
    public function tonight(): Response
    {
        $user = Auth::user();
        if (!$user) {
            return Response::redirect('/login');
        }
        $date = Request::current()->string('date', today_date());
        if (!valid_date($date)) {
            $date = today_date();
        }
        $runId = Request::current()->int('run', 0);
        if ($user['role'] === 'porter') {
            $runs = Database::all(
                'SELECT * FROM route_runs WHERE porter_id = ? AND service_date = ? AND status <> "cancelled" ORDER BY start_time, id',
                [(int) $user['id'], $date]
            );
        } elseif ($runId > 0) {
            $runs = Database::all('SELECT * FROM route_runs WHERE id = ?', [$runId]);
        } else {
            $runs = Database::all(
                'SELECT * FROM route_runs WHERE service_date = ? AND status <> "cancelled" ORDER BY start_time, id',
                [$date]
            );
        }
        $selected = $runs[0] ?? null;
        if ($user['role'] !== 'porter' && $runId > 0) {
            foreach ($runs as $run) {
                if ((int) $run['id'] === $runId) {
                    $selected = $run;
                }
            }
        }
        $stops = [];
        $openClock = Database::one(
            'SELECT * FROM time_entries WHERE user_id = ? AND clock_out IS NULL ORDER BY id DESC LIMIT 1',
            [(int) $user['id']]
        );
        if ($selected) {
            $stops = Database::all(
                'SELECT s.*, p.name AS property_name, p.address, p.city, p.state, p.postal_code,
                        p.compactor_location, p.gate_access_notes, b.name AS building_name, b.hazards_notes, b.compactor_trip_after
                 FROM route_stops s
                 INNER JOIN properties p ON p.id = s.property_id
                 LEFT JOIN buildings b ON b.id = s.building_id
                 WHERE s.route_run_id = ?
                 ORDER BY s.sort_order, s.id',
                [(int) $selected['id']]
            );
            foreach ($stops as &$stop) {
                $stop['doors'] = Database::all(
                    'SELECT * FROM door_checks WHERE route_stop_id = ? ORDER BY unit_label',
                    [(int) $stop['id']]
                );
                $stop['show_gate'] = Auth::canSeeGateNotes($user, (int) $stop['property_id']);
            }
            unset($stop);
        }
        return View::page('admin/tonight', [
            'title' => 'Tonight',
            'description' => 'Porter route',
            'date' => $date,
            'runs' => $runs,
            'run' => $selected,
            'stops' => $stops,
            'openClock' => $openClock,
        ], 'layouts/admin');
    }

    public function clock(): Response
    {
        $this->csrf();
        $user = Auth::user();
        $run = $this->authorizedRun(Request::current()->int('route_run_id', 0));
        $this->assertCanClock($run);
        $action = Request::current()->string('action');
        if ($action === 'in') {
            $open = Database::one('SELECT id FROM time_entries WHERE user_id = ? AND clock_out IS NULL', [(int) $user['id']]);
            if ($open) {
                Session::flash('error', 'You are already clocked in.');
                return Response::redirect('/admin/tonight?run=' . $run['id']);
            }
            Database::insertRow('time_entries', [
                'user_id' => (int) $user['id'],
                'route_run_id' => (int) $run['id'],
                'clock_in' => now_dt(),
                'clock_out' => null,
                'notes' => null,
            ]);
            if ($run['status'] === 'scheduled') {
                Database::updateRow('route_runs', ['status' => 'in_progress'], 'id = ?', [(int) $run['id']]);
            }
            Audit::write((int) $user['id'], 'porter.clock_in', 'route_run', (int) $run['id'], 'Clocked in');
            Session::flash('success', 'Clocked in.');
        } else {
            $open = Database::one('SELECT * FROM time_entries WHERE user_id = ? AND clock_out IS NULL ORDER BY id DESC LIMIT 1', [(int) $user['id']]);
            if (!$open) {
                Session::flash('error', 'You are not clocked in.');
                return Response::redirect('/admin/tonight?run=' . $run['id']);
            }
            Database::updateRow('time_entries', ['clock_out' => now_dt()], 'id = ?', [(int) $open['id']]);
            Audit::write((int) $user['id'], 'porter.clock_out', 'route_run', (int) $run['id'], 'Clocked out');
            Session::flash('success', 'Clocked out.');
        }
        return Response::redirect('/admin/tonight?run=' . $run['id']);
    }

    public function stop(array $params): Response
    {
        $this->csrf();
        $stop = $this->authorizedStop((int) $params['id']);
        $action = Request::current()->string('action', 'done');
        if ($action === 'done') {
            Database::exec(
                'UPDATE door_checks SET status = "picked_up", updated_at = ? WHERE route_stop_id = ? AND status = "pending"',
                [now_dt(), (int) $stop['id']]
            );
        } elseif ($action === 'skipped') {
            Database::updateRow('route_stops', [
                'status' => 'skipped',
                'notes' => blank_to_null(Request::current()->string('notes')),
                'completed_at' => now_dt(),
            ], 'id = ?', [(int) $stop['id']]);
            Audit::write((int) (Auth::user()['id'] ?? 0), 'porter.stop', 'route_stop', (int) $stop['id'], 'Skipped a stop');
            return Response::redirect('/admin/tonight?run=' . $stop['route_run_id']);
        }
        $this->refreshStop((int) $stop['id']);
        Audit::write((int) (Auth::user()['id'] ?? 0), 'porter.stop', 'route_stop', (int) $stop['id'], 'Checked a building');
        return Response::redirect('/admin/tonight?run=' . $stop['route_run_id'] . '#stop-' . $stop['id']);
    }

    public function door(array $params): Response
    {
        $this->csrf();
        $door = Database::one(
            'SELECT d.*, s.route_run_id, s.property_id, s.id AS stop_id FROM door_checks d
             INNER JOIN route_stops s ON s.id = d.route_stop_id WHERE d.id = ?',
            [(int) $params['id']]
        );
        if (!$door) {
            throw new HttpException(404, 'That door is not on a route.');
        }
        $this->authorizedRun((int) $door['route_run_id']);
        $status = Request::current()->string('status', 'picked_up');
        if (!in_array($status, ['picked_up', 'missed', 'violation', 'pending'], true)) {
            $status = 'picked_up';
        }
        Database::updateRow('door_checks', [
            'status' => $status,
            'notes' => blank_to_null(Request::current()->string('notes')),
            'updated_at' => now_dt(),
        ], 'id = ?', [(int) $door['id']]);
        if ($status === 'missed' || $status === 'violation') {
            $this->openIssue($door, $status);
        }
        $this->refreshStop((int) $door['stop_id']);
        Audit::write((int) (Auth::user()['id'] ?? 0), 'porter.door', 'door_check', (int) $door['id'], 'Marked ' . $door['unit_label'] . ' ' . $status);
        Session::flash('success', 'Unit ' . $door['unit_label'] . ' marked ' . str_replace('_', ' ', $status) . '.');
        return Response::redirect('/admin/tonight?run=' . $door['route_run_id'] . '#stop-' . $door['stop_id']);
    }

    private function openIssue(array $door, string $status): void
    {
        $type = $status === 'missed' ? 'missed_pickup' : 'violation';
        $existing = Database::one(
            'SELECT id FROM issues WHERE property_id = ? AND unit_label = ? AND type = ? AND status <> "resolved" AND created_at >= ?',
            [(int) $door['property_id'], (string) $door['unit_label'], $type, today_date() . ' 00:00:00']
        );
        if ($existing) {
            return;
        }
        $photo = null;
        $file = Request::current()->file('photo');
        if ($file) {
            $photo = Uploader::storeImage($file);
        }
        $kind = Request::current()->string('violation_kind');
        Database::insertRow('issues', [
            'property_id' => (int) $door['property_id'],
            'resident_id' => $door['resident_id'] ? (int) $door['resident_id'] : null,
            'route_stop_id' => (int) $door['stop_id'],
            'unit_label' => (string) $door['unit_label'],
            'external_property_name' => null,
            'type' => $type,
            'violation_kind' => $type === 'violation' ? ($kind !== '' ? $kind : 'other') : null,
            'source' => 'porter',
            'status' => 'open',
            'reporter_name' => Auth::user()['name'] ?? 'Porter',
            'reporter_contact' => null,
            'reporter_role' => 'Porter',
            'description' => blank_to_null(Request::current()->string('notes')),
            'photo_path' => $photo,
            'created_by' => Auth::user()['id'] ?? null,
            'created_at' => now_dt(),
            'resolved_at' => null,
        ]);
    }

    private function refreshStop(int $stopId): void
    {
        $rows = Database::all('SELECT status FROM door_checks WHERE route_stop_id = ?', [$stopId]);
        if ($rows === []) {
            Database::updateRow('route_stops', ['status' => 'done', 'completed_at' => now_dt()], 'id = ?', [$stopId]);
            return;
        }
        $pending = 0;
        $missed = 0;
        foreach ($rows as $row) {
            if ($row['status'] === 'pending') {
                $pending++;
            }
            if ($row['status'] === 'missed') {
                $missed++;
            }
        }
        $status = 'pending';
        $doneAt = null;
        if ($pending === 0 && $missed === 0) {
            $status = 'done';
            $doneAt = now_dt();
        } elseif ($pending === 0 && $missed > 0) {
            $status = 'missed';
            $doneAt = now_dt();
        }
        Database::updateRow('route_stops', [
            'status' => $status,
            'completed_at' => $doneAt,
        ], 'id = ?', [$stopId]);
    }

    private function authorizedRun(int $runId): array
    {
        $run = Database::one('SELECT * FROM route_runs WHERE id = ?', [$runId]);
        if (!$run) {
            throw new HttpException(404, 'That route does not exist.');
        }
        $user = Auth::user();
        if (!$user) {
            throw new HttpException(302, '/login');
        }
        $assigned = (int) $run['porter_id'] === (int) $user['id'];
        if ($user['role'] === 'porter' && !$assigned) {
            throw new HttpException(403, 'That route is assigned to someone else.');
        }
        return $run;
    }

    private function assertCanClock(array $run): void
    {
        $user = Auth::user();
        $assigned = $user && (int) $run['porter_id'] === (int) $user['id'];
        if ($user && ($user['role'] === 'owner' || $assigned)) {
            return;
        }
        throw new HttpException(403, 'Clock in only on a route assigned to you. An owner can cover any route.');
    }

    private function authorizedStop(int $stopId): array
    {
        $stop = Database::one('SELECT * FROM route_stops WHERE id = ?', [$stopId]);
        if (!$stop) {
            throw new HttpException(404, 'That stop does not exist.');
        }
        $this->authorizedRun((int) $stop['route_run_id']);
        return $stop;
    }
}
