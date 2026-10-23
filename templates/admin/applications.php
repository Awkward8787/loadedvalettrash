<?php
/** @var list<array<string,mixed>> $applications */
/** @var string $filterRole */
/** @var string $filterStatus */
?>
<h1>Applications</h1>
<form class="form" method="get" action="/admin/applications">
  <div class="grid-2">
    <div class="field">
      <label for="filter-role">Role</label>
      <select id="filter-role" name="role">
        <option value="all"<?= $filterRole === 'all' ? ' selected' : '' ?>>All roles</option>
        <?php foreach (job_roles() as $value => $label): ?>
          <option value="<?= e($value) ?>"<?= $filterRole === $value ? ' selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field">
      <label for="filter-status">Status</label>
      <select id="filter-status" name="status">
        <option value="all"<?= $filterStatus === 'all' ? ' selected' : '' ?>>All statuses</option>
        <?php foreach (job_statuses() as $value): ?>
          <option value="<?= e($value) ?>"<?= $filterStatus === $value ? ' selected' : '' ?>><?= e(job_status_label($value)) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>
  <button class="btn btn-ghost btn-small" type="submit">Filter</button>
</form>
<?php if ($applications === []): ?>
  <p class="card">No applications in this list.</p>
<?php endif; ?>
<div class="stack">
  <?php foreach ($applications as $application): ?>
    <article class="card">
      <p><span class="badge"><?= e(job_status_label((string) $application['status'])) ?></span> <?= e(job_role_label((string) $application['role'])) ?></p>
      <h2><a href="/admin/applications/<?= (int) $application['id'] ?>"><?= e((string) $application['full_name']) ?></a></h2>
      <p><?= e((string) $application['city']) ?> · <?= e((string) $application['phone']) ?></p>
      <p class="muted"><?= e((string) $application['created_at']) ?></p>
    </article>
  <?php endforeach; ?>
</div>
