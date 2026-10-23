<?php
/** @var array<string,mixed> $application */
$statuses = application_statuses();
$selected = old('status', '');
if ($selected === '') {
    $selected = (string) $application['status'];
}
?>
<p><a href="/admin/applications">All applications</a></p>
<h1><?= e((string) $application['full_name']) ?></h1>
<p><span class="badge"><?= e(application_status_label((string) $application['status'])) ?></span> Received <?= e(format_stamp((string) $application['created_at'])) ?></p>
<dl class="detail-list card">
  <div>
    <dt>Phone</dt>
    <dd><?= e((string) $application['phone']) ?></dd>
  </div>
  <div>
    <dt>Email</dt>
    <dd><?= e((string) $application['email']) ?></dd>
  </div>
  <div>
    <dt>Weeknights</dt>
    <dd><?= e(format_weeknights((string) $application['nights'])) ?></dd>
  </div>
  <div>
    <dt>Reliable transportation</dt>
    <dd><?= e(yes_no_label($application['has_transportation'])) ?></dd>
  </div>
  <div>
    <dt>18 or older</dt>
    <dd><?= e(yes_no_label($application['is_adult'])) ?></dd>
  </div>
  <div>
    <dt>Past work experience</dt>
    <dd><?= filled($application['experience'] ?? null) ? nl((string) $application['experience']) : 'None given.' ?></dd>
  </div>
  <div>
    <dt>Anything else</dt>
    <dd><?= filled($application['notes'] ?? null) ? nl((string) $application['notes']) : 'None given.' ?></dd>
  </div>
</dl>
<form class="form card" method="post" action="/admin/applications/<?= (int) $application['id'] ?>">
  <?= csrf_field() ?>
  <fieldset class="field" <?= has_error('status') ? 'aria-invalid="true" aria-describedby="err-status"' : '' ?>>
    <legend>Status</legend>
    <?php foreach ($statuses as $value => $label): ?>
      <label class="choice">
        <input type="radio" name="status" value="<?= e($value) ?>" <?= $selected === $value ? 'checked' : '' ?>>
        <span><?= e($label) ?></span>
      </label>
    <?php endforeach; ?>
    <?= field_error('status') ?>
  </fieldset>
  <button class="btn btn-primary" type="submit" data-busy-label="Saving status">Save status</button>
</form>
