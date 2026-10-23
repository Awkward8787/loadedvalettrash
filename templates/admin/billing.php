<h1>Billing</h1>
<p class="muted"><?= e(sprintf('%04d-%02d', (int) $year, (int) $month)) ?> · due <?= e(money((int) $dueCents)) ?> · profit <?= e(money((int) $profitCents)) ?></p>
<form class="form" method="get" action="/admin/billing">
  <div class="grid-2">
    <div class="field">
      <label for="billing-year">Year</label>
      <input id="billing-year" name="year" type="number" value="<?= (int) $year ?>">
    </div>
    <div class="field">
      <label for="billing-month">Month</label>
      <input id="billing-month" name="month" type="number" min="1" max="12" value="<?= (int) $month ?>">
    </div>
  </div>
  <button class="btn btn-ghost" type="submit">Show month</button>
</form>
<form class="form card" method="post" action="/admin/billing/generate">
  <?= csrf_field() ?>
  <h2>Generate</h2>
  <p class="muted">Signed and live properties. Opt-in uses billable doors times the price, minus the property share. Per-door and flat use the property’s rate. Paid invoices are left alone.</p>
  <input type="hidden" name="year" value="<?= (int) $year ?>">
  <input type="hidden" name="month" value="<?= (int) $month ?>">
  <label for="billing-property">Property</label>
  <select id="billing-property" name="property_id">
    <option value="0">All signed and live</option>
    <?php foreach ($properties as $property): ?>
      <option value="<?= (int) $property['id'] ?>"><?= e((string) $property['name']) ?></option>
    <?php endforeach; ?>
  </select>
  <button class="btn btn-primary" type="submit">Generate invoices</button>
</form>
<div class="stack">
  <?php if ($rows === []): ?><p class="card">No invoices for this month.</p><?php endif; ?>
  <?php foreach ($rows as $row): ?>
    <?php $invoice = $row['invoice']; $profit = $row['profit']; ?>
    <article class="card">
      <h2><a href="/admin/billing/<?= (int) $invoice['id'] ?>"><?= e((string) $invoice['property_name']) ?></a></h2>
      <p>
        <span class="badge"><?= e((string) $invoice['status']) ?></span>
        <?php if (\Lvt\Billing\Ledger::isLate($invoice)): ?><span class="badge badge-late">Late</span><?php endif; ?>
      </p>
      <p><?= (int) $invoice['door_count'] ?> doors · due <?= e(money((int) $invoice['due_cents'])) ?><?php if ($profit): ?> · profit <?= e(money((int) $profit['profit_cents'])) ?><?php endif; ?></p>
    </article>
  <?php endforeach; ?>
</div>
