<?php

declare(strict_types=1);

namespace Lvt\Http\Controllers;

use Lvt\Auth;
use Lvt\Billing\Ledger;
use Lvt\Database;
use Lvt\Response;
use Lvt\View;

final class DashboardController extends Controller
{
    public function index(): Response
    {
        $user = Auth::user();
        if (!$user) {
            return Response::redirect('/login');
        }
        if ($user['role'] === 'porter') {
            return Response::redirect('/admin/tonight');
        }
        $today = today_date();
        $year = (int) date('Y');
        $month = (int) date('n');
        $properties = (int) Database::value('SELECT COUNT(*) FROM properties WHERE stage = "live"');
        $doors = Ledger::activeDoorCount();
        $routes = Database::all(
            'SELECT r.*, u.name AS porter_name,
                    (SELECT COUNT(DISTINCT s.property_id) FROM route_stops s WHERE s.route_run_id = r.id) AS property_count
             FROM route_runs r
             LEFT JOIN users u ON u.id = r.porter_id
             WHERE r.service_date = ? AND r.status <> "cancelled"
             ORDER BY r.start_time, r.id',
            [$today]
        );
        $invoices = Database::all(
            'SELECT * FROM invoices WHERE period_year = ? AND period_month = ? AND status <> "void"',
            [$year, $month]
        );
        $revenue = 0;
        $collected = 0;
        $profit = 0;
        foreach ($invoices as $invoice) {
            $revenue += (int) $invoice['due_cents'];
            if ($invoice['status'] === 'paid') {
                $collected += (int) $invoice['due_cents'];
            }
            $profit += Ledger::profitForInvoice($invoice)['profit_cents'];
        }
        $openIssues = (int) Database::value('SELECT COUNT(*) FROM issues WHERE status <> "resolved"');
        $late = (int) Database::value(
            'SELECT COUNT(*) FROM invoices WHERE status IN ("draft","sent") AND due_on < ?',
            [$today]
        );
        $followUps = Database::all(
            'SELECT id, name, stage, follow_up_on FROM properties
             WHERE follow_up_on IS NOT NULL AND follow_up_on <= ? AND stage NOT IN ("live","lost")
             ORDER BY follow_up_on ASC LIMIT 8',
            [$today]
        );
        $newInquiries = (int) Database::value('SELECT COUNT(*) FROM inquiries WHERE status = "new"');
        $pendingReviews = (int) Database::value('SELECT COUNT(*) FROM reviews WHERE status = "pending"');
        return View::page('admin/dashboard', [
            'title' => 'Dashboard',
            'description' => 'Operations dashboard',
            'activeProperties' => $properties,
            'activeDoors' => $doors,
            'routes' => $routes,
            'revenueCents' => $revenue,
            'collectedCents' => $collected,
            'profitCents' => $profit,
            'invoiceCount' => count($invoices),
            'openIssues' => $openIssues,
            'lateInvoices' => $late,
            'followUps' => $followUps,
            'newInquiries' => $newInquiries,
            'pendingReviews' => $pendingReviews,
            'monthLabel' => date('F Y'),
        ], 'layouts/admin');
    }
}
