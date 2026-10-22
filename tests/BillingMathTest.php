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
        $quote = Calculator::optIn(60, 3500, 10.0);
        $this->assertSame(210000, $quote['gross_cents']);
        $this->assertSame(21000, $quote['share_cents']);
        $this->assertSame(189000, $quote['due_cents']);
    }

    public function testLaborAndProfitUsePayrollTaxSuppliesGasReserveAndFixedCosts(): void
    {
        $hours = round(1.75 * 21.67, 4);
        $labor = Calculator::laborCents($hours, 1500, 1.10);
        $this->assertSame(62572, $labor);
        $profit = Calculator::profitCents(189000, $labor, 4000, 9000, 2500, 34900);
        $this->assertSame(76028, $profit);
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
        $this->assertSame(189000, $quote['due_cents']);

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
        $this->assertSame(189000 - 3300 - 4000 - 9000 - 2500 - 34900, $profit['profit_cents']);
    }
}
