<h1>Tonight and this month</h1>
<p class="muted"><?= e((string) $monthLabel) ?>. Counts come from live records. Zeros mean the work has not been entered yet.</p>
<div class="stat-grid">
  <div class="stat"><b><?= (int) $activeProperties ?></b><span>Live properties</span></div>
  <div class="stat"><b><?= (int) $activeDoors ?></b><span>Active doors</span></div>
  <div class="stat"><b><?= count($routes) ?></b><span>Routes tonight</span></div>
  <div class="stat"><b><?= (int) $openIssues ?></b><span>Open issues</span></div>
</div>
<div class="stat-grid">
  <div class="stat"><b><?= e(money((int) $revenueCents)) ?></b><span>Invoiced this month</span></div>
  <div class="stat"><b><?= e(money((int) $collectedCents)) ?></b><span>Marked paid</span></div>
  <div class="stat"><b><?= e(money((int) $profitCents)) ?></b><span>Profit on those invoices</span></div>
  <div class="stat"><b><?= (int) $lateInvoices ?></b><span>Late invoices</span></div>
</div>
<?php if ((int) $invoiceCount === 0): ?>
  <p class="muted">No invoices for this month yet, so revenue and profit are zero. <a href="/admin/billing">Generate invoices</a> after doors are on the roster.</p>
<?php endif; ?>
<div class="grid-2">
  <section class="card">
    <h2>Tonight</h2>
    <?php if ($routes === []): ?>
      <p>No routes on the board for tonight.</p>
    <?php else: ?>
      <ul class="rule-list">
        <?php foreach ($routes as $route): ?>
          <li><a href="/admin/tonight?run=<?= (int) $route['id'] ?>"><?= e((string) ($route['porter_name'] ?: 'Unassigned')) ?></a> · <?= (int) $route['property_count'] ?> properties · <?= e((string) $route['status']) ?></li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </section>
  <section class="card">
    <h2>Needs a look</h2>
    <p><a href="/admin/inquiries"><?= (int) $newInquiries ?> new proposal requests</a></p>
    <p><a href="/admin/reviews"><?= (int) $pendingReviews ?> reviews waiting</a></p>
    <?php if ($followUps === []): ?>
      <p class="muted">No follow-ups due.</p>
    <?php else: ?>
      <ul class="rule-list">
        <?php foreach ($followUps as $row): ?>
          <li><a href="/admin/properties/<?= (int) $row['id'] ?>"><?= e((string) $row['name']) ?></a> · <?= e((string) $row['follow_up_on']) ?></li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </section>
</div>
