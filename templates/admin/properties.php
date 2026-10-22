<h1>Properties</h1>
<p class="muted">Signed and live communities. Leads stay on the <a href="/admin/pipeline">pipeline</a>.</p>
<?php if ($rows === []): ?>
  <p class="card">No signed or live properties yet.</p>
<?php endif; ?>
<div class="stack">
  <?php foreach ($rows as $row): ?>
    <article class="card">
      <span class="badge <?= $row['stage'] === 'live' ? 'badge-live' : '' ?>"><?= e((string) $row['stage']) ?></span>
      <h2><a href="/admin/properties/<?= (int) $row['id'] ?>"><?= e((string) $row['name']) ?></a></h2>
      <p><?= (int) $row['units_count'] ?> units · <?= (int) $row['active_residents'] ?> active roster rows · <?= e((string) $row['pricing_model']) ?></p>
      <p><a href="/admin/properties/<?= (int) $row['id'] ?>/residents">Roster</a></p>
    </article>
  <?php endforeach; ?>
</div>
