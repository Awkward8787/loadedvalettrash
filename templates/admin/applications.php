<?php
/** @var list<array<string,mixed>> $applications */
/** @var string $filterRole */
/** @var string $filterStatus */
?>
<h1>Applications</h1>
<form class="form" method="get" action="/admin/applications">
  <div class="grid-2">
    <fieldset class="field" id="filter-role">
      <legend>Role</legend>
      <label for="filter-role-all"><input id="filter-role-all" type="radio" name="role" value="all"<?= $filterRole === 'all' ? ' checked' : '' ?>> <span>All</span></label>
      <?php foreach (job_roles() as $value => $label): ?>
        <label for="filter-role-<?= e($value) ?>"><input id="filter-role-<?= e($value) ?>" type="radio" name="role" value="<?= e($value) ?>"<?= $filterRole === $value ? ' checked' : '' ?>> <span><?= e($label) ?></span></label>
      <?php endforeach; ?>
    </fieldset>
    <fieldset class="field" id="filter-status">
      <legend>Status</legend>
      <label for="filter-status-all"><input id="filter-status-all" type="radio" name="status" value="all"<?= $filterStatus === 'all' ? ' checked' : '' ?>> <span>All</span></label>
      <?php foreach (job_statuses() as $value): ?>
        <label for="filter-status-<?= e($value) ?>"><input id="filter-status-<?= e($value) ?>" type="radio" name="status" value="<?= e($value) ?>"<?= $filterStatus === $value ? ' checked' : '' ?>> <span><?= e(job_status_label($value)) ?></span></label>
      <?php endforeach; ?>
    </fieldset>
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
