<?php
/** @var array<string,mixed> $application */
$selectedStatus = old('status');
if ($selectedStatus === '') {
    $selectedStatus = (string) $application['status'];
}
$postedNotes = $GLOBALS['_old']['notes'] ?? null;
$notesValue = is_scalar($postedNotes)
    ? (string) $postedNotes
    : (string) ($application['notes'] ?? '');
$tel = preg_replace('/[^\d+]/', '', (string) $application['phone']);
?>
<p><a class="btn btn-ghost btn-small" href="/admin/applications">All applications</a></p>
<p><span class="badge"><?= e(job_status_label((string) $application['status'])) ?></span> <?= e(job_role_label((string) $application['role'])) ?></p>
<h1><?= e((string) $application['full_name']) ?></h1>
<p><a href="tel:<?= e((string) $tel) ?>"><?= e((string) $application['phone']) ?></a></p>
<p><?= filled($application['email'] ?? null) ? e((string) $application['email']) : 'No email given.' ?></p>
<p><?= e((string) $application['city']) ?></p>
<p>18 or older: <?= e(yes_no_label($application['is_adult'])) ?></p>
<p>Reliable transportation: <?= e(yes_no_label($application['has_transportation'])) ?></p>
<p>Evenings: <?= e(job_days_label((string) $application['days_available'])) ?></p>
<p>Can lift about 40 lb: <?= e(yes_no_label($application['can_lift'])) ?></p>
<?php if (filled($application['experience'] ?? null)): ?>
  <p><?= nl((string) $application['experience']) ?></p>
<?php else: ?>
  <p>No prior experience given.</p>
<?php endif; ?>
<p>Can start <?= e((string) $application['start_date']) ?></p>
<p>Heard about us: <?= e((string) $application['heard_about']) ?></p>
<p>Agreed to be contacted: <?= e(yes_no_label($application['consent'])) ?></p>
<p class="muted">Submitted <?= e((string) $application['created_at']) ?></p>
<form class="form card" method="post" action="/admin/applications/<?= (int) $application['id'] ?>">
  <?= csrf_field() ?>
  <fieldset class="field"<?= has_error('status') ? ' aria-invalid="true" aria-describedby="err-status"' : '' ?>>
    <legend>Status</legend>
    <?php foreach (job_statuses() as $value): ?>
      <label for="status-<?= e($value) ?>"><input id="status-<?= e($value) ?>" type="radio" name="status" value="<?= e($value) ?>"<?= $selectedStatus === $value ? ' checked' : '' ?>> <span><?= e(job_status_label($value)) ?></span></label>
    <?php endforeach; ?>
    <?= field_error('status') ?>
  </fieldset>
  <div class="field">
    <label for="notes">Notes</label>
    <textarea id="notes" name="notes" maxlength="4000" <?= has_error('notes') ? 'aria-invalid="true" aria-describedby="err-notes"' : '' ?>><?= e($notesValue) ?></textarea>
    <?= field_error('notes') ?>
  </div>
  <button class="btn btn-primary" type="submit">Save</button>
</form>
