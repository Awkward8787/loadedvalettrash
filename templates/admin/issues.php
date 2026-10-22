<h1>Issues</h1>
<p>
  <a href="/admin/issues?status=open">Open</a> ·
  <a href="/admin/issues?status=scheduled_return">Return scheduled</a> ·
  <a href="/admin/issues?status=resolved">Resolved</a> ·
  <a href="/admin/issues/report">Weekly report</a>
</p>
<?php if ($issues === []): ?><p class="card">Nothing in this list.</p><?php endif; ?>
<div class="stack">
  <?php foreach ($issues as $issue): ?>
    <article class="card">
      <p><span class="badge"><?= e((string) $issue['type']) ?></span> <span class="badge"><?= e((string) $issue['status']) ?></span></p>
      <h2><a href="/admin/issues/<?= (int) $issue['id'] ?>"><?= e((string) ($issue['property_name'] ?: $issue['external_property_name'] ?: 'Unlinked property')) ?> · <?= e((string) ($issue['unit_label'] ?: 'no unit')) ?></a></h2>
      <p class="muted"><?= e((string) $issue['created_at']) ?> · <?= e((string) $issue['source']) ?></p>
      <p><?= e(mb_substr((string) ($issue['description'] ?? ''), 0, 180)) ?></p>
    </article>
  <?php endforeach; ?>
</div>
