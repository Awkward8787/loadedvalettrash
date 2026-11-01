<?php
$kinds = public_inquiry_kinds();
$posted = (string) ($GLOBALS['_old']['kind'] ?? '');
$intent = $posted !== '' ? $posted : \Lvt\Request::current()->string('intent');
if (!array_key_exists($intent, $kinds)) {
    $intent = 'proposal';
}
?>
<section class="section">
  <div class="wrap grid-2">
    <div>
      <p class="eyebrow">Request</p>
      <h1>Tell us what you need.</h1>
      <p class="lede">Ask for a proposal, a 10-minute walkthrough, or party and event cleanup. Junk pickup, including move-out items, uses the estimate form. Anyone can also send a message below. Serving apartment communities in <?= e((string) ($settings['service_area'] ?? service_area_phrase())) ?>. The note lands in our office queue. We reply with the contact details you give us.</p>
      <?php if (filled($settings['phone'] ?? null) || filled($settings['email'] ?? null) || filled($settings['address'] ?? null)): ?>
        <div class="card">
          <?php if (filled($settings['phone'] ?? null)): ?><p><?= e((string) $settings['phone']) ?></p><?php endif; ?>
          <?php if (filled($settings['email'] ?? null)): ?><p><?= e((string) $settings['email']) ?></p><?php endif; ?>
          <?php if (filled($settings['address'] ?? null)): ?><p><?= nl((string) $settings['address']) ?></p><?php endif; ?>
        </div>
      <?php endif; ?>
    </div>
    <form class="form card" method="post" action="/contact">
      <?= csrf_field() ?>
      <p class="hp"><label>Company website <input type="text" name="company_website" tabindex="-1" autocomplete="off"></label></p>
      <div class="field">
        <label for="kind">What do you need?</label>
        <select id="kind" name="kind" data-jump-value="junk_estimate" data-jump-href="/junk-pickup">
          <?php foreach ($kinds as $value => $label): ?>
            <option value="<?= e($value) ?>"<?= $intent === $value ? ' selected' : '' ?>><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
        <p class="muted">For a home or a private event, put the location in Property name and enter 1 for units.</p>
        <p><a href="/junk-pickup">Request a junk pickup estimate</a></p>
      </div>
      <div class="field">
        <label for="property_name">Property name</label>
        <input id="property_name" name="property_name" required maxlength="180" value="<?= old('property_name') ?>" <?= has_error('property_name') ? 'aria-invalid="true" aria-describedby="err-property_name"' : '' ?>>
        <?= field_error('property_name') ?>
      </div>
      <div class="field">
        <label for="units">Number of units</label>
        <input id="units" name="units" type="number" required min="1" max="5000" inputmode="numeric" value="<?= old('units') ?>" <?= has_error('units') ? 'aria-invalid="true" aria-describedby="err-units"' : '' ?>>
        <?= field_error('units') ?>
      </div>
      <div class="field">
        <label for="contact_name">Your name</label>
        <input id="contact_name" name="contact_name" required maxlength="120" autocomplete="name" value="<?= old('contact_name') ?>" <?= has_error('contact_name') ? 'aria-invalid="true" aria-describedby="err-contact_name"' : '' ?>>
        <?= field_error('contact_name') ?>
      </div>
      <div class="field">
        <label for="contact_email">Email</label>
        <input id="contact_email" name="contact_email" type="email" maxlength="190" autocomplete="email" value="<?= old('contact_email') ?>" <?= has_error('contact_email') ? 'aria-invalid="true" aria-describedby="err-contact_email"' : '' ?>>
        <?= field_error('contact_email') ?>
      </div>
      <div class="field">
        <label for="contact_phone">Phone</label>
        <input id="contact_phone" name="contact_phone" type="tel" maxlength="40" autocomplete="tel" value="<?= old('contact_phone') ?>" <?= has_error('contact_phone') ? 'aria-invalid="true" aria-describedby="err-contact_phone"' : '' ?>>
        <?= field_error('contact_phone') ?>
      </div>
      <div class="field">
        <label for="message">Message</label>
        <textarea id="message" name="message" maxlength="4000"><?= old('message') ?></textarea>
        <?= field_error('message') ?>
      </div>
      <button class="btn btn-primary" type="submit">Send</button>
    </form>
  </div>
</section>
<section class="section section-alt" id="inquiry">
  <div class="wrap grid-2">
    <div>
      <p class="eyebrow">Message</p>
      <h2>Send a message.</h2>
      <p>Property managers, residents, and anyone else can reach the office here. Choose junk pickup estimate if you want the calculator instead.</p>
    </div>
    <form class="form card" method="post" action="/contact/inquiry">
      <?= csrf_field() ?>
      <p class="hp"><label>Company website <input type="text" name="company_website" tabindex="-1" autocomplete="off"></label></p>
      <?= field_error('form') ?>
      <div class="field">
        <label for="inq_name">Name</label>
        <input id="inq_name" name="inq_name" required maxlength="120" autocomplete="name" value="<?= old('inq_name') ?>" <?= has_error('inq_name') ? 'aria-invalid="true" aria-describedby="err-inq_name"' : '' ?>>
        <?= field_error('inq_name') ?>
      </div>
      <div class="field">
        <label for="inq_email">Email</label>
        <input id="inq_email" name="inq_email" type="email" required maxlength="190" autocomplete="email" value="<?= old('inq_email') ?>" <?= has_error('inq_email') ? 'aria-invalid="true" aria-describedby="err-inq_email"' : '' ?>>
        <?= field_error('inq_email') ?>
      </div>
      <div class="field">
        <label for="inq_phone">Phone (optional)</label>
        <input id="inq_phone" name="inq_phone" type="tel" maxlength="40" autocomplete="tel" value="<?= old('inq_phone') ?>" <?= has_error('inq_phone') ? 'aria-invalid="true" aria-describedby="err-inq_phone"' : '' ?>>
        <?= field_error('inq_phone') ?>
      </div>
      <fieldset class="field"<?= has_error('inq_role') ? ' aria-invalid="true" aria-describedby="err-inq_role"' : '' ?>>
        <legend>Role</legend>
        <?php $firstRole = true; ?>
        <?php foreach (outreach_roles() as $value => $label): ?>
          <label for="inq-role-<?= e($value) ?>"><input id="inq-role-<?= e($value) ?>" type="radio" name="inq_role" value="<?= e($value) ?>"<?= $firstRole ? ' required' : '' ?><?= old('inq_role') === $value ? ' checked' : '' ?>> <span><?= e($label) ?></span></label>
          <?php $firstRole = false; ?>
        <?php endforeach; ?>
        <?= field_error('inq_role') ?>
      </fieldset>
      <div class="field">
        <label for="inq_organization">Property or company (optional)</label>
        <input id="inq_organization" name="inq_organization" maxlength="180" value="<?= old('inq_organization') ?>" <?= has_error('inq_organization') ? 'aria-invalid="true" aria-describedby="err-inq_organization"' : '' ?>>
        <?= field_error('inq_organization') ?>
      </div>
      <div class="field">
        <label for="inq_service_area">City / service area</label>
        <select id="inq_service_area" name="inq_service_area" required <?= has_error('inq_service_area') ? 'aria-invalid="true" aria-describedby="err-inq_service_area"' : '' ?>>
          <option value="">Choose one</option>
          <?php foreach (service_area_options() as $value => $label): ?>
            <option value="<?= e($value) ?>"<?= old('inq_service_area') === $value ? ' selected' : '' ?>><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
        <?= field_error('inq_service_area') ?>
      </div>
      <div class="field">
        <label for="inq_need">What do you need?</label>
        <select id="inq_need" name="inq_need" required data-jump-value="junk_estimate" data-jump-href="/junk-pickup" <?= has_error('inq_need') ? 'aria-invalid="true" aria-describedby="err-inq_need"' : '' ?>>
          <option value="">Choose one</option>
          <?php foreach (outreach_needs() as $value => $label): ?>
            <option value="<?= e($value) ?>"<?= old('inq_need') === $value ? ' selected' : '' ?>><?= e($label) ?></option>
          <?php endforeach; ?>
          <option value="junk_estimate"<?= old('inq_need') === 'junk_estimate' ? ' selected' : '' ?>>Junk pickup estimate</option>
        </select>
        <?= field_error('inq_need') ?>
        <p><a href="/junk-pickup">Junk pickup estimate</a></p>
      </div>
      <div class="field">
        <label for="inq_message">Message</label>
        <textarea id="inq_message" name="inq_message" required maxlength="4000" <?= has_error('inq_message') ? 'aria-invalid="true" aria-describedby="err-inq_message"' : '' ?>><?= old('inq_message') ?></textarea>
        <?= field_error('inq_message') ?>
      </div>
      <button class="btn btn-primary" type="submit">Send message</button>
    </form>
  </div>
</section>
