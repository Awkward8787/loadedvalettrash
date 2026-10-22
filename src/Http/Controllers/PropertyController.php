<?php

declare(strict_types=1);

namespace Lvt\Http\Controllers;

use Lvt\Audit;
use Lvt\Auth;
use Lvt\Billing\Ledger;
use Lvt\Csv;
use Lvt\Database;
use Lvt\HttpException;
use Lvt\Request;
use Lvt\Response;
use Lvt\Session;
use Lvt\Settings;
use Lvt\View;

final class PropertyController extends Controller
{
    private const STAGES = ['lead', 'contacted', 'walkthrough', 'proposal', 'signed', 'live', 'lost'];

    public function index(): Response
    {
        $rows = Database::all(
            'SELECT p.*, (SELECT COUNT(*) FROM residents r WHERE r.property_id = p.id AND r.status = "active") AS active_residents
             FROM properties p
             WHERE p.stage IN ("signed","live")
             ORDER BY p.name'
        );
        return View::page('admin/properties', [
            'title' => 'Properties',
            'description' => 'Signed and live properties',
            'rows' => $rows,
        ], 'layouts/admin');
    }

    public function show(array $params): Response
    {
        $property = $this->property((int) $params['id']);
        $user = Auth::user();
        return View::page('admin/property', [
            'title' => (string) $property['name'],
            'description' => 'Property record',
            'property' => $property,
            'company' => $property['management_company_id']
                ? Database::one('SELECT * FROM management_companies WHERE id = ?', [(int) $property['management_company_id']])
                : null,
            'companies' => Database::all('SELECT id, name FROM management_companies ORDER BY name'),
            'contacts' => Database::all('SELECT * FROM property_contacts WHERE property_id = ? ORDER BY is_primary DESC, id', [(int) $property['id']]),
            'notes' => Database::all(
                'SELECT n.*, u.name AS author FROM property_notes n LEFT JOIN users u ON u.id = n.user_id WHERE n.property_id = ? ORDER BY n.id DESC',
                [(int) $property['id']]
            ),
            'buildings' => Database::all('SELECT * FROM buildings WHERE property_id = ? ORDER BY sort_order, id', [(int) $property['id']]),
            'showGate' => Auth::canSeeGateNotes($user, (int) $property['id']),
            'stages' => self::STAGES,
            'doorEstimate' => Ledger::estimateDoors($property),
            'minutes' => Ledger::propertyMinutes($property),
        ], 'layouts/admin');
    }

    public function update(array $params): Response
    {
        $this->csrf();
        $property = $this->property((int) $params['id']);
        $req = Request::current();
        $stage = $req->string('stage');
        if (!in_array($stage, self::STAGES, true)) {
            $stage = (string) $property['stage'];
        }
        $model = $req->string('pricing_model');
        if (!in_array($model, ['opt_in', 'per_door', 'flat'], true)) {
            $model = 'opt_in';
        }
        $share = $req->string('property_share_percent');
        $shareValue = null;
        if ($share !== '') {
            if (!is_numeric($share) || (float) $share < 0 || (float) $share > 100) {
                Session::flash('error', 'Property share must be between 0 and 100.');
                return Response::redirect('/admin/properties/' . $property['id']);
            }
            $shareValue = number_format((float) $share, 2, '.', '');
        }
        $days = $req->post['service_days'] ?? ['mon', 'tue', 'wed', 'thu', 'fri'];
        if (!is_array($days)) {
            $days = ['mon', 'tue', 'wed', 'thu', 'fri'];
        }
        $allowed = ['mon', 'tue', 'wed', 'thu', 'fri'];
        $days = array_values(array_intersect($allowed, array_map('strval', $days)));
        if ($days === []) {
            $days = $allowed;
        }
        $companyId = $req->int('management_company_id', 0);
        $companyName = $req->string('company_name');
        if ($companyName !== '') {
            $existing = Database::one('SELECT id FROM management_companies WHERE LOWER(name) = LOWER(?)', [$companyName]);
            if ($existing) {
                $companyId = (int) $existing['id'];
            } else {
                $now = now_dt();
                $companyId = Database::insertRow('management_companies', [
                    'name' => mb_substr($companyName, 0, 160),
                    'notes' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
        $data = [
            'name' => mb_substr($req->string('name', (string) $property['name']), 0, 180),
            'management_company_id' => $companyId > 0 ? $companyId : null,
            'address' => blank_to_null($req->string('address')),
            'city' => blank_to_null($req->string('city')),
            'state' => strtoupper(substr($req->string('state', 'GA'), 0, 2)) ?: 'GA',
            'postal_code' => blank_to_null($req->string('postal_code')),
            'units_count' => max(0, min(5000, $req->int('units_count', 0))),
            'planned_doors' => $req->string('planned_doors') === '' ? null : max(0, $req->int('planned_doors', 0)),
            'website' => blank_to_null($req->string('website')),
            'has_valet_trash' => in_array($req->string('has_valet_trash'), ['yes', 'no', 'unknown'], true) ? $req->string('has_valet_trash') : 'unknown',
            'stage' => $stage,
            'lost_reason' => $stage === 'lost' ? blank_to_null($req->string('lost_reason')) : null,
            'pricing_model' => $model,
            'opt_in_price_cents' => dollars_to_cents($req->string('opt_in_price')),
            'property_share_percent' => $shareValue,
            'per_door_rate_cents' => dollars_to_cents($req->string('per_door_rate')),
            'flat_rate_cents' => dollars_to_cents($req->string('flat_rate')),
            'service_days' => implode(',', $days),
            'setout_start' => $this->timeOrNull($req->string('setout_start')),
            'setout_end' => $this->timeOrNull($req->string('setout_end')),
            'pickup_start' => $this->timeOrNull($req->string('pickup_start')),
            'pickup_end' => $this->timeOrNull($req->string('pickup_end')),
            'minutes_per_door' => $req->string('minutes_per_door') === '' ? null : $req->string('minutes_per_door'),
            'minutes_fixed' => $req->string('minutes_fixed') === '' ? null : max(0, $req->int('minutes_fixed', 45)),
            'compactor_location' => blank_to_null($req->string('compactor_location')),
            'contract_start' => $this->dateOrNull($req->string('contract_start')),
            'contract_end' => $this->dateOrNull($req->string('contract_end')),
            'trial_start' => $this->dateOrNull($req->string('trial_start')),
            'trial_end' => $this->dateOrNull($req->string('trial_end')),
            'follow_up_on' => $this->dateOrNull($req->string('follow_up_on')),
            'supplies_cents' => dollars_to_cents($req->string('supplies')),
            'gas_cents' => dollars_to_cents($req->string('gas')),
            'updated_at' => now_dt(),
        ];
        $gateTouched = false;
        if (Auth::canSeeGateNotes(Auth::user(), (int) $property['id'])) {
            $incoming = $req->string('gate_access_notes');
            $data['gate_access_notes'] = blank_to_null($incoming);
            $gateTouched = trim((string) ($property['gate_access_notes'] ?? '')) !== trim($incoming);
        }
        if (mb_strlen((string) $data['name']) < 2) {
            Session::flash('error', 'Property name is required.');
            return Response::redirect('/admin/properties/' . $property['id']);
        }
        Database::updateRow('properties', $data, 'id = ?', [(int) $property['id']]);
        Audit::write(
            (int) (Auth::user()['id'] ?? 0),
            'property.update',
            'property',
            (int) $property['id'],
            $gateTouched ? 'Updated property fields, including access notes' : 'Updated property fields'
        );
        Session::flash('success', 'Property saved.');
        return Response::redirect('/admin/properties/' . $property['id']);
    }

    public function note(array $params): Response
    {
        $this->csrf();
        $property = $this->property((int) $params['id']);
        $body = Request::current()->string('body');
        if (mb_strlen($body) < 2) {
            Session::flash('error', 'Write a note before saving.');
            return Response::redirect('/admin/properties/' . $property['id']);
        }
        Database::insertRow('property_notes', [
            'property_id' => (int) $property['id'],
            'user_id' => Auth::user()['id'] ?? null,
            'body' => $body,
            'created_at' => now_dt(),
        ]);
        Audit::write((int) (Auth::user()['id'] ?? 0), 'property.note', 'property', (int) $property['id'], 'Added a property note');
        Session::flash('success', 'Note added.');
        return Response::redirect('/admin/properties/' . $property['id']);
    }

    public function contact(array $params): Response
    {
        $this->csrf();
        $property = $this->property((int) $params['id']);
        $req = Request::current();
        if ($req->string('delete_contact_id') !== '') {
            Database::exec('DELETE FROM property_contacts WHERE id = ? AND property_id = ?', [$req->int('delete_contact_id'), (int) $property['id']]);
            Session::flash('success', 'Contact removed.');
            return Response::redirect('/admin/properties/' . $property['id']);
        }
        $name = $req->string('name');
        if (mb_strlen($name) < 2) {
            Session::flash('error', 'Enter the contact name.');
            return Response::redirect('/admin/properties/' . $property['id']);
        }
        Database::insertRow('property_contacts', [
            'property_id' => (int) $property['id'],
            'name' => $name,
            'role_title' => blank_to_null($req->string('role_title')),
            'phone' => blank_to_null($req->string('phone')),
            'email' => blank_to_null($req->string('email')),
            'is_primary' => $req->string('is_primary') === '1' ? 1 : 0,
            'created_at' => now_dt(),
        ]);
        Audit::write((int) (Auth::user()['id'] ?? 0), 'property.contact', 'property', (int) $property['id'], 'Added a contact');
        Session::flash('success', 'Contact added.');
        return Response::redirect('/admin/properties/' . $property['id']);
    }

    public function building(array $params): Response
    {
        $this->csrf();
        $property = $this->property((int) $params['id']);
        $req = Request::current();
        if ($req->string('delete_building_id') !== '') {
            Database::exec('DELETE FROM buildings WHERE id = ? AND property_id = ?', [$req->int('delete_building_id'), (int) $property['id']]);
            Session::flash('success', 'Building removed.');
            return Response::redirect('/admin/properties/' . $property['id']);
        }
        $name = $req->string('name');
        if (mb_strlen($name) < 1) {
            Session::flash('error', 'Name the building or breezeway.');
            return Response::redirect('/admin/properties/' . $property['id']);
        }
        $payload = [
            'name' => mb_substr($name, 0, 120),
            'floors' => $req->string('floors') === '' ? null : max(0, min(40, $req->int('floors', 0))),
            'unit_count' => $req->string('unit_count') === '' ? null : max(0, $req->int('unit_count', 0)),
            'sort_order' => max(0, $req->int('sort_order', 0)),
            'hazards_notes' => blank_to_null($req->string('hazards_notes')),
            'compactor_trip_after' => $req->string('compactor_trip_after') === '1' ? 1 : 0,
        ];
        $existing = $req->int('building_id', 0);
        if ($existing > 0) {
            Database::updateRow('buildings', $payload, 'id = ? AND property_id = ?', [$existing, (int) $property['id']]);
        } else {
            $payload['property_id'] = (int) $property['id'];
            Database::insertRow('buildings', $payload);
        }
        Audit::write((int) (Auth::user()['id'] ?? 0), 'property.building', 'property', (int) $property['id'], 'Saved a building stop');
        Session::flash('success', 'Building saved.');
        return Response::redirect('/admin/properties/' . $property['id']);
    }

    public function residents(array $params): Response
    {
        $property = $this->property((int) $params['id']);
        $year = Request::current()->int('year', (int) date('Y'));
        $month = Request::current()->int('month', (int) date('n'));
        if ($month < 1 || $month > 12) {
            $month = (int) date('n');
        }
        $residents = Database::all(
            'SELECT r.*, b.name AS building_name FROM residents r
             LEFT JOIN buildings b ON b.id = r.building_id
             WHERE r.property_id = ? ORDER BY r.unit_number',
            [(int) $property['id']]
        );
        return View::page('admin/residents', [
            'title' => 'Roster · ' . $property['name'],
            'description' => 'Resident roster',
            'property' => $property,
            'residents' => $residents,
            'buildings' => Database::all('SELECT id, name FROM buildings WHERE property_id = ? ORDER BY sort_order', [(int) $property['id']]),
            'year' => $year,
            'month' => $month,
            'billable' => Ledger::billableCount((int) $property['id'], $year, $month),
        ], 'layouts/admin');
    }

    public function residentStore(array $params): Response
    {
        $this->csrf();
        $property = $this->property((int) $params['id']);
        $req = Request::current();
        $unit = $req->string('unit_number');
        if ($unit === '') {
            Session::flash('error', 'Unit number is required.');
            return Response::redirect('/admin/properties/' . $property['id'] . '/residents');
        }
        $status = $req->string('status', 'active');
        if (!in_array($status, ['pending', 'active', 'cancelled'], true)) {
            $status = 'active';
        }
        $now = now_dt();
        Database::insertRow('residents', [
            'property_id' => (int) $property['id'],
            'building_id' => $req->int('building_id', 0) > 0 ? $req->int('building_id', 0) : null,
            'unit_number' => mb_substr($unit, 0, 40),
            'name' => blank_to_null($req->string('name')),
            'start_date' => $this->dateOrNull($req->string('start_date')),
            'cancel_date' => $this->dateOrNull($req->string('cancel_date')),
            'status' => $status,
            'notes' => blank_to_null($req->string('notes')),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        Audit::write((int) (Auth::user()['id'] ?? 0), 'resident.create', 'property', (int) $property['id'], 'Added unit ' . $unit);
        Session::flash('success', 'Resident added.');
        return Response::redirect('/admin/properties/' . $property['id'] . '/residents');
    }

    public function residentUpdate(array $params): Response
    {
        $this->csrf();
        $property = $this->property((int) $params['id']);
        $resident = Database::one('SELECT * FROM residents WHERE id = ? AND property_id = ?', [(int) $params['rid'], (int) $property['id']]);
        if (!$resident) {
            throw new HttpException(404, 'That resident is not on this roster.');
        }
        $req = Request::current();
        $status = $req->string('status', (string) $resident['status']);
        if (!in_array($status, ['pending', 'active', 'cancelled'], true)) {
            $status = (string) $resident['status'];
        }
        Database::updateRow('residents', [
            'building_id' => $req->int('building_id', 0) > 0 ? $req->int('building_id', 0) : null,
            'unit_number' => mb_substr($req->string('unit_number', (string) $resident['unit_number']), 0, 40),
            'name' => blank_to_null($req->string('name')),
            'start_date' => $this->dateOrNull($req->string('start_date')),
            'cancel_date' => $this->dateOrNull($req->string('cancel_date')),
            'status' => $status,
            'notes' => blank_to_null($req->string('notes')),
            'updated_at' => now_dt(),
        ], 'id = ?', [(int) $resident['id']]);
        Audit::write((int) (Auth::user()['id'] ?? 0), 'resident.update', 'resident', (int) $resident['id'], 'Updated unit ' . $resident['unit_number']);
        Session::flash('success', 'Resident updated.');
        return Response::redirect('/admin/properties/' . $property['id'] . '/residents');
    }

    public function residentImport(array $params): Response
    {
        $this->csrf();
        $property = $this->property((int) $params['id']);
        $file = Request::current()->file('csv');
        if (!$file || (int) ($file['error'] ?? 1) !== UPLOAD_ERR_OK) {
            Session::flash('error', 'Choose a roster CSV.');
            return Response::redirect('/admin/properties/' . $property['id'] . '/residents');
        }
        try {
            $rows = Csv::parseFile((string) $file['tmp_name']);
        } catch (HttpException $e) {
            Session::flash('error', $e->getMessage());
            return Response::redirect('/admin/properties/' . $property['id'] . '/residents');
        }
        $count = 0;
        foreach ($rows as $row) {
            $unit = trim((string) ($row['unit'] ?? $row['unit_number'] ?? ''));
            $residentName = trim((string) ($row['name'] ?? ''));
            if ($unit === '' || str_starts_with(strtoupper($residentName), 'EXAMPLE')) {
                continue;
            }
            $status = strtolower((string) ($row['status'] ?? 'active'));
            if (!in_array($status, ['pending', 'active', 'cancelled'], true)) {
                $status = 'active';
            }
            $buildingId = null;
            $buildingName = trim((string) ($row['building'] ?? ''));
            if ($buildingName !== '') {
                $building = Database::one(
                    'SELECT id FROM buildings WHERE property_id = ? AND LOWER(name) = LOWER(?)',
                    [(int) $property['id'], $buildingName]
                );
                if (!$building) {
                    $buildingId = Database::insertRow('buildings', [
                        'property_id' => (int) $property['id'],
                        'name' => mb_substr($buildingName, 0, 120),
                        'floors' => null,
                        'unit_count' => null,
                        'sort_order' => 0,
                        'hazards_notes' => null,
                        'compactor_trip_after' => 0,
                    ]);
                } else {
                    $buildingId = (int) $building['id'];
                }
            }
            $now = now_dt();
            $existing = Database::one(
                'SELECT id FROM residents WHERE property_id = ? AND unit_number = ?',
                [(int) $property['id'], $unit]
            );
            $payload = [
                'building_id' => $buildingId,
                'name' => blank_to_null((string) ($row['name'] ?? $row['resident'] ?? '')),
                'start_date' => $this->dateOrNull((string) ($row['start_date'] ?? $row['start'] ?? '')),
                'cancel_date' => $this->dateOrNull((string) ($row['cancel_date'] ?? $row['cancel'] ?? '')),
                'status' => $status,
                'notes' => blank_to_null((string) ($row['notes'] ?? '')),
                'updated_at' => $now,
            ];
            if ($existing) {
                Database::updateRow('residents', $payload, 'id = ?', [(int) $existing['id']]);
            } else {
                $payload['property_id'] = (int) $property['id'];
                $payload['unit_number'] = mb_substr($unit, 0, 40);
                $payload['created_at'] = $now;
                Database::insertRow('residents', $payload);
            }
            $count++;
        }
        Audit::write((int) (Auth::user()['id'] ?? 0), 'resident.import', 'property', (int) $property['id'], 'Imported ' . $count . ' roster rows');
        Session::flash('success', 'Imported ' . $count . ' roster rows.');
        return Response::redirect('/admin/properties/' . $property['id'] . '/residents');
    }

    public function residentExport(array $params): Response
    {
        $property = $this->property((int) $params['id']);
        $year = Request::current()->int('year', (int) date('Y'));
        $month = Request::current()->int('month', (int) date('n'));
        $settings = Settings::get();
        $rate = (int) ($property['opt_in_price_cents'] ?? $settings['opt_in_price_cents']);
        if ($property['pricing_model'] === 'per_door') {
            $rate = (int) ($property['per_door_rate_cents'] ?? 0);
        }
        if ($property['pricing_model'] === 'flat') {
            $rate = 0;
        }
        $rows = Database::all(
            'SELECT r.*, b.name AS building_name FROM residents r
             LEFT JOIN buildings b ON b.id = r.building_id
             WHERE r.property_id = ? ORDER BY r.unit_number',
            [(int) $property['id']]
        );
        $start = sprintf('%04d-%02d-01', $year, $month);
        $end = date('Y-m-t', strtotime($start . ' 12:00:00'));
        $out = [];
        foreach ($rows as $row) {
            $billable = $this->rowBillable($row, $start, $end);
            $out[] = [
                (string) $property['name'],
                (string) ($row['building_name'] ?? ''),
                (string) $row['unit_number'],
                (string) ($row['name'] ?? ''),
                (string) ($row['start_date'] ?? ''),
                (string) ($row['cancel_date'] ?? ''),
                (string) $row['status'],
                $billable ? 'Y' : 'N',
                $billable ? number_format($rate / 100, 2, '.', '') : '0.00',
            ];
        }
        Audit::write((int) (Auth::user()['id'] ?? 0), 'resident.export', 'property', (int) $property['id'], 'Exported roster ' . $year . '-' . $month);
        $slug = preg_replace('/[^a-z0-9]+/i', '-', (string) $property['name']) ?: 'property';
        return Csv::download('roster-' . trim((string) $slug, '-') . '-' . sprintf('%04d-%02d', $year, $month) . '.csv', [
            'property', 'building', 'unit', 'resident_name', 'start_date', 'cancel_date', 'status', 'active_this_month', 'monthly_fee',
        ], $out);
    }

    public function residentTemplate(): Response
    {
        return Csv::download('roster-import-template.csv', [
            'building', 'unit', 'name', 'start_date', 'cancel_date', 'status', 'notes',
        ], [
            ['Building A', '101', 'EXAMPLE Resident', '2026-11-01', '', 'active', 'Delete this example row'],
        ]);
    }

    private function property(int $id): array
    {
        $row = Database::one('SELECT * FROM properties WHERE id = ?', [$id]);
        if (!$row) {
            throw new HttpException(404, 'That property is not in the system.');
        }
        return $row;
    }

    private function dateOrNull(string $value): ?string
    {
        return $value !== '' && valid_date($value) ? $value : null;
    }

    private function timeOrNull(string $value): ?string
    {
        if ($value === '') {
            return null;
        }
        $dt = \DateTimeImmutable::createFromFormat('H:i', $value);
        return $dt ? $dt->format('H:i:s') : null;
    }

    private function rowBillable(array $row, string $start, string $end): bool
    {
        if (!in_array($row['status'], ['active', 'cancelled'], true)) {
            return false;
        }
        if ($row['start_date'] !== null && $row['start_date'] > $end) {
            return false;
        }
        if ($row['status'] === 'active') {
            return $row['cancel_date'] === null || $row['cancel_date'] >= $start;
        }
        return $row['cancel_date'] !== null && $row['cancel_date'] >= $start;
    }
}
