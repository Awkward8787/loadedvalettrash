<?php
$price = money_plain((int) $settings['opt_in_price_cents']);
$setout = clock_label((string) $settings['setout_start']) . '–' . clock_label((string) $settings['setout_end']);
$pickup = clock_label((string) $settings['pickup_start']) . '–' . clock_label((string) $settings['pickup_end']);
?>
<section class="section">
  <div class="wrap">
    <p class="eyebrow">Services</p>
    <h1>Doorstep valet, event cleanup, and move-outs.</h1>
    <p class="lede">Three jobs. Doorstep valet is the weeknight route for apartment complexes. Party and event cleanup, and move-out haul-off, are quoted one job at a time.</p>
    <div class="grid-3 services">
      <article class="card">
        <h2>Doorstep valet trash</h2>
        <p class="price"><?= e($price) ?> <small>/ door / month</small></p>
        <p>For apartment complexes. Residents who want it opt in through the leasing office. The office bills <?= e($price) ?> a month as a rent add-on, the same way it bills pet rent. Porters pick up outside each door Monday through Friday evenings and carry the trash to the property compactor. They do not enter the apartment.</p>
        <p>Set-out is <?= e($setout) ?>. Pickup is <?= e($pickup) ?>. You can also buy it community-wide and set your own resident fee. That price is a quote, not a number on this page.</p>
        <a class="btn btn-primary" href="/contact">Request a proposal</a>
      </article>
      <article class="card">
        <h2>Party and event cleanup</h2>
        <p class="service-price">Quoted per event</p>
        <p>Cleanup after parties and events, for residents, for properties and clubhouses, and for private events.</p>
        <a class="btn btn-ghost" href="/contact?intent=event_cleanup">Request a quote</a>
      </article>
      <article class="card">
        <h2>Move-outs</h2>
        <p class="service-price">Quoted per job</p>
        <p>Move-out cleanout and trash haul-off for leasing offices, apartment complexes, and homes.</p>
        <a class="btn btn-ghost" href="/contact?intent=move_out">Request a quote</a>
      </article>
    </div>
  </div>
</section>
<section class="section section-alt">
  <div class="wrap">
    <h2>How residents set trash out</h2>
    <p>This rule is for the doorstep route. Party cleanup and move-outs are arranged through the quote form.</p>
    <?php include LVT_ROOT . '/templates/partials/setout-callout.php'; ?>
    <p><a href="/how-it-works">Bag rules, holidays, and missed pickups</a></p>
  </div>
</section>
