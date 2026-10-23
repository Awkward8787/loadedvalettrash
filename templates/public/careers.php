<?php
/** @var string $setout */
/** @var string $pickup */
$postedDays = $GLOBALS['_old']['days'] ?? [];
if (!is_array($postedDays)) {
    $postedDays = [];
}
$roles = job_roles();
$days = job_days();
?>
<section class="section">
  <div class="wrap grid-2">
    <div>
      <p class="eyebrow">Careers</p>
      <h1>Weeknight routes.</h1>
      <p class="lede">Loaded Valet Trash is hiring in Valdosta and Lowndes County. Routes run Monday through Friday evenings. The work is collecting bagged trash from the can outside each apartment door and taking it to the property compactor or dumpster. Porters do not go inside apartments.</p>
      <h2>Two roles</h2>
      <div class="stack">
        <article class="card">
          <h3><?= e($roles['laborer']) ?></h3>
          <p><strong>Starting at $18 an hour.</strong></p>
          <p>Collect bagged trash from the can outside each apartment door and take it to the property compactor or dumpster. Residents set cans out from <?= e($setout) ?>. Pickup runs <?= e($pickup) ?>, Monday through Friday.</p>
        </article>
        <article class="card">
          <h3>Supervisor</h3>
          <p><strong>Starting at $20 an hour.</strong></p>
          <p>Supervise those Monday through Friday evening routes in Valdosta and Lowndes County. Pickup is the same doorstep work: bagged trash from the can outside the door, then to the compactor or dumpster.</p>
        </article>
      </div>
      <p><a class="btn btn-ghost" href="tel:+12295958852">Call (229) 595-8852</a></p>
    </div>
    <div>
      <h2>Apply</h2>
      <p class="lede">Phone is required. Email is optional.</p>
      <form class="form card" method="post" action="/careers">
        <?= csrf_field() ?>
        <p class="hp"><label>Company website <input type="text" name="company_website" tabindex="-1" autocomplete="off"></label></p>
        <?= field_error('form') ?>
        <div class="field">
          <label for="role">Role</label>
          <select id="role" name="role" required <?= has_error('role') ? 'aria-invalid="true" aria-describedby="err-role"' : '' ?>>
            <option value="">Choose a role</option>
            <?php foreach ($roles as $value => $label): ?>
              <option value="<?= e($value) ?>"<?= old('role') === $value ? ' selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
          </select>
          <?= field_error('role') ?>
        </div>
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
          <label for="email">Email (optional)</label>
          <input id="email" name="email" type="email" maxlength="190" autocomplete="email" value="<?= old('email') ?>" <?= has_error('email') ? 'aria-invalid="true" aria-describedby="err-email"' : '' ?>>
          <?= field_error('email') ?>
        </div>
        <div class="field">
          <label for="city">City</label>
          <input id="city" name="city" required maxlength="120" autocomplete="address-level2" value="<?= old('city') ?>" <?= has_error('city') ? 'aria-invalid="true" aria-describedby="err-city"' : '' ?>>
          <?= field_error('city') ?>
        </div>
        <fieldset class="field"<?= has_error('is_18') ? ' aria-invalid="true" aria-describedby="err-is_18"' : '' ?>>
          <legend>Are you 18 or older?</legend>
          <label for="is_18_yes"><input id="is_18_yes" type="radio" name="is_18" value="yes" required <?= old('is_18') === 'yes' ? 'checked' : '' ?>> <span>Yes</span></label>
          <label for="is_18_no"><input id="is_18_no" type="radio" name="is_18" value="no" <?= old('is_18') === 'no' ? 'checked' : '' ?>> <span>No</span></label>
          <?= field_error('is_18') ?>
        </fieldset>
        <fieldset class="field"<?= has_error('has_transportation') ? ' aria-invalid="true" aria-describedby="err-has_transportation"' : '' ?>>
          <legend>Do you have reliable transportation?</legend>
          <label for="transport_yes"><input id="transport_yes" type="radio" name="has_transportation" value="yes" required <?= old('has_transportation') === 'yes' ? 'checked' : '' ?>> <span>Yes</span></label>
          <label for="transport_no"><input id="transport_no" type="radio" name="has_transportation" value="no" <?= old('has_transportation') === 'no' ? 'checked' : '' ?>> <span>No</span></label>
          <?= field_error('has_transportation') ?>
        </fieldset>
        <fieldset class="field"<?= has_error('days') ? ' aria-invalid="true" aria-describedby="err-days"' : '' ?>>
          <legend>Which evenings can you work?</legend>
          <?php foreach ($days as $value => $label): ?>
            <label for="day-<?= e($value) ?>"><input id="day-<?= e($value) ?>" type="checkbox" name="days[]" value="<?= e($value) ?>" <?= in_array($value, $postedDays, true) ? 'checked' : '' ?>> <span><?= e($label) ?></span></label>
          <?php endforeach; ?>
          <?= field_error('days') ?>
        </fieldset>
        <fieldset class="field"<?= has_error('can_lift') ? ' aria-invalid="true" aria-describedby="err-can_lift"' : '' ?>>
          <legend>Can you lift about 40 lb?</legend>
          <label for="lift_yes"><input id="lift_yes" type="radio" name="can_lift" value="yes" required <?= old('can_lift') === 'yes' ? 'checked' : '' ?>> <span>Yes</span></label>
          <label for="lift_no"><input id="lift_no" type="radio" name="can_lift" value="no" <?= old('can_lift') === 'no' ? 'checked' : '' ?>> <span>No</span></label>
          <?= field_error('can_lift') ?>
        </fieldset>
        <div class="field">
          <label for="experience">Prior experience (optional)</label>
          <textarea id="experience" name="experience" maxlength="4000" <?= has_error('experience') ? 'aria-invalid="true" aria-describedby="err-experience"' : '' ?>><?= old('experience') ?></textarea>
          <?= field_error('experience') ?>
        </div>
        <div class="field">
          <label for="start_date">Date you can start</label>
          <input id="start_date" name="start_date" type="date" required value="<?= old('start_date') ?>" <?= has_error('start_date') ? 'aria-invalid="true" aria-describedby="err-start_date"' : '' ?>>
          <?= field_error('start_date') ?>
        </div>
        <div class="field">
          <label for="heard_about">How did you hear about us?</label>
          <input id="heard_about" name="heard_about" required maxlength="190" value="<?= old('heard_about') ?>" <?= has_error('heard_about') ? 'aria-invalid="true" aria-describedby="err-heard_about"' : '' ?>>
          <?= field_error('heard_about') ?>
        </div>
        <div class="field">
          <label for="consent"><input id="consent" type="checkbox" name="consent" value="1" required <?= old('consent') === '1' ? 'checked' : '' ?>> <span>I agree that Loaded Valet Trash may contact me about this application.</span></label>
          <?= field_error('consent') ?>
        </div>
        <button class="btn btn-primary" type="submit">Submit application</button>
      </form>
    </div>
  </div>
</section>
