<h1>Applications</h1>
<p>Porter applications sent from the public form.</p>
<?php if ($applications === []): ?>
  <p class="card">No applications yet.</p>
<?php endif; ?>
<div class="stack">
  <?php foreach ($applications as $application): ?>
    <article class="card">
      <p><span class="badge"><?= e(application_status_label((string) $application['status'])) ?></span></p>
      <h2><a href="/admin/applications/<?= (int) $application['id'] ?>"><?= e((string) $application['full_name']) ?></a></h2>
      <p><?= e((string) $application['phone']) ?></p>
      <p><?= e((string) $application['email']) ?></p>
      <p>Weeknights: <?= e(format_weeknights((string) $application['nights'])) ?></p>
      <p>Received <?= e(format_stamp((string) $application['created_at'])) ?></p>
    </article>
  <?php endforeach; ?>
</div>
