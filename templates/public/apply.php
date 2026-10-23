<?php
/** @var string $intro */
/** @var string $setout */
/** @var string $pickup */
$nights = old_list('nights');
$errors = $GLOBALS['_errors'] ?? [];
?>
<section class="section apply-page">
  <div class="wrap">
    <p class="eyebrow">Hiring</p>
    <h1>Apply to work as a porter</h1>
    <p class="lede"><?= e($intro) ?></p>
    <form class="form card" method="post" action="/apply" novalidate>
      <?= csrf_field() ?>
      <p class="hp"><label>Company website <input type="text" name="company_website" tabindex="-1" autocomplete="off"></label></p>
      <?php if (has_error('form')): ?>
        <p class="banner banner-error" role="alert"><?= e((string) $errors['form']) ?></p>
      <?php elseif ($errors !== []): ?>
        <p class="banner banner-error" role="alert">The application was not sent. Fix the fields below, then send it again.</p>
      <?php endif; ?>
      <div class="field">
        <label for="full_name">Full name</label>
        <input id="full_name" name="full_name" required maxlength="120" autocomplete="name" value="<?= old('full_name') ?>" <?= has_error('full_name') ? 'aria-invalid="true" aria-describedby="err-full_name"' : '' ?>>
        <?= field_error('full_name') ?>
      </div>
      <div class="field">
        <label for="phone">Phone</label>
        <input id="phone" name="phone" type="tel" required maxlength="40" autocomplete="tel" value="<?= old('phone') ?>" <?= has_error('phone') ? 'aria-invalid="true" aria-describedby="err-phone"' : '' ?>>
        <?= field_error('phone') ?>
      </div>
      <div class="field">
        <label for="email">Email</label>
        <input id="email" name="email" type="email" required maxlength="190" autocomplete="email" value="<?= old('email') ?>" <?= has_error('email') ? 'aria-invalid="true" aria-describedby="err-email"' : '' ?>>
        <?= field_error('email') ?>
      </div>
      <fieldset class="field" <?= has_error('nights') ? 'aria-invalid="true" aria-describedby="nights-hint err-nights"' : 'aria-describedby="nights-hint"' ?>>
        <legend>Which weeknights can you work?</legend>
        <p class="field-hint" id="nights-hint">Monday through Friday evenings. Pickup runs <?= e($pickup) ?>. Set-out runs <?= e($setout) ?>.</p>
        <?php foreach (weeknights() as $value => $label): ?>
          <label class="choice">
            <input type="checkbox" name="nights[]" value="<?= e($value) ?>" <?= in_array($value, $nights, true) ? 'checked' : '' ?>>
            <span><?= e($label) ?></span>
          </label>
        <?php endforeach; ?>
        <?= field_error('nights') ?>
      </fieldset>
      <fieldset class="field" <?= has_error('has_transportation') ? 'aria-invalid="true" aria-describedby="err-has_transportation"' : '' ?>>
        <legend>Do you have reliable transportation?</legend>
        <label class="choice">
          <input type="radio" name="has_transportation" value="yes" <?= old_is('has_transportation', 'yes') ? 'checked' : '' ?>>
          <span>Yes</span>
        </label>
        <label class="choice">
          <input type="radio" name="has_transportation" value="no" <?= old_is('has_transportation', 'no') ? 'checked' : '' ?>>
          <span>No</span>
        </label>
        <?= field_error('has_transportation') ?>
      </fieldset>
      <fieldset class="field" <?= has_error('is_adult') ? 'aria-invalid="true" aria-describedby="err-is_adult"' : '' ?>>
        <legend>Are you 18 or older?</legend>
        <label class="choice">
          <input type="radio" name="is_adult" value="yes" <?= old_is('is_adult', 'yes') ? 'checked' : '' ?>>
          <span>Yes</span>
        </label>
        <label class="choice">
          <input type="radio" name="is_adult" value="no" <?= old_is('is_adult', 'no') ? 'checked' : '' ?>>
          <span>No</span>
        </label>
        <?= field_error('is_adult') ?>
      </fieldset>
      <div class="field">
        <label for="experience">Past work experience <span class="field-optional">(optional)</span></label>
        <textarea id="experience" name="experience" maxlength="4000" <?= has_error('experience') ? 'aria-invalid="true" aria-describedby="err-experience"' : '' ?>><?= old('experience') ?></textarea>
        <?= field_error('experience') ?>
      </div>
      <div class="field">
        <label for="notes">Anything else <span class="field-optional">(optional)</span></label>
        <textarea id="notes" name="notes" maxlength="4000" <?= has_error('notes') ? 'aria-invalid="true" aria-describedby="err-notes"' : '' ?>><?= old('notes') ?></textarea>
        <?= field_error('notes') ?>
      </div>
      <button class="btn btn-primary" type="submit" data-busy-label="Sending application">Send application</button>
    </form>
    <?php if (filled($settings['porter_job_details'] ?? null)): ?>
      <section class="card job-details" aria-labelledby="job-details-title">
        <h2 id="job-details-title">Job details</h2>
        <div class="job-details-body"><?= nl((string) $settings['porter_job_details']) ?></div>
      </section>
    <?php endif; ?>
  </div>
</section>
