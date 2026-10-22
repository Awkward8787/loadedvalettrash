<section class="section">
  <div class="wrap">
    <h1>Share a review</h1>
    <p class="lede">Use your name, the property, and your role. Submissions wait in a private queue until they are approved.</p>
    <form class="form card" method="post" action="/reviews">
      <?= csrf_field() ?>
      <p class="hp"><label>Company website <input type="text" name="company_website" tabindex="-1" autocomplete="off"></label></p>
      <div class="field">
        <label for="reviewer_name">Your name</label>
        <input id="reviewer_name" name="reviewer_name" required maxlength="120" value="<?= old('reviewer_name') ?>" <?= has_error('reviewer_name') ? 'aria-invalid="true" aria-describedby="err-reviewer_name"' : '' ?>>
        <?= field_error('reviewer_name') ?>
      </div>
      <div class="field">
        <label for="property_name">Property</label>
        <input id="property_name" name="property_name" required maxlength="180" value="<?= old('property_name') ?>" <?= has_error('property_name') ? 'aria-invalid="true" aria-describedby="err-property_name"' : '' ?>>
        <?= field_error('property_name') ?>
      </div>
      <div class="field">
        <label for="role_title">Your role</label>
        <input id="role_title" name="role_title" required maxlength="80" placeholder="Community manager" value="<?= old('role_title') ?>" <?= has_error('role_title') ? 'aria-invalid="true" aria-describedby="err-role_title"' : '' ?>>
        <?= field_error('role_title') ?>
      </div>
      <fieldset class="field">
        <legend>Rating</legend>
        <?php for ($i = 5; $i >= 1; $i--): ?>
          <label><input type="radio" name="rating" value="<?= $i ?>" <?= (string) ($GLOBALS['_old']['rating'] ?? '') === (string) $i ? 'checked' : '' ?>> <?= $i ?> out of 5</label>
        <?php endfor; ?>
        <?= field_error('rating') ?>
      </fieldset>
      <div class="field">
        <label for="body">What should other offices know?</label>
        <textarea id="body" name="body" required minlength="20" maxlength="2000" <?= has_error('body') ? 'aria-invalid="true" aria-describedby="err-body"' : '' ?>><?= old('body') ?></textarea>
        <?= field_error('body') ?>
      </div>
      <button class="btn btn-primary" type="submit">Submit for approval</button>
    </form>
  </div>
</section>
