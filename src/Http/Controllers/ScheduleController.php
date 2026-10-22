<?php

declare(strict_types=1);

namespace Lvt\Http\Controllers;

use Lvt\Audit;
use Lvt\Auth;
use Lvt\Billing\Ledger;
use Lvt\Database;
use Lvt\HttpException;
use Lvt\Request;
use Lvt\Response;
use Lvt\Routing\Builder;
use Lvt\Routing\Estimator;
use Lvt\Session;
use Lvt\Settings;
use Lvt\View;

final class ScheduleController extends Controller
{
    public function index(): Response
    {
        $runs = Database::all(
            'SELECT r.*, u.name AS porter_name FROM route_runs r
             LEFT JOIN users u ON u.id = r.porter_id
             WHERE r.service_date >= ? ORDER BY r.service_date, r.start_time LIMIT 80',
            [date('Y-m-d', strtotime('-1 day'))]
        );
        $templates = Database::all(
            'SELECT t.*, u.name AS porter_name FROM route_templates t
             LEFT JOIN users u ON u.id = t.porter_id ORDER BY t.day_of_week, t.name'
        );
        return View::page('admin/schedule', [
            'title' => 'Schedule',
            'description' => 'Nightly routes',
            'runs' => $runs,
            'templates' => $templates,
            'properties' => Database::all('SELECT id, name, stage FROM properties WHERE stage <> "lost" ORDER BY name'),
            'porters' => Database::all('SELECT id, name FROM users WHERE active = 1 AND role IN ("porter","owner","manager") ORDER BY name'),
        ], 'layouts/admin');
    }

    public function storeRun(): Response
    {
        $this->csrf();
        $req = Request::current();
        $date = $req->string('service_date');
        if (!valid_date($date)) {
            Session::flash('error', 'Pick a service date.');
            return Response::redirect('/admin/schedule');
        }
        $ids = $this->propertyIds($req);
        if ($ids === []) {
            Session::flash('error', 'Add at least one property stop.');
            return Response::redirect('/admin/schedule');
        }
        $porter = $req->int('porter_id', 0);
        $runId = Builder::createRun($date, $porter > 0 ? $porter : null, $req->string('start_time', '20:00'), $ids);
        Audit::write((int) (Auth::user()['id'] ?? 0), 'route.create', 'route_run', $runId, 'Built a route for ' . $date);
        Session::flash('success', 'Route built.');
        return Response::redirect('/admin/schedule/' . $runId);
    }

    public function storeTemplate(): Response
    {
        $this->csrf();
        $req = Request::current();
        $name = $req->string('name');
        $day = $req->int('day_of_week', 0);
        $ids = $this->propertyIds($req);
        if (mb_strlen($name) < 2 || $day < 1 || $day > 5 || $ids === []) {
            Session::flash('error', 'A weekly route needs a name, a weekday, and at least one property.');
            return Response::redirect('/admin/schedule');
        }
        $porter = $req->int('porter_id', 0);
        $id = Database::insertRow('route_templates', [
            'name' => mb_substr($name, 0, 120),
            'porter_id' => $porter > 0 ? $porter : null,
            'day_of_week' => $day,
            'start_time' => $this->time($req->string('start_time', '20:00')),
            'active' => 1,
            'created_at' => now_dt(),
        ]);
        $order = 0;
        foreach ($ids as $propertyId) {
            Database::insertRow('route_template_stops', [
                'template_id' => $id,
                'property_id' => $propertyId,
                'sort_order' => $order++,
            ]);
        }
        Audit::write((int) (Auth::user()['id'] ?? 0), 'route.template', 'route_template', $id, 'Saved weekly template ' . $name);
        Session::flash('success', 'Weekly template saved. Generate nights when you are ready.');
        return Response::redirect('/admin/schedule');
    }

    public function generate(): Response
    {
        $this->csrf();
        $weeks = max(1, min(8, Request::current()->int('weeks', 4)));
        $created = Builder::generateFromTemplates($weeks);
        Audit::write((int) (Auth::user()['id'] ?? 0), 'route.generate', 'route_run', null, 'Generated ' . $created . ' route nights');
        Session::flash('success', 'Generated ' . $created . ' route nights. Holidays marked off, and weekends, were skipped.');
        return Response::redirect('/admin/schedule');
    }

    public function calendar(): Response
    {
        $month = Request::current()->string('month', date('Y-m'));
        if (preg_match('/^\d{4}-\d{2}$/', $month) !== 1) {
            $month = date('Y-m');
        }
        $start = $month . '-01';
        $end = date('Y-m-t', strtotime($start . ' 12:00:00'));
        $runs = Database::all(
            'SELECT r.*, u.name AS porter_name FROM route_runs r
             LEFT JOIN users u ON u.id = r.porter_id
             WHERE r.service_date BETWEEN ? AND ? ORDER BY r.service_date, r.start_time',
            [$start, $end]
        );
        $byDay = [];
        foreach ($runs as $run) {
            $byDay[$run['service_date']][] = $run;
        }
        return View::page('admin/calendar', [
            'title' => 'Calendar',
            'description' => 'Route calendar',
            'month' => $month,
            'start' => $start,
            'end' => $end,
            'byDay' => $byDay,
        ], 'layouts/admin');
    }

    public function show(array $params): Response
    {
        $run = Database::one(
            'SELECT r.*, u.name AS porter_name FROM route_runs r LEFT JOIN users u ON u.id = r.porter_id WHERE r.id = ?',
            [(int) $params['id']]
        );
        if (!$run) {
            throw new HttpException(404, 'That route does not exist.');
        }
        $stops = Database::all(
            'SELECT s.*, p.name AS property_name, p.address, p.city, p.state, p.postal_code, p.compactor_location,
                    b.name AS building_name
             FROM route_stops s
             INNER JOIN properties p ON p.id = s.property_id
             LEFT JOIN buildings b ON b.id = s.building_id
             WHERE s.route_run_id = ? ORDER BY s.sort_order, s.id',
            [(int) $run['id']]
        );
        $seen = [];
        $minutes = 0;
        $addresses = [];
        foreach ($stops as $stop) {
            $pid = (int) $stop['property_id'];
            if (!isset($seen[$pid])) {
                $property = Database::one('SELECT * FROM properties WHERE id = ?', [$pid]);
                if ($property) {
                    $minutes += Ledger::propertyMinutes($property);
                    $address = property_address($property);
                    if ($address !== '') {
                        $addresses[] = $address;
                    }
                }
                $seen[$pid] = true;
            }
        }
        $window = (int) Settings::get()['pickup_window_minutes'];
        return View::page('admin/route', [
            'title' => 'Route ' . $run['service_date'],
            'description' => 'Route detail',
            'run' => $run,
            'stops' => $stops,
            'minutes' => $minutes,
            'portersNeeded' => Estimator::portersNeeded($minutes, $window),
            'finish' => Estimator::finish(substr((string) $run['start_time'], 0, 5), $minutes),
            'over' => Estimator::overWindow($minutes, $window),
            'directions' => maps_directions($addresses),
            'porters' => Database::all('SELECT id, name FROM users WHERE active = 1 ORDER BY name'),
            'window' => $window,
        ], 'layouts/admin');
    }

    public function update(array $params): Response
    {
        $this->csrf();
        $run = Database::one('SELECT * FROM route_runs WHERE id = ?', [(int) $params['id']]);
        if (!$run) {
            throw new HttpException(404, 'That route does not exist.');
        }
        $req = Request::current();
        $status = $req->string('status', (string) $run['status']);
        if (!in_array($status, ['scheduled', 'in_progress', 'complete', 'cancelled'], true)) {
            $status = (string) $run['status'];
        }
        $porter = $req->int('porter_id', 0);
        Database::updateRow('route_runs', [
            'porter_id' => $porter > 0 ? $porter : null,
            'start_time' => $this->time($req->string('start_time', substr((string) $run['start_time'], 0, 5))),
            'status' => $status,
            'notes' => blank_to_null($req->string('notes')),
        ], 'id = ?', [(int) $run['id']]);
        Audit::write((int) (Auth::user()['id'] ?? 0), 'route.update', 'route_run', (int) $run['id'], 'Updated route assignment');
        Session::flash('success', 'Route updated.');
        return Response::redirect('/admin/schedule/' . $run['id']);
    }

    /** @return list<int> */
    private function propertyIds(Request $req): array
    {
        $raw = $req->post['property_ids'] ?? [];
        if (!is_array($raw)) {
            return [];
        }
        $ids = [];
        foreach ($raw as $value) {
            $id = (int) $value;
            if ($id > 0 && !in_array($id, $ids, true)) {
                $ids[] = $id;
            }
        }
        return $ids;
    }

    private function time(string $value): string
    {
        $dt = \DateTimeImmutable::createFromFormat('H:i', substr($value, 0, 5));
        return $dt ? $dt->format('H:i:s') : '20:00:00';
    }
}
