<?php

declare(strict_types=1);

namespace Lvt\Http\Controllers;

use Lvt\Audit;
use Lvt\Auth;
use Lvt\Database;
use Lvt\FormRateLimiter;
use Lvt\HttpException;
use Lvt\Request;
use Lvt\Response;
use Lvt\Session;
use Lvt\Settings;
use Lvt\View;

final class ApplicationController extends Controller
{
    public function form(): Response
    {
        return View::page('public/apply', $this->pageData());
    }

    public function submit(): Response
    {
        $this->csrf();
        $req = Request::current();
        $email = $req->string('email');
        $ip = $req->ip();
        if (FormRateLimiter::tooMany('apply', $email, $ip)) {
            return $this->invalid('public/apply', [
                'form' => 'We received too many applications. Wait 15 minutes, then send the application again.',
            ], $this->pageData(), 'layouts/public', 429);
        }
        FormRateLimiter::hit('apply', $email, $ip);
        if ($trap = $this->honeypot('/apply')) {
            return $trap;
        }

        $errors = [];
        $name = $req->string('full_name');
        $phone = $req->string('phone');
        $experience = $req->string('experience');
        $notes = $req->string('notes');
        $transport = $req->string('has_transportation');
        $adult = $req->string('is_adult');
        $nights = $this->nights($req);

        if (mb_strlen($name) < 2 || mb_strlen($name) > 120) {
            $errors['full_name'] = 'Enter your full name using 2 to 120 characters.';
        }
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if ($phone === '' || mb_strlen($phone) > 40 || strlen($digits) < 7 || strlen($digits) > 15) {
            $errors['phone'] = 'Enter a phone number with 7 to 15 digits.';
        }
        if ($email === '' || mb_strlen($email) > 190 || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors['email'] = 'Enter a valid email address, like name@example.com.';
        }
        if ($nights === []) {
            $errors['nights'] = 'Choose at least one weeknight, Monday through Friday.';
        }
        if ($transport !== 'yes' && $transport !== 'no') {
            $errors['has_transportation'] = 'Choose yes or no for reliable transportation.';
        }
        if ($adult !== 'yes' && $adult !== 'no') {
            $errors['is_adult'] = 'Choose yes or no for whether you are 18 or older.';
        }
        if (mb_strlen($experience) > 4000) {
            $errors['experience'] = 'Past work experience is limited to 4,000 characters. Shorten it and send the application again.';
        }
        if (mb_strlen($notes) > 4000) {
            $errors['notes'] = 'The extra note is limited to 4,000 characters. Shorten it and send the application again.';
        }
        if ($errors) {
            return $this->invalid('public/apply', $errors, $this->pageData());
        }

        $now = now_dt();
        $id = Database::insertRow('applications', [
            'full_name' => $name,
            'phone' => $phone,
            'email' => $email,
            'nights' => implode(',', $nights),
            'has_transportation' => $transport === 'yes' ? 1 : 0,
            'is_adult' => $adult === 'yes' ? 1 : 0,
            'experience' => blank_to_null($experience),
            'notes' => blank_to_null($notes),
            'status' => 'new',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        Audit::write(null, 'application.create', 'application', $id, 'Public porter application');
        Session::flash('success', 'We have your application. If we follow up, we will use the phone or email you sent.');
        return Response::redirect('/apply');
    }

    public function index(): Response
    {
        $rows = Database::all('SELECT * FROM applications ORDER BY created_at DESC, id DESC LIMIT 200');
        return View::page('admin/applications', [
            'title' => 'Applications',
            'description' => 'Porter applications',
            'applications' => $rows,
        ], 'layouts/admin');
    }

    public function show(array $params): Response
    {
        $row = $this->find($params);
        return View::page('admin/application', [
            'title' => 'Application',
            'description' => 'Porter application',
            'application' => $row,
        ], 'layouts/admin');
    }

    public function update(array $params): Response
    {
        $this->csrf();
        $row = $this->find($params);
        $status = Request::current()->string('status');
        if (!array_key_exists($status, application_statuses())) {
            return $this->invalid('admin/application', [
                'status' => 'Choose a status: New, Contacted, Hired, or Declined.',
            ], [
                'title' => 'Application',
                'description' => 'Porter application',
                'application' => $row,
            ], 'layouts/admin');
        }
        Database::updateRow('applications', [
            'status' => $status,
            'updated_at' => now_dt(),
        ], 'id = ?', [(int) $row['id']]);
        Audit::write((int) (Auth::user()['id'] ?? 0), 'application.update', 'application', (int) $row['id'], 'Status ' . $status);
        Session::flash('success', 'Status saved.');
        return Response::redirect('/admin/applications/' . (int) $row['id']);
    }

    /** @param array<string,string> $params */
    private function find(array $params): array
    {
        $row = Database::one('SELECT * FROM applications WHERE id = ?', [(int) ($params['id'] ?? 0)]);
        if (!$row) {
            throw new HttpException(404, 'That application does not exist.');
        }
        return $row;
    }

    /** @return list<string> */
    private function nights(Request $req): array
    {
        $allowed = array_keys(weeknights());
        $posted = $req->strings('nights');
        return array_values(array_filter($allowed, static fn (string $night): bool => in_array($night, $posted, true)));
    }

    /** @return array<string,string> */
    private function pageData(): array
    {
        $settings = Settings::get();
        $area = trim((string) ($settings['service_area'] ?? ''));
        $intro = $area !== ''
            ? 'We\'re hiring porters for evening pickups in ' . $area . '.'
            : 'We\'re hiring porters for evening pickups.';
        return [
            'title' => 'Apply to work as a porter',
            'description' => $intro,
            'intro' => $intro,
            'setout' => clock_range((string) $settings['setout_start'], (string) $settings['setout_end']),
            'pickup' => clock_range((string) $settings['pickup_start'], (string) $settings['pickup_end']),
        ];
    }
}
