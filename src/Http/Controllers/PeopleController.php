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

final class PeopleController extends Controller
{
    public function index(): Response
    {
        $people = Database::all(
            'SELECT id, name, email, phone, role, pay_rate_cents, active, availability FROM users ORDER BY role, name'
        );
        return View::page('admin/employees', [
            'title' => 'Team',
            'description' => 'Porters and managers',
            'people' => $people,
        ], 'layouts/admin');
    }

    public function store(): Response
    {
        $this->csrf();
        $req = Request::current();
        $errors = $this->validatePerson($req, true);
        if ($errors) {
            Session::flash('error', implode(' ', $errors));
            Session::flashOld($req->post);
            return Response::redirect('/admin/employees');
        }
        $role = $this->allowedRole($req->string('role', 'porter'));
        $now = now_dt();
        $id = Database::insertRow('users', [
            'name' => $req->string('name'),
            'email' => strtolower($req->string('email')),
            'password_hash' => password_hash((string) ($req->post['password'] ?? ''), PASSWORD_DEFAULT),
            'role' => $role,
            'phone' => blank_to_null($req->string('phone')),
            'pay_rate_cents' => dollars_to_cents($req->string('pay_rate')) ?? (int) \Lvt\Settings::get()['default_wage_cents'],
            'availability' => blank_to_null($req->string('availability')),
            'totp_secret' => null,
            'totp_enabled' => 0,
            'active' => 1,
            'last_login_at' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        Audit::write((int) (Auth::user()['id'] ?? 0), 'user.create', 'user', $id, 'Added ' . $role . ' ' . $req->string('name'));
        Session::flash('success', 'Team member added. They will set up an authenticator the first time they sign in.');
        return Response::redirect('/admin/employees/' . $id);
    }

    public function show(array $params): Response
    {
        $person = $this->person((int) $params['id']);
        $entries = Database::all(
            'SELECT t.*, r.service_date FROM time_entries t
             LEFT JOIN route_runs r ON r.id = t.route_run_id
             WHERE t.user_id = ? ORDER BY t.clock_in DESC LIMIT 60',
            [(int) $person['id']]
        );
        $minutes = 0;
        $monthStart = date('Y-m-01 00:00:00');
        foreach ($entries as $entry) {
            if ($entry['clock_out'] && $entry['clock_in'] >= $monthStart) {
                $minutes += max(0, strtotime((string) $entry['clock_out']) - strtotime((string) $entry['clock_in']));
            }
        }
        return View::page('admin/employee', [
            'title' => (string) $person['name'],
            'description' => 'Team member',
            'person' => $person,
            'entries' => $entries,
            'monthHours' => round($minutes / 3600, 2),
        ], 'layouts/admin');
    }

    public function update(array $params): Response
    {
        $this->csrf();
        $person = $this->person((int) $params['id']);
        $req = Request::current();
        if ($req->string('clock_in') !== '' && $req->string('clock_out') !== '') {
            $in = strtotime($req->string('clock_in'));
            $out = strtotime($req->string('clock_out'));
            if ($in === false || $out === false || $out <= $in) {
                Session::flash('error', 'Clock-out has to be after clock-in.');
                return Response::redirect('/admin/employees/' . $person['id']);
            }
            Database::insertRow('time_entries', [
                'user_id' => (int) $person['id'],
                'route_run_id' => $req->int('route_run_id', 0) > 0 ? $req->int('route_run_id', 0) : null,
                'clock_in' => date('Y-m-d H:i:s', $in),
                'clock_out' => date('Y-m-d H:i:s', $out),
                'notes' => blank_to_null($req->string('time_notes')),
            ]);
            Audit::write((int) (Auth::user()['id'] ?? 0), 'time.manual', 'user', (int) $person['id'], 'Added hours');
            Session::flash('success', 'Hours added.');
            return Response::redirect('/admin/employees/' . $person['id']);
        }
        $errors = $this->validatePerson($req, false, (int) $person['id']);
        if ($errors) {
            Session::flash('error', implode(' ', $errors));
            return Response::redirect('/admin/employees/' . $person['id']);
        }
        $role = $this->allowedRole($req->string('role', (string) $person['role']));
        $active = $req->string('active') === '1' ? 1 : 0;
        if ((string) $person['role'] === 'owner' && ($role !== 'owner' || $active !== 1)) {
            $owners = (int) Database::value('SELECT COUNT(*) FROM users WHERE role = "owner" AND active = 1');
            if ($owners <= 1) {
                Session::flash('error', 'Keep at least one active owner.');
                return Response::redirect('/admin/employees/' . $person['id']);
            }
        }
        $data = [
            'name' => $req->string('name'),
            'email' => strtolower($req->string('email')),
            'role' => $role,
            'phone' => blank_to_null($req->string('phone')),
            'pay_rate_cents' => dollars_to_cents($req->string('pay_rate')) ?? (int) $person['pay_rate_cents'],
            'availability' => blank_to_null($req->string('availability')),
            'active' => $active,
            'updated_at' => now_dt(),
        ];
        $password = (string) ($req->post['password'] ?? '');
        if ($password !== '') {
            $problem = password_problem($password);
            if ($problem) {
                Session::flash('error', $problem);
                return Response::redirect('/admin/employees/' . $person['id']);
            }
            $data['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
        }
        Database::updateRow('users', $data, 'id = ?', [(int) $person['id']]);
        Audit::write((int) (Auth::user()['id'] ?? 0), 'user.update', 'user', (int) $person['id'], 'Updated team member');
        Session::flash('success', 'Team member saved.');
        return Response::redirect('/admin/employees/' . $person['id']);
    }

    private function person(int $id): array
    {
        $row = Database::one('SELECT id, name, email, phone, role, pay_rate_cents, availability, active, totp_enabled, created_at FROM users WHERE id = ?', [$id]);
        if (!$row) {
            throw new HttpException(404, 'That team member does not exist.');
        }
        return $row;
    }

    private function allowedRole(string $role): string
    {
        $actor = Auth::user();
        if (!in_array($role, ['owner', 'manager', 'porter'], true)) {
            $role = 'porter';
        }
        if ($role === 'owner' && ($actor['role'] ?? '') !== 'owner') {
            return 'manager';
        }
        return $role;
    }

    private function validatePerson(Request $req, bool $passwordRequired, int $ignoreId = 0): array
    {
        $errors = [];
        if (mb_strlen($req->string('name')) < 2) {
            $errors[] = 'Name is required.';
        }
        $email = strtolower($req->string('email'));
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors[] = 'A valid email is required.';
        } else {
            $dupe = Database::one('SELECT id FROM users WHERE email = ? AND id <> ?', [$email, $ignoreId]);
            if ($dupe) {
                $errors[] = 'That email is already used.';
            }
        }
        $password = (string) ($req->post['password'] ?? '');
        if ($passwordRequired || $password !== '') {
            $problem = password_problem($password);
            if ($passwordRequired && $problem) {
                $errors[] = $problem;
            }
        }
        if ($req->string('pay_rate') !== '' && dollars_to_cents($req->string('pay_rate')) === null) {
            $errors[] = 'Pay rate should look like 15.00.';
        }
        return $errors;
    }
}
