<?php

declare(strict_types=1);

namespace Lvt\Tests;

use Lvt\Billing\Calculator;
use Lvt\Billing\Ledger;
use Lvt\Database;
use Lvt\Routing\Builder;

final class BillingMathTest extends TestCase
{
    public function testOptInInvoiceMatchesThePlanningExample(): void
    {
        $quote = Calculator::optIn(60, 2000, 10.0);
        $this->assertSame(120000, $quote['gross_cents']);
        $this->assertSame(12000, $quote['share_cents']);
        $this->assertSame(108000, $quote['due_cents']);
    }

    public function testLaborAndProfitUsePayrollTaxSuppliesGasReserveAndFixedCosts(): void
    {
        $hours = round(1.75 * 21.67, 4);
        $labor = Calculator::laborCents($hours, 1500, 1.10);
        $this->assertSame(62572, $labor);
        $profit = Calculator::profitCents(108000, $labor, 4000, 9000, 2500, 34900);
        $this->assertSame(-4972, $profit);
    }

    public function testPerDoorAndFlatHaveNoShare(): void
    {
        $perDoor = Calculator::perDoor(40, 1400);
        $this->assertSame(56000, $perDoor['due_cents']);
        $this->assertSame(0, $perDoor['share_cents']);
        $flat = Calculator::flat(90000);
        $this->assertSame(90000, $flat['due_cents']);
        $this->assertSame(0, $flat['share_cents']);
    }

    public function testHoursSplitAcrossSitesByEstimatedMinutes(): void
    {
        $split = Calculator::splitHours(3.0, [10 => 105.0, 11 => 145.0]);
        $this->assertEqualsWithDelta(3.0, $split[10] + $split[11], 0.0001);
        $this->assertEqualsWithDelta(1.26, $split[10], 0.001);
    }

    public function testLedgerQuotesBillableDoorsAndFlagsALateDraft(): void
    {
        $propertyId = $this->insertProperty('Ledger Court', ['units_count' => 60]);
        $now = now_dt();
        for ($i = 1; $i <= 60; $i++) {
            Database::insertRow('residents', [
                'property_id' => $propertyId,
                'building_id' => null,
                'unit_number' => (string) $i,
                'name' => 'Resident ' . $i,
                'start_date' => '2026-10-01',
                'cancel_date' => null,
                'status' => 'active',
                'notes' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
        Database::insertRow('residents', [
            'property_id' => $propertyId,
            'building_id' => null,
            'unit_number' => 'left',
            'name' => 'Left last month',
            'start_date' => '2026-01-01',
            'cancel_date' => '2026-09-30',
            'status' => 'cancelled',
            'notes' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $property = Database::one('SELECT * FROM properties WHERE id = ?', [$propertyId]);
        $quote = Ledger::quote($property, 2026, 10);
        $this->assertSame(60, $quote['door_count']);
        $this->assertSame(2000, $quote['rate_cents']);
        $this->assertSame(108000, $quote['due_cents']);

        $invoiceId = Database::insertRow('invoices', [
            'property_id' => $propertyId,
            'period_year' => 2026,
            'period_month' => 10,
            'pricing_model' => 'opt_in',
            'door_count' => $quote['door_count'],
            'rate_cents' => $quote['rate_cents'],
            'gross_cents' => $quote['gross_cents'],
            'share_percent' => $quote['share_percent'],
            'share_cents' => $quote['share_cents'],
            'due_cents' => $quote['due_cents'],
            'status' => 'draft',
            'due_on' => Ledger::dueDate(2026, 10),
            'paid_at' => null,
            'notes' => null,
            'created_at' => $now,
        ]);
        $invoice = Database::one('SELECT * FROM invoices WHERE id = ?', [$invoiceId]);
        $this->assertSame('2026-10-10', $invoice['due_on']);
        $this->assertTrue(Ledger::isLate($invoice));

        $porter = $this->makeStaff('porter', 'ledger-porter@example.test');
        $runId = Builder::createRun('2026-10-02', $porter['id'], '20:00:00', [$propertyId]);
        Database::insertRow('time_entries', [
            'user_id' => $porter['id'],
            'route_run_id' => $runId,
            'clock_in' => '2026-10-02 20:00:00',
            'clock_out' => '2026-10-02 22:00:00',
            'notes' => null,
        ]);
        $profit = Ledger::profitForInvoice($invoice);
        $this->assertSame(2.0, $profit['hours']);
        $this->assertSame(3300, $profit['labor_cents']);
        $this->assertSame(108000 - 3300 - 4000 - 9000 - 2500 - 34900, $profit['profit_cents']);
    }

    public function testDefaultPriceChangeLeavesInvoicesAndPropertyRatesAlone(): void
    {
        $sql = strtolower((string) file_get_contents(dirname(__DIR__) . '/migrations/006_resident_price.sql'));
        $this->assertDoesNotMatchRegularExpression('/\b(update|from|into|table|join)\s+invoices\b/', $sql);
        $this->assertDoesNotMatchRegularExpression('/\b(update|from|into|table|join)\s+properties\b/', $sql);
        $propertyId = $this->insertProperty('Kept Rate', ['opt_in_price_cents' => 3500]);
        $now = now_dt();
        $invoiceId = Database::insertRow('invoices', [
            'property_id' => $propertyId,
            'period_year' => 2026,
            'period_month' => 9,
            'pricing_model' => 'opt_in',
            'door_count' => 1,
            'rate_cents' => 3500,
            'gross_cents' => 3500,
            'share_percent' => 10,
            'share_cents' => 350,
            'due_cents' => 3150,
            'status' => 'paid',
            'due_on' => '2026-09-10',
            'paid_at' => $now,
            'notes' => null,
            'created_at' => $now,
        ]);
        Database::exec('UPDATE settings SET opt_in_price_cents = 3500 WHERE id = 1');
        Database::exec('UPDATE settings SET opt_in_price_cents = 2000 WHERE id = 1 AND opt_in_price_cents = 3500');
        $this->assertSame(2000, (int) Database::value('SELECT opt_in_price_cents FROM settings WHERE id = 1'));
        $this->assertSame(3500, (int) Database::value('SELECT opt_in_price_cents FROM properties WHERE id = ?', [$propertyId]));
        $this->assertSame(3500, (int) Database::value('SELECT rate_cents FROM invoices WHERE id = ?', [$invoiceId]));
        Database::exec('UPDATE settings SET opt_in_price_cents = 4000 WHERE id = 1');
        Database::exec('UPDATE settings SET opt_in_price_cents = 2000 WHERE id = 1 AND opt_in_price_cents = 3500');
        $this->assertSame(4000, (int) Database::value('SELECT opt_in_price_cents FROM settings WHERE id = 1'));
    }
}
