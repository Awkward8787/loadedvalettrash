<h1>Inquiries <span class="badge"><?= (int) $newCount ?> new</span></h1>
<p class="muted">Messages and junk pickup estimates, newest first. Proposal requests stay in <a href="/admin/inquiries">Inbox</a>. <a href="/admin/junk-rates">Junk pickup rates</a> are blank until you set them.</p>
<?php if ($rows === []): ?>
  <p class="card">No inquiries yet.</p>
<?php endif; ?>
<div class="stack">
  <?php foreach ($rows as $row): ?>
    <article class="card">
      <p><span class="badge"><?= e((string) $row['status']) ?></span> <?= e((string) $row['type']) ?></p>
      <h2><a href="<?= e((string) $row['href']) ?>"><?= e((string) $row['title']) ?></a></h2>
      <p><?= e((string) $row['meta']) ?></p>
      <p class="muted"><?= e((string) $row['created_at']) ?></p>
    </article>
  <?php endforeach; ?>
</div>
