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
use Lvt\Session;
use Lvt\View;

final class BillingController extends Controller
{
    public function index(): Response
    {
        $year = Request::current()->int('year', (int) date('Y'));
        $month = Request::current()->int('month', (int) date('n'));
        $invoices = Database::all(
            'SELECT i.*, p.name AS property_name FROM invoices i
             INNER JOIN properties p ON p.id = i.property_id
             WHERE i.period_year = ? AND i.period_month = ?
             ORDER BY p.name',
            [$year, $month]
        );
        $rows = [];
        $due = 0;
        $profit = 0;
        foreach ($invoices as $invoice) {
            $math = $invoice['status'] === 'void' ? null : Ledger::profitForInvoice($invoice);
            $rows[] = ['invoice' => $invoice, 'profit' => $math];
            if ($invoice['status'] !== 'void') {
                $due += (int) $invoice['due_cents'];
                $profit += $math['profit_cents'] ?? 0;
            }
        }
        return View::page('admin/billing', [
            'title' => 'Billing',
            'description' => 'Monthly invoices',
            'rows' => $rows,
            'year' => $year,
            'month' => $month,
            'dueCents' => $due,
            'profitCents' => $profit,
            'properties' => Database::all('SELECT id, name, stage FROM properties WHERE stage IN ("signed","live") ORDER BY name'),
        ], 'layouts/admin');
    }

    public function generate(): Response
    {
        $this->csrf();
        $req = Request::current();
        $year = $req->int('year', (int) date('Y'));
        $month = $req->int('month', (int) date('n'));
        if ($month < 1 || $month > 12 || $year < 2024 || $year > 2100) {
            Session::flash('error', 'Pick a real month.');
            return Response::redirect('/admin/billing');
        }
        $one = $req->int('property_id', 0);
        if ($one > 0) {
            $properties = Database::all('SELECT * FROM properties WHERE id = ?', [$one]);
        } else {
            $properties = Database::all('SELECT * FROM properties WHERE stage IN ("signed","live") ORDER BY name');
        }
        $made = 0;
        $skipped = 0;
        foreach ($properties as $property) {
            $existing = Database::one(
                'SELECT * FROM invoices WHERE property_id = ? AND period_year = ? AND period_month = ?',
                [(int) $property['id'], $year, $month]
            );
            if ($existing && in_array($existing['status'], ['paid', 'void'], true)) {
                $skipped++;
                continue;
            }
            $quote = Ledger::quote($property, $year, $month);
            $payload = [
                'pricing_model' => (string) $property['pricing_model'],
                'door_count' => $quote['door_count'],
                'rate_cents' => $quote['rate_cents'],
                'gross_cents' => $quote['gross_cents'],
                'share_percent' => number_format($quote['share_percent'], 2, '.', ''),
                'share_cents' => $quote['share_cents'],
                'due_cents' => $quote['due_cents'],
                'due_on' => Ledger::dueDate($year, $month),
            ];
            if ($existing) {
                Database::updateRow('invoices', $payload, 'id = ?', [(int) $existing['id']]);
            } else {
                $payload['property_id'] = (int) $property['id'];
                $payload['period_year'] = $year;
                $payload['period_month'] = $month;
                $payload['status'] = 'draft';
                $payload['paid_at'] = null;
                $payload['notes'] = null;
                $payload['created_at'] = now_dt();
                Database::insertRow('invoices', $payload);
            }
            $made++;
        }
        Audit::write((int) (Auth::user()['id'] ?? 0), 'invoice.generate', 'invoice', null, 'Generated ' . $made . ' invoices for ' . $year . '-' . $month);
        Session::flash('success', 'Updated ' . $made . ' invoices. Left ' . $skipped . ' paid or void invoices alone.');
        return Response::redirect('/admin/billing?year=' . $year . '&month=' . $month);
    }

    public function show(array $params): Response
    {
        $invoice = $this->invoice((int) $params['id']);
        $profit = $invoice['status'] === 'void' ? null : Ledger::profitForInvoice($invoice);
        return View::page('admin/invoice', [
            'title' => 'Invoice #' . $invoice['id'],
            'description' => 'Property invoice',
            'invoice' => $invoice,
            'profit' => $profit,
            'late' => Ledger::isLate($invoice),
        ], 'layouts/admin');
    }

    public function update(array $params): Response
    {
        $this->csrf();
        $invoice = $this->invoice((int) $params['id']);
        $status = Request::current()->string('status');
        if (!in_array($status, ['draft', 'sent', 'paid', 'void'], true)) {
            throw new HttpException(422, 'Unknown invoice status.');
        }
        Database::updateRow('invoices', [
            'status' => $status,
            'paid_at' => $status === 'paid' ? now_dt() : null,
            'notes' => blank_to_null(Request::current()->string('notes')),
        ], 'id = ?', [(int) $invoice['id']]);
        Audit::write((int) (Auth::user()['id'] ?? 0), 'invoice.update', 'invoice', (int) $invoice['id'], 'Marked ' . $status);
        Session::flash('success', 'Invoice marked ' . $status . '.');
        return Response::redirect('/admin/billing/' . $invoice['id']);
    }

    private function invoice(int $id): array
    {
        $row = Database::one(
            'SELECT i.*, p.name AS property_name FROM invoices i INNER JOIN properties p ON p.id = i.property_id WHERE i.id = ?',
            [$id]
        );
        if (!$row) {
            throw new HttpException(404, 'That invoice does not exist.');
        }
        return $row;
    }
}
