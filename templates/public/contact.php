<?php
$posted = (string) ($GLOBALS['_old']['kind'] ?? '');
$intent = $posted !== '' ? $posted : \Lvt\Request::current()->string('intent');
if (!in_array($intent, ['proposal', 'walkthrough'], true)) {
    $intent = 'proposal';
}
?>
<section class="section">
  <div class="wrap grid-2">
    <div>
      <p class="eyebrow">Proposal</p>
      <h1>Tell us about the property.</h1>
      <p class="lede">Ask for a proposal or a 10-minute walkthrough. The note lands in our office queue. We reply with the contact details you give us.</p>
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
        <select id="kind" name="kind">
          <option value="proposal"<?= $intent === 'proposal' ? ' selected' : '' ?>>Request a proposal</option>
          <option value="walkthrough"<?= $intent === 'walkthrough' ? ' selected' : '' ?>>Book a walkthrough</option>
        </select>
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
