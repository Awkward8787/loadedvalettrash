<?php
/** @var array{items:array<string,array{low:int,high:int}|null>,loads:array<string,array{low:int,high:int}|null>} $rateBook */
$area = (string) ($settings['service_area'] ?? service_area_phrase());
$ratesJson = json_encode($rateBook, JSON_UNESCAPED_SLASHES);
if (!is_string($ratesJson)) {
    $ratesJson = '{}';
}
$mode = old('mode') === 'load' ? 'load' : 'items';
?>
<section class="section">
  <div class="wrap grid-2">
    <div>
      <p class="eyebrow">Junk pickup</p>
      <h1>Junk pickup.</h1>
      <p class="lede">Bulk and junk item removal for apartment communities and residents in <?= e($area) ?>, including furniture and move-out items.</p>
      <?php include LVT_ROOT . '/templates/partials/junk-ranges.php'; ?>
      <p>Tell us the items or the load size, where they are, and a date that works. <?= e(\Lvt\Junk\Quote::FOLLOW_UP) ?></p>
      <p><a href="/contact#inquiry">Send a message instead</a></p>
    </div>
    <form class="form card" method="post" action="/junk-pickup" enctype="multipart/form-data" data-junk-calculator data-rates="<?= e($ratesJson) ?>">
      <?= csrf_field() ?>
      <p class="hp"><label>Company website <input type="text" name="company_website" tabindex="-1" autocomplete="off"></label></p>
      <?= field_error('form') ?>
      <fieldset class="field"<?= has_error('mode') ? ' aria-invalid="true" aria-describedby="err-mode"' : '' ?>>
        <legend>How should we size it?</legend>
        <label for="mode-items"><input id="mode-items" type="radio" name="mode" value="items"<?= $mode === 'items' ? ' checked' : '' ?>> <span>Count items</span></label>
        <label for="mode-load"><input id="mode-load" type="radio" name="mode" value="load"<?= $mode === 'load' ? ' checked' : '' ?>> <span>Load size</span></label>
        <?= field_error('mode') ?>
      </fieldset>
      <?php foreach (\Lvt\Junk\Quote::items() as $code => $label): ?>
        <div class="field">
          <label for="qty-<?= e($code) ?>"><?= e($label) ?></label>
          <input id="qty-<?= e($code) ?>" name="qty_<?= e($code) ?>" data-junk-qty="<?= e($code) ?>" type="number" min="0" max="50" inputmode="numeric" value="<?= old('qty_' . $code, '0') ?>" <?= has_error('qty_' . $code) ? 'aria-invalid="true" aria-describedby="err-qty_' . e($code) . '"' : '' ?>>
          <?= field_error('qty_' . $code) ?>
        </div>
      <?php endforeach; ?>
      <fieldset class="field"<?= has_error('load_size') ? ' aria-invalid="true" aria-describedby="err-load_size"' : '' ?>>
        <legend>Load size</legend>
        <?php foreach (\Lvt\Junk\Quote::loads() as $code => $label): ?>
          <label for="load-<?= e($code) ?>"><input id="load-<?= e($code) ?>" type="radio" name="load_size" value="<?= e($code) ?>"<?= old('load_size') === $code ? ' checked' : '' ?>> <span><?= e($label) ?></span></label>
        <?php endforeach; ?>
        <?= field_error('load_size') ?>
      </fieldset>
      <p class="estimate-note" data-junk-estimate>Choose items or a load size. <?= e(\Lvt\Junk\Quote::FOLLOW_UP) ?></p>
      <div class="field">
        <label for="address">Address or city</label>
        <input id="address" name="address" required maxlength="255" autocomplete="street-address" value="<?= old('address') ?>" <?= has_error('address') ? 'aria-invalid="true" aria-describedby="err-address"' : '' ?>>
        <?= field_error('address') ?>
      </div>
      <div class="field">
        <label for="service_area">Service area</label>
        <select id="service_area" name="service_area" required <?= has_error('service_area') ? 'aria-invalid="true" aria-describedby="err-service_area"' : '' ?>>
          <option value="">Choose one</option>
          <?php foreach (service_area_options() as $value => $label): ?>
            <option value="<?= e($value) ?>"<?= old('service_area') === $value ? ' selected' : '' ?>><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
        <?= field_error('service_area') ?>
      </div>
      <div class="field">
        <label for="preferred_on">Preferred date</label>
        <input id="preferred_on" name="preferred_on" type="date" required value="<?= old('preferred_on') ?>" <?= has_error('preferred_on') ? 'aria-invalid="true" aria-describedby="err-preferred_on"' : '' ?>>
        <?= field_error('preferred_on') ?>
      </div>
      <div class="field">
        <label for="photo">Photo (optional)</label>
        <input id="photo" name="photo" type="file" accept="image/jpeg,image/png,image/webp" <?= has_error('photo') ? 'aria-invalid="true" aria-describedby="err-photo"' : '' ?>>
        <?= field_error('photo') ?>
      </div>
      <div class="field">
        <label for="name">Name</label>
        <input id="name" name="name" required maxlength="120" autocomplete="name" value="<?= old('name') ?>" <?= has_error('name') ? 'aria-invalid="true" aria-describedby="err-name"' : '' ?>>
        <?= field_error('name') ?>
      </div>
      <div class="field">
        <label for="email">Email</label>
        <input id="email" name="email" type="email" required maxlength="190" autocomplete="email" value="<?= old('email') ?>" <?= has_error('email') ? 'aria-invalid="true" aria-describedby="err-email"' : '' ?>>
        <?= field_error('email') ?>
      </div>
      <div class="field">
        <label for="phone">Phone (optional)</label>
        <input id="phone" name="phone" type="tel" maxlength="40" autocomplete="tel" value="<?= old('phone') ?>" <?= has_error('phone') ? 'aria-invalid="true" aria-describedby="err-phone"' : '' ?>>
        <?= field_error('phone') ?>
      </div>
      <div class="field">
        <label for="note">Note (optional)</label>
        <textarea id="note" name="note" maxlength="4000" <?= has_error('note') ? 'aria-invalid="true" aria-describedby="err-note"' : '' ?>><?= old('note') ?></textarea>
        <?= field_error('note') ?>
      </div>
      <button class="btn btn-primary" type="submit">Request estimate</button>
    </form>
  </div>
</section>
