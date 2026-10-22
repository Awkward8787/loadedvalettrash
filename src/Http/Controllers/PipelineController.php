<?php

declare(strict_types=1);

namespace Lvt\Http\Controllers;

use Lvt\Audit;
use Lvt\Csv;
use Lvt\Database;
use Lvt\HttpException;
use Lvt\Request;
use Lvt\Response;
use Lvt\Session;
use Lvt\View;

final class PipelineController extends Controller
{
    private const STAGES = ['lead', 'contacted', 'walkthrough', 'proposal', 'signed', 'live', 'lost'];

    public function index(): Response
    {
        $stage = Request::current()->string('stage');
        $q = Request::current()->string('q');
        $sql = 'SELECT p.*, c.name AS company_name FROM properties p
                LEFT JOIN management_companies c ON c.id = p.management_company_id WHERE 1=1';
        $params = [];
        if (in_array($stage, self::STAGES, true)) {
            $sql .= ' AND p.stage = ?';
            $params[] = $stage;
        }
        if ($q !== '') {
            $sql .= ' AND (p.name LIKE ? OR p.city LIKE ? OR p.address LIKE ?)';
            $like = '%' . $q . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }
        $sql .= ' ORDER BY p.follow_up_on IS NULL, p.follow_up_on ASC, p.name ASC';
        $rows = Database::all($sql, $params);
        $counts = [];
        foreach (self::STAGES as $name) {
            $counts[$name] = (int) Database::value('SELECT COUNT(*) FROM properties WHERE stage = ?', [$name]);
        }
        return View::page('admin/pipeline', [
            'title' => 'Pipeline',
            'description' => 'Property leads',
            'rows' => $rows,
            'counts' => $counts,
            'stage' => $stage,
            'q' => $q,
            'stages' => self::STAGES,
        ], 'layouts/admin');
    }

    public function create(): Response
    {
        return View::page('admin/lead-form', [
            'title' => 'New lead',
            'description' => 'Add a property lead',
            'companies' => Database::all('SELECT id, name FROM management_companies ORDER BY name'),
        ], 'layouts/admin');
    }

    public function store(): Response
    {
        $this->csrf();
        $req = Request::current();
        $errors = [];
        $name = $req->string('name');
        if (mb_strlen($name) < 2) {
            $errors['name'] = 'Enter the property name.';
        }
        $units = $req->int('units_count', 0);
        if ($units < 0 || $units > 5000) {
            $errors['units_count'] = 'Units must be between 0 and 5,000.';
        }
        $email = $req->string('email');
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors['email'] = 'That email does not look valid.';
        }
        $follow = $req->string('follow_up_on');
        if ($follow !== '' && !valid_date($follow)) {
            $errors['follow_up_on'] = 'Use a real follow-up date.';
        }
        if ($errors) {
            return $this->invalid('admin/lead-form', $errors, [
                'title' => 'New lead',
                'description' => 'Add a property lead',
                'companies' => Database::all('SELECT id, name FROM management_companies ORDER BY name'),
            ], 'layouts/admin');
        }
        $companyId = $this->companyId($req->string('company_name'), $req->int('management_company_id', 0));
        $now = now_dt();
        $id = Database::insertRow('properties', [
            'management_company_id' => $companyId,
            'name' => $name,
            'address' => blank_to_null($req->string('address')),
            'city' => blank_to_null($req->string('city')),
            'state' => $req->string('state', 'GA') !== '' ? strtoupper(substr($req->string('state', 'GA'), 0, 2)) : 'GA',
            'postal_code' => blank_to_null($req->string('postal_code')),
            'units_count' => $units,
            'planned_doors' => $req->string('planned_doors') === '' ? null : max(0, $req->int('planned_doors', 0)),
            'website' => blank_to_null($req->string('website')),
            'has_valet_trash' => $this->valetFlag($req->string('has_valet_trash')),
            'stage' => 'lead',
            'lost_reason' => null,
            'pricing_model' => 'opt_in',
            'opt_in_price_cents' => null,
            'property_share_percent' => null,
            'per_door_rate_cents' => null,
            'flat_rate_cents' => null,
            'service_days' => 'mon,tue,wed,thu,fri',
            'setout_start' => null,
            'setout_end' => null,
            'pickup_start' => null,
            'pickup_end' => null,
            'minutes_per_door' => null,
            'minutes_fixed' => null,
            'compactor_location' => null,
            'gate_access_notes' => null,
            'contract_start' => null,
            'contract_end' => null,
            'trial_start' => null,
            'trial_end' => null,
            'follow_up_on' => $follow !== '' ? $follow : null,
            'supplies_cents' => null,
            'gas_cents' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        if ($req->string('manager') !== '') {
            Database::insertRow('property_contacts', [
                'property_id' => $id,
                'name' => $req->string('manager'),
                'role_title' => 'Manager',
                'phone' => blank_to_null($req->string('phone')),
                'email' => blank_to_null($email),
                'is_primary' => 1,
                'created_at' => $now,
            ]);
        }
        if ($req->string('notes') !== '') {
            Database::insertRow('property_notes', [
                'property_id' => $id,
                'user_id' => \Lvt\Auth::user()['id'] ?? null,
                'body' => $req->string('notes'),
                'created_at' => $now,
            ]);
        }
        Audit::write((int) (\Lvt\Auth::user()['id'] ?? 0), 'lead.create', 'property', $id, 'Created lead ' . $name);
        Session::flash('success', 'Lead saved.');
        return Response::redirect('/admin/properties/' . $id);
    }

    public function import(): Response
    {
        $this->csrf();
        $file = Request::current()->file('csv');
        if (!$file) {
            Session::flash('error', 'Choose a CSV file first.');
            return Response::redirect('/admin/pipeline');
        }
        if ((int) ($file['error'] ?? 0) !== UPLOAD_ERR_OK || (int) ($file['size'] ?? 0) > 1024 * 1024) {
            Session::flash('error', 'Upload a CSV smaller than 1 MB.');
            return Response::redirect('/admin/pipeline');
        }
        try {
            $rows = Csv::parseFile((string) $file['tmp_name']);
        } catch (HttpException $e) {
            Session::flash('error', $e->getMessage());
            return Response::redirect('/admin/pipeline');
        }
        $created = 0;
        $skipped = 0;
        foreach ($rows as $row) {
            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '' || str_starts_with(strtoupper($name), 'EXAMPLE')) {
                $skipped++;
                continue;
            }
            $exists = Database::one('SELECT id FROM properties WHERE LOWER(name) = LOWER(?)', [$name]);
            if ($exists) {
                $skipped++;
                continue;
            }
            $units = (int) preg_replace('/[^\d]/', '', (string) ($row['units'] ?? '0'));
            $now = now_dt();
            $company = trim((string) ($row['management_company'] ?? $row['company'] ?? ''));
            $id = Database::insertRow('properties', [
                'management_company_id' => $company !== '' ? $this->companyId($company, 0) : null,
                'name' => mb_substr($name, 0, 180),
                'address' => blank_to_null((string) ($row['address'] ?? '')),
                'city' => blank_to_null((string) ($row['city'] ?? '')),
                'state' => 'GA',
                'postal_code' => blank_to_null((string) ($row['postal_code'] ?? $row['zip'] ?? '')),
                'units_count' => max(0, min(5000, $units)),
                'planned_doors' => null,
                'website' => blank_to_null((string) ($row['website'] ?? '')),
                'has_valet_trash' => $this->valetFlag((string) ($row['has_valet_trash'] ?? '')),
                'stage' => 'lead',
                'lost_reason' => null,
                'pricing_model' => 'opt_in',
                'opt_in_price_cents' => null,
                'property_share_percent' => null,
                'per_door_rate_cents' => null,
                'flat_rate_cents' => null,
                'service_days' => 'mon,tue,wed,thu,fri',
                'setout_start' => null,
                'setout_end' => null,
                'pickup_start' => null,
                'pickup_end' => null,
                'minutes_per_door' => null,
                'minutes_fixed' => null,
                'compactor_location' => null,
                'gate_access_notes' => null,
                'contract_start' => null,
                'contract_end' => null,
                'trial_start' => null,
                'trial_end' => null,
                'follow_up_on' => null,
                'supplies_cents' => null,
                'gas_cents' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $manager = trim((string) ($row['manager'] ?? ''));
            if ($manager !== '' || filled($row['phone'] ?? null) || filled($row['email'] ?? null)) {
                Database::insertRow('property_contacts', [
                    'property_id' => $id,
                    'name' => $manager !== '' ? mb_substr($manager, 0, 120) : 'Office',
                    'role_title' => 'Manager',
                    'phone' => blank_to_null((string) ($row['phone'] ?? '')),
                    'email' => blank_to_null((string) ($row['email'] ?? '')),
                    'is_primary' => 1,
                    'created_at' => $now,
                ]);
            }
            $created++;
        }
        Audit::write((int) (\Lvt\Auth::user()['id'] ?? 0), 'lead.import', 'property', null, 'Imported ' . $created . ' leads, skipped ' . $skipped);
        Session::flash('success', 'Imported ' . $created . ' leads. Skipped ' . $skipped . ' blank or duplicate rows.');
        return Response::redirect('/admin/pipeline');
    }

    public function template(): Response
    {
        return Csv::download('lead-import-template.csv', [
            'name', 'address', 'units', 'manager', 'phone', 'email', 'website', 'has_valet_trash',
        ], [
            ['EXAMPLE — delete this row', '100 Example St, Valdosta, GA', '120', 'Alex Manager', '229-555-0100', 'alex@example.com', 'https://example.com', 'no'],
        ]);
    }

    public function company(): Response
    {
        $this->csrf();
        $name = Request::current()->string('name');
        if (mb_strlen($name) < 2) {
            Session::flash('error', 'Enter a management company name.');
            return Response::redirect('/admin/pipeline');
        }
        $this->companyId($name, 0);
        Audit::write((int) (\Lvt\Auth::user()['id'] ?? 0), 'company.save', 'company', null, 'Saved management company ' . $name);
        Session::flash('success', 'Management company saved. Attach it from a property.');
        return Response::redirect('/admin/pipeline');
    }

    private function companyId(string $name, int $selected): ?int
    {
        if ($selected > 0) {
            $row = Database::one('SELECT id FROM management_companies WHERE id = ?', [$selected]);
            if ($row) {
                return (int) $row['id'];
            }
        }
        $name = trim($name);
        if ($name === '') {
            return null;
        }
        $existing = Database::one('SELECT id FROM management_companies WHERE LOWER(name) = LOWER(?)', [$name]);
        if ($existing) {
            return (int) $existing['id'];
        }
        $now = now_dt();
        return Database::insertRow('management_companies', [
            'name' => mb_substr($name, 0, 160),
            'notes' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function valetFlag(string $value): string
    {
        $value = strtolower(trim($value));
        if (in_array($value, ['yes', 'y', '1', 'true'], true)) {
            return 'yes';
        }
        if (in_array($value, ['no', 'n', '0', 'false'], true)) {
            return 'no';
        }
        return 'unknown';
    }
}
