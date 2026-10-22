<?php $price = money_plain((int) $settings['opt_in_price_cents']); ?>
<section class="section">
  <div class="wrap">
    <p class="eyebrow">Pricing</p>
    <h1>Opt in at <?= e($price) ?>, or ask for a community-wide quote.</h1>
    <div class="grid-2">
      <article class="card price-card featured">
        <h2>Opt-in</h2>
        <p class="price"><?= e($price) ?> <small>per opted-in door, each month</small></p>
        <ul class="rule-list">
          <li>The resident pays through rent. The property does not get a separate vendor bill.</li>
          <li>You keep the property share we agree on. The starting point we model is <?= e(rtrim(rtrim(number_format((float) $settings['default_property_share_percent'], 2), '0'), '.')) ?>%.</li>
          <li>We send the roster on the 1st. You remit by the 10th.</li>
          <li>Cancel with 30 days’ notice to the office. Service ends at the next month-end.</li>
        </ul>
      </article>
      <article class="card">
        <h2>Community-wide</h2>
        <p class="price">Contact us</p>
        <p>Some properties want every door on the route. We quote a per-door rate or a flat monthly amount for the whole community. You choose whether residents pay a fee, and how much.</p>
        <p>There is no published community-wide price on this site. The number depends on unit count, layout, and how far the compactor is from the buildings.</p>
        <a class="btn btn-primary" href="/contact">Contact for a community-wide quote</a>
      </article>
    </div>
    <div class="card">
      <h2>Free 30-day trial</h2>
      <p>Monday–Friday service for 30 days on the buildings you pick, or the whole property. We bring flyers and the opt-in addendum. You give porters gate and compactor access and a short check-in around day 14 and day 30. At the end you continue, expand, or stop. The trial is free.</p>
      <a class="btn btn-ghost" href="/contact?intent=walkthrough">Book a walkthrough</a>
    </div>
  </div>
</section>
