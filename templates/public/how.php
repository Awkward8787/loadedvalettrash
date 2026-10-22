<?php
$price = money_plain((int) $settings['opt_in_price_cents']);
$setout = clock_label((string) $settings['setout_start']) . ' to ' . clock_label((string) $settings['setout_end']);
$pickup = clock_label((string) $settings['pickup_start']) . ' to ' . clock_label((string) $settings['pickup_end']);
?>
<section class="section">
  <div class="wrap">
    <p class="eyebrow">Service</p>
    <h1>How valet trash works</h1>
    <p class="lede">Monday through Friday. Residents set bags out for two hours. Porters take them to the community compactor the same night.</p>
    <div class="grid-2">
      <article class="card">
        <h2>Schedule</h2>
        <ul class="rule-list">
          <li>Service days: Monday, Tuesday, Wednesday, Thursday, and Friday. No Saturday or Sunday service.</li>
          <li>Set-out window: <?= e($setout) ?>.</li>
          <li>Porter pickup: <?= e($pickup) ?>.</li>
          <li>Trash left out before the window, or still out after pickup, goes back inside.</li>
        </ul>
      </article>
      <article class="card">
        <h2>What is included</h2>
        <ul class="rule-list">
          <li>Doorstep collection of bagged household trash from opted-in homes, or from every door on a community-wide plan.</li>
          <li>Haul to the property’s own compactor or dumpster. We are not the waste hauler.</li>
          <li>Uniformed porters. They do not enter apartments.</li>
          <li>A door notice and a photo when a bag breaks the rules.</li>
          <li>A monthly roster the office uses to bill <?= e($price) ?>.</li>
          <li>Same-day return if we miss a bag that was out on time.</li>
        </ul>
      </article>
    </div>
  </div>
</section>
<section class="section section-alt">
  <div class="wrap">
    <h2>Resident rules</h2>
    <ul class="rule-list">
      <li>Tied, leak-proof bag. Kitchen size (13 gallon) up to 30 gallon. <?= (int) $settings['max_bag_weight_lb'] ?> pounds max per bag.</li>
      <li>Up to <?= (int) $settings['max_bags_per_night'] ?> bags a night, and up to <?= (int) $settings['holiday_bags'] ?> bags the night after a holiday.</li>
      <li>Place bags at the door, or in a bin if the property supplies one. Not on stairs or walkways.</li>
      <li>No loose trash, no open boxes of trash, no leaking bags.</li>
      <li>Cardboard: flatten and tie a small bundle of up to two boxes, or take it to the compactor.</li>
      <li>Not accepted: furniture and other bulk items, mattresses, electronics, paint, chemicals, batteries, needles or medical waste, construction debris, hot ashes, unbagged pet waste or litter.</li>
      <li>Recycling only if the property has recycling service, bagged and kept separate, on the property’s designated night.</li>
    </ul>
    <h2>When a rule is broken</h2>
    <ol class="rule-list">
      <li>First time: a courtesy notice on the door.</li>
      <li>Second time: the notice is copied to the office.</li>
      <li>Third time within 30 days: the office may pause service or apply its own lease rules. The property decides. The porter does not.</li>
    </ol>
    <h2>Missed pickups</h2>
    <p>If the bag was out by the end of the set-out window and was not collected, tell the leasing office by <?= e((string) $settings['missed_report_by']) ?> the next day, or <a href="/missed-pickup">report it here</a>. If it was our miss, we return the same day. If the bag went out late, bring it in and set it out the next service night.</p>
    <h2>Holidays and weather</h2>
    <p>There is no service on the holidays marked off below. Residents get notice ahead of time. The next service night allows up to <?= (int) $settings['holiday_bags'] ?> bags. Severe storms, lightning, or a tropical-storm warning can delay a route up to two hours or cancel it. We notify residents by 7 PM, and the next night is a double-bag night. Monthly billing is not prorated for holidays or weather.</p>
    <?php if (!empty($holidays)): ?>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Holiday</th><th>Date</th><th>Service</th><th>What residents do</th></tr></thead>
          <tbody>
          <?php foreach ($holidays as $holiday): ?>
            <tr>
              <td><?= e((string) $holiday['name']) ?></td>
              <td><?= e((string) $holiday['observed_on']) ?></td>
              <td><?= (int) $holiday['service_off'] === 1 ? 'No service' : 'Normal service' ?></td>
              <td><?= e((string) $holiday['instructions']) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</section>
