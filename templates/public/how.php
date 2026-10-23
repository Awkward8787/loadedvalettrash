<?php
$price = money_plain((int) $settings['opt_in_price_cents']);
$setout = clock_label((string) $settings['setout_start']) . ' to ' . clock_label((string) $settings['setout_end']);
$pickup = clock_label((string) $settings['pickup_start']) . ' to ' . clock_label((string) $settings['pickup_end']);
?>
<section class="section">
  <div class="wrap">
    <p class="eyebrow">Service</p>
    <h1>How valet trash works</h1>
    <p class="lede">Monday through Friday. Residents set a trash can outside the door for two hours, with the bagged trash inside it. Never put a loose bag on the ground. Porters take it to the community compactor the same night.</p>
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
          <li>Doorstep collection from the can outside opted-in homes, or from every door on a community-wide plan. Bagged trash stays inside the can. Any bag that does not fit inside the can sits on top of it. On extra-bag nights, extra bags go only on top of the can, never on the ground or beside it.</li>
          <li>On this route, haul is to the property’s own compactor or dumpster. We are not the property’s waste hauler. Move-out haul-off is a separate job, quoted per job.</li>
          <li>Uniformed porters. They do not enter apartments.</li>
          <li>A door notice and a photo when a set-out breaks the rules.</li>
          <li>A monthly roster the office uses to bill <?= e($price) ?>.</li>
          <li>Same-day return if we miss a can that was out on time.</li>
        </ul>
      </article>
    </div>
  </div>
</section>
<section class="section section-alt">
  <div class="wrap">
    <h2>Resident rules</h2>
    <?php include LVT_ROOT . '/templates/partials/setout-callout.php'; ?>
    <ul class="rule-list">
      <li>Tie the trash in a bag and put it inside your can. Kitchen size (13 gallon) up to 30 gallon. <?= (int) $settings['max_bag_weight_lb'] ?> pounds max per bag. Bags must be tied and leak-proof.</li>
      <li>Up to <?= (int) $settings['max_bags_per_night'] ?> bags a night, and up to <?= (int) $settings['holiday_bags'] ?> bags the night after a holiday. On extra-bag nights, extra bags go only on top of the can, never on the ground or beside it.</li>
      <li>Put your trash can outside your door with the bagged trash inside it. Never put a loose bag on the ground. Set the can right outside your door during the set-out window, not on stairs or walkways.</li>
      <li>A second bag goes on top of the can, never on the ground or beside it. On extra-bag nights, every extra bag goes only on top of the can. Loose bags scatter garbage.</li>
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
    <p>If the can was out by the end of the set-out window, with the bagged trash inside it, and it was not collected, tell the leasing office by <?= e((string) $settings['missed_report_by']) ?> the next day, or <a href="/missed-pickup">report it here</a>. If it was our miss, we return the same day. If the can went out late, bring it in and set it out the next service night. Never put a loose bag on the ground.</p>
    <h2>Holidays and weather</h2>
    <p>There is no service on the holidays marked off below. Residents get notice ahead of time. The next service night allows up to <?= (int) $settings['holiday_bags'] ?> bags. Severe storms, lightning, or a tropical-storm warning can delay a route up to two hours or cancel it. We notify residents by 7 PM, and the next night is a double-bag night. On those extra-bag nights, extra bags go only on top of the can, never on the ground or beside it. Monthly billing is not prorated for holidays or weather.</p>
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
