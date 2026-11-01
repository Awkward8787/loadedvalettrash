<?php
/** @var array<string,mixed> $estimate */
$selectedStatus = old('status');
if ($selectedStatus === '') {
    $selectedStatus = (string) $estimate['status'];
}
$postedNotes = $GLOBALS['_old']['notes'] ?? null;
$notesValue = is_scalar($postedNotes) ? (string) $postedNotes : (string) ($estimate['notes'] ?? '');
$tel = preg_replace('/[^\d+]/', '', (string) ($estimate['phone'] ?? ''));
$pair = null;
if ($estimate['estimate_low_cents'] !== null && $estimate['estimate_high_cents'] !== null) {
    $pair = ['low' => (int) $estimate['estimate_low_cents'], 'high' => (int) $estimate['estimate_high_cents']];
}
$selection = \Lvt\Junk\Quote::selectionLabel(
    (string) $estimate['mode'],
    $estimate['items_json'] !== null ? (string) $estimate['items_json'] : null,
    $estimate['load_size'] !== null ? (string) $estimate['load_size'] : null
);
?>
<p><a class="btn btn-ghost btn-small" href="/admin/outreach">All inquiries</a></p>
<p><span class="badge"><?= e((string) $estimate['status']) ?></span> Junk pickup estimate</p>
<h1><?= e((string) $estimate['name']) ?></h1>
<p><?= e($selection) ?></p>
<?php if ($pair === null): ?>
  <p><?= e(\Lvt\Junk\Quote::FOLLOW_UP) ?></p>
<?php else: ?>
  <p><?= e(\Lvt\Junk\Quote::ESTIMATE_LABEL) ?> <?= e(\Lvt\Junk\Quote::moneyRange($pair)) ?>.</p>
<?php endif; ?>
<p><?= e((string) $estimate['address']) ?></p>
<p><?= e(service_area_label((string) $estimate['service_area'])) ?></p>
<p>Preferred date <?= e((string) $estimate['preferred_on']) ?></p>
<p><a href="mailto:<?= e((string) $estimate['email']) ?>"><?= e((string) $estimate['email']) ?></a></p>
<?php if (filled($estimate['phone'] ?? null)): ?>
  <p><a href="tel:<?= e((string) $tel) ?>"><?= e((string) $estimate['phone']) ?></a></p>
<?php else: ?>
  <p>No phone given.</p>
<?php endif; ?>
<?php if (filled($estimate['note'] ?? null)): ?>
  <p><?= nl((string) $estimate['note']) ?></p>
<?php endif; ?>
<?php if (filled($estimate['photo_path'] ?? null)): ?>
  <p><a href="/admin/junk-estimates/<?= (int) $estimate['id'] ?>/photo">View photo</a></p>
<?php else: ?>
  <p>No photo attached.</p>
<?php endif; ?>
<p class="muted">Submitted <?= e((string) $estimate['created_at']) ?></p>
<form class="form card" method="post" action="/admin/junk-estimates/<?= (int) $estimate['id'] ?>">
  <?= csrf_field() ?>
  <fieldset class="field"<?= has_error('status') ? ' aria-invalid="true" aria-describedby="err-status"' : '' ?>>
    <legend>Status</legend>
    <?php foreach (['new', 'quoted', 'booked', 'closed'] as $value): ?>
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
