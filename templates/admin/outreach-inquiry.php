<?php
/** @var array<string,mixed> $inquiry */
$selectedStatus = old('status');
if ($selectedStatus === '') {
    $selectedStatus = (string) $inquiry['status'];
}
$postedNotes = $GLOBALS['_old']['notes'] ?? null;
$notesValue = is_scalar($postedNotes) ? (string) $postedNotes : (string) ($inquiry['notes'] ?? '');
$tel = preg_replace('/[^\d+]/', '', (string) ($inquiry['phone'] ?? ''));
?>
<p><a class="btn btn-ghost btn-small" href="/admin/outreach">All inquiries</a></p>
<p><span class="badge"><?= e((string) $inquiry['status']) ?></span> <?= e(outreach_need_label((string) $inquiry['need'])) ?></p>
<h1><?= e((string) $inquiry['name']) ?></h1>
<p><?= e(outreach_role_label((string) $inquiry['role'])) ?></p>
<p><a href="mailto:<?= e((string) $inquiry['email']) ?>"><?= e((string) $inquiry['email']) ?></a></p>
<?php if (filled($inquiry['phone'] ?? null)): ?>
  <p><a href="tel:<?= e((string) $tel) ?>"><?= e((string) $inquiry['phone']) ?></a></p>
<?php else: ?>
  <p>No phone given.</p>
<?php endif; ?>
<p><?= filled($inquiry['organization'] ?? null) ? e((string) $inquiry['organization']) : 'No property or company given.' ?></p>
<p><?= e(service_area_label((string) $inquiry['service_area'])) ?></p>
<p><?= nl((string) $inquiry['message']) ?></p>
<p class="muted">Submitted <?= e((string) $inquiry['created_at']) ?></p>
<form class="form card" method="post" action="/admin/outreach/<?= (int) $inquiry['id'] ?>">
  <?= csrf_field() ?>
  <fieldset class="field"<?= has_error('status') ? ' aria-invalid="true" aria-describedby="err-status"' : '' ?>>
    <legend>Status</legend>
    <?php foreach (['new', 'contacted', 'closed'] as $value): ?>
      <label for="status-<?= e($value) ?>"><input id="status-<?= e($value) ?>" type="radio" name="status" value="<?= e($value) ?>"<?= $selectedStatus === $value ? ' checked' : '' ?>> <span><?= e(ucfirst($value)) ?></span></label>
    <?php endforeach; ?>
    <?= field_error('status') ?>
  </fieldset>
  <div class="field">
    <label for="notes">Internal notes</label>
    <textarea id="notes" name="notes" maxlength="4000" <?= has_error('notes') ? 'aria-invalid="true" aria-describedby="err-notes"' : '' ?>><?= e($notesValue) ?></textarea>
    <?= field_error('notes') ?>
  </div>
  <button class="btn btn-primary" type="submit">Save</button>
</form>
