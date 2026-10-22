<?php

declare(strict_types=1);

namespace Lvt\Http\Controllers;

use Lvt\Audit;
use Lvt\Auth;
use Lvt\Database;
use Lvt\Request;
use Lvt\Response;
use Lvt\Session;
use Lvt\Settings;
use Lvt\View;

final class SettingsController extends Controller
{
    public function edit(): Response
    {
        return View::page('admin/settings', [
            'title' => 'Settings',
            'description' => 'Site and cost settings',
            'record' => Settings::get(),
            'holidays' => Database::all('SELECT * FROM holidays ORDER BY observed_on'),
        ], 'layouts/admin');
    }

    public function update(): Response
    {
        $this->csrf();
        $req = Request::current();
        $price = dollars_to_cents($req->string('opt_in_price'));
        $wage = dollars_to_cents($req->string('default_wage'));
        $supplies = dollars_to_cents($req->string('per_site_supplies'));
        $gas = dollars_to_cents($req->string('per_site_gas'));
        $reserve = dollars_to_cents($req->string('per_site_vehicle_reserve'));
        $fixed = dollars_to_cents($req->string('monthly_fixed_costs'));
        if ($price === null || $wage === null || $supplies === null || $gas === null || $reserve === null || $fixed === null) {
            Session::flash('error', 'Prices and costs need to be dollar amounts, like 35.00.');
            return Response::redirect('/admin/settings');
        }
        $share = (float) $req->string('default_property_share_percent', '10');
        if ($share < 0 || $share > 100) {
            Session::flash('error', 'The default property share must be between 0 and 100.');
            return Response::redirect('/admin/settings');
        }
        $multiplier = (float) $req->string('payroll_multiplier', '1.10');
        if ($multiplier < 1 || $multiplier > 3) {
            Session::flash('error', 'Payroll multiplier should be at least 1.00. 1.10 means wages plus 10%.');
            return Response::redirect('/admin/settings');
        }
        Database::updateRow('settings', [
            'business_name' => mb_substr($req->string('business_name', 'Loaded Valet Trash'), 0, 120),
            'owner_name' => mb_substr($req->string('owner_name', 'Anthony Jaamal Jones'), 0, 120),
            'phone' => blank_to_null($req->string('phone')),
            'email' => blank_to_null($req->string('email')),
            'address' => blank_to_null($req->string('address')),
            'service_area' => blank_to_null($req->string('service_area')),
            'insurance_text' => blank_to_null($req->string('insurance_text')),
            'opt_in_price_cents' => $price,
            'default_property_share_percent' => number_format($share, 2, '.', ''),
            'minutes_per_door' => max(0, (float) $req->string('minutes_per_door', '1')),
            'minutes_per_site' => max(0, $req->int('minutes_per_site', 45)),
            'pickup_window_minutes' => max(30, $req->int('pickup_window_minutes', 120)),
            'setout_start' => $this->time($req->string('setout_start', '18:00')),
            'setout_end' => $this->time($req->string('setout_end', '20:00')),
            'pickup_start' => $this->time($req->string('pickup_start', '20:00')),
            'pickup_end' => $this->time($req->string('pickup_end', '22:00')),
            'max_bags_per_night' => max(1, min(10, $req->int('max_bags_per_night', 2))),
            'holiday_bags' => max(1, min(12, $req->int('holiday_bags', 4))),
            'max_bag_weight_lb' => max(1, min(100, $req->int('max_bag_weight_lb', 25))),
            'default_wage_cents' => $wage,
            'payroll_multiplier' => number_format($multiplier, 2, '.', ''),
            'per_site_supplies_cents' => $supplies,
            'per_site_gas_cents' => $gas,
            'per_site_vehicle_reserve_cents' => $reserve,
            'monthly_fixed_costs_cents' => $fixed,
            'remittance_due_day' => max(1, min(28, $req->int('remittance_due_day', 10))),
            'session_timeout_minutes' => max(5, min(240, $req->int('session_timeout_minutes', 30))),
            'missed_report_by' => mb_substr($req->string('missed_report_by', '10:00 AM'), 0, 40),
            'updated_at' => now_dt(),
        ], 'id = ?', [1]);
        Settings::flush();
        Audit::write((int) (Auth::user()['id'] ?? 0), 'settings.update', 'settings', 1, 'Updated site settings');
        Session::flash('success', 'Settings saved. Empty contact fields stay off the public site.');
        return Response::redirect('/admin/settings');
    }

    public function holiday(): Response
    {
        $this->csrf();
        $req = Request::current();
        $name = $req->string('name');
        $date = $req->string('observed_on');
        if (mb_strlen($name) < 2 || !valid_date($date)) {
            Session::flash('error', 'A holiday needs a name and a date.');
            return Response::redirect('/admin/settings');
        }
        $existing = Database::one('SELECT id FROM holidays WHERE observed_on = ?', [$date]);
        $payload = [
            'name' => mb_substr($name, 0, 120),
            'observed_on' => $date,
            'service_off' => $req->string('service_off') === '1' ? 1 : 0,
            'instructions' => mb_substr($req->string('instructions', 'No service.'), 0, 255),
        ];
        if ($existing) {
            Database::updateRow('holidays', $payload, 'id = ?', [(int) $existing['id']]);
        } else {
            Database::insertRow('holidays', $payload);
        }
        Audit::write((int) (Auth::user()['id'] ?? 0), 'holiday.save', 'holiday', null, 'Saved holiday ' . $name);
        Session::flash('success', 'Holiday saved.');
        return Response::redirect('/admin/settings');
    }

    public function holidayDelete(array $params): Response
    {
        $this->csrf();
        Database::exec('DELETE FROM holidays WHERE id = ?', [(int) $params['id']]);
        Audit::write((int) (Auth::user()['id'] ?? 0), 'holiday.delete', 'holiday', (int) $params['id'], 'Removed a holiday');
        Session::flash('success', 'Holiday removed.');
        return Response::redirect('/admin/settings');
    }

    public function audit(): Response
    {
        $rows = Database::all(
            'SELECT a.*, u.name AS user_name FROM audit_log a
             LEFT JOIN users u ON u.id = a.user_id
             ORDER BY a.id DESC LIMIT 300'
        );
        return View::page('admin/audit', [
            'title' => 'Audit log',
            'description' => 'Admin actions',
            'rows' => $rows,
        ], 'layouts/admin');
    }

    private function time(string $value): string
    {
        $dt = \DateTimeImmutable::createFromFormat('H:i', substr($value, 0, 5));
        return $dt ? $dt->format('H:i:s') : '20:00:00';
    }
}
