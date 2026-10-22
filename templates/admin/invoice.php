<h1><?= e((string) $invoice['property_name']) ?></h1>
<p><?= e(sprintf('%04d-%02d', (int) $invoice['period_year'], (int) $invoice['period_month'])) ?> · due <?= e((string) $invoice['due_on']) ?></p>
<p>
  <span class="badge"><?= e((string) $invoice['status']) ?></span>
  <?php if (!empty($late)): ?><span class="badge badge-late">Late</span><?php endif; ?>
</p>
<div class="card">
  <p>Model: <?= e((string) $invoice['pricing_model']) ?></p>
  <p>Doors: <?= (int) $invoice['door_count'] ?> at <?= e(money((int) $invoice['rate_cents'])) ?></p>
  <p>Gross: <?= e(money((int) $invoice['gross_cents'])) ?></p>
  <p>Property share (<?= e((string) $invoice['share_percent']) ?>%): <?= e(money((int) $invoice['share_cents'])) ?></p>
  <p><strong>Amount due: <?= e(money((int) $invoice['due_cents'])) ?></strong></p>
</div>
<?php if (!empty($profit)): ?>
  <div class="card">
    <h2>Profit</h2>
    <p class="muted">Labor is porter hours on routes that include this property, times each person’s wage, times the payroll multiplier in settings (<?= e((string) $settings['payroll_multiplier']) ?>). Supplies, gas, and the vehicle reserve are per site. Fixed costs are split across the <?= (int) $profit['sites'] ?> non-void invoices this month. Changing settings changes this math.</p>
    <p>Hours: <?= e(number_format((float) $profit['hours'], 2)) ?></p>
    <p>Labor: <?= e(money((int) $profit['labor_cents'])) ?></p>
    <p>Supplies: <?= e(money((int) $profit['supplies_cents'])) ?></p>
    <p>Gas: <?= e(money((int) $profit['gas_cents'])) ?></p>
    <p>Vehicle reserve: <?= e(money((int) $profit['reserve_cents'])) ?></p>
    <p>Share of fixed costs: <?= e(money((int) $profit['fixed_share_cents'])) ?></p>
    <p><strong>Profit: <?= e(money((int) $profit['profit_cents'])) ?></strong></p>
  </div>
<?php endif; ?>
<form class="form card" method="post" action="/admin/billing/<?= (int) $invoice['id'] ?>">
  <?= csrf_field() ?>
  <label>Status
    <select name="status">
      <?php foreach (['draft','sent','paid','void'] as $status): ?>
        <option value="<?= e($status) ?>"<?= $invoice['status'] === $status ? ' selected' : '' ?>><?= e($status) ?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <input name="notes" value="<?= e((string) ($invoice['notes'] ?? '')) ?>" placeholder="Note">
  <button class="btn btn-primary" type="submit">Update invoice</button>
</form>
