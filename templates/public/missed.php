<section class="section">
  <div class="wrap grid-2">
    <div>
      <p class="eyebrow">Residents and offices</p>
      <h1>Report a missed pickup</h1>
      <p class="lede">If your trash can was outside by the end of the set-out window, with the bagged trash inside it, and it was still there in the morning, tell us by <?= e((string) $settings['missed_report_by']) ?>. A miss that is ours is picked up the same day.</p>
      <p>If the can went out late, bring it back inside and set it out on the next service night. Put your trash can outside your door with the bagged trash inside it. Never put a loose bag on the ground.</p>
      <?php include LVT_ROOT . '/templates/partials/setout-callout.php'; ?>
    </div>
    <form class="form card" method="post" action="/missed-pickup">
      <?= csrf_field() ?>
      <p class="hp"><label>Company website <input type="text" name="company_website" tabindex="-1" autocomplete="off"></label></p>
      <div class="field">
        <label for="property_name">Property</label>
        <input id="property_name" name="property_name" required value="<?= old('property_name') ?>" <?= has_error('property_name') ? 'aria-invalid="true" aria-describedby="err-property_name"' : '' ?>>
        <?= field_error('property_name') ?>
      </div>
      <div class="field">
        <label for="unit_label">Unit</label>
        <input id="unit_label" name="unit_label" required maxlength="40" value="<?= old('unit_label') ?>" <?= has_error('unit_label') ? 'aria-invalid="true" aria-describedby="err-unit_label"' : '' ?>>
        <?= field_error('unit_label') ?>
      </div>
      <div class="field">
        <label for="reporter_name">Your name</label>
        <input id="reporter_name" name="reporter_name" required value="<?= old('reporter_name') ?>" <?= has_error('reporter_name') ? 'aria-invalid="true" aria-describedby="err-reporter_name"' : '' ?>>
        <?= field_error('reporter_name') ?>
      </div>
      <div class="field">
        <label for="reporter_role">You are</label>
        <input id="reporter_role" name="reporter_role" value="<?= old('reporter_role') ?>" placeholder="Resident or leasing office">
      </div>
      <div class="field">
        <label for="reporter_contact">Phone or email</label>
        <input id="reporter_contact" name="reporter_contact" required value="<?= old('reporter_contact') ?>" <?= has_error('reporter_contact') ? 'aria-invalid="true" aria-describedby="err-reporter_contact"' : '' ?>>
        <?= field_error('reporter_contact') ?>
      </div>
      <div class="field">
        <label for="happened_on">Service night</label>
        <input id="happened_on" name="happened_on" type="date" value="<?= old('happened_on') ?>">
        <?= field_error('happened_on') ?>
      </div>
      <div class="field">
        <label for="description">What was set out?</label>
        <textarea id="description" name="description" required minlength="10"><?= old('description') ?></textarea>
        <?= field_error('description') ?>
      </div>
      <button class="btn btn-primary" type="submit">Send the report</button>
    </form>
  </div>
</section>
