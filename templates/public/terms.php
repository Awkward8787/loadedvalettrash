<?php $price = money_plain((int) $settings['opt_in_price_cents']); ?>
<section class="section">
  <div class="wrap">
    <p class="eyebrow">Terms</p>
    <h1>Terms of service</h1>
    <p>These terms describe the Loaded Valet Trash website and the default service offer. A signed service agreement with a property controls if it says something different. This page is not legal advice. Have your own counsel read any agreement before you sign it.</p>
    <h2>The service</h2>
    <p>On the doorstep route we collect bagged household trash from the can outside the door on Monday through Friday evenings and take it to the property’s compactor or dumpster. Put your trash can outside your door with the bagged trash inside it. Never put a loose bag on the ground. We do not haul that doorstep trash off the property, and we do not enter homes. The set-out window, pickup window, bag limits, and holiday list on the <a href="/how-it-works">how it works</a> page are the default rules.</p>
    <h2>Opt-in price</h2>
    <p>The default resident price is <?= e($price) ?> per opted-in door each month, billed by the leasing office as a rent add-on. The property remits collections, minus any property share written into the agreement, by the 10th. Billing is not prorated for the holiday nights or weather cancellations built into the schedule.</p>
    <h2>Community-wide service</h2>
    <p>A community-wide price is whatever written quote we send for that property. It may be a per-door rate or a flat monthly amount. The public site does not lock in a community-wide number.</p>
    <h2>Event cleanup and move-outs</h2>
    <p>Party and event cleanup is quoted per event. Move-out cleanout and trash haul-off is quoted per job. Those jobs are separate from the weekly doorstep route. This page does not set a price or a minimum for them.</p>
    <h2>Trial</h2>
    <p>A free 30-day trial, when we offer one, covers Monday to Friday service on the buildings the manager names. The property gives porters access to the gate and compactor and joins a short check-in around day 14 and day 30. Either side can stop at the end of the trial. After a trial, service continues month to month unless a signed agreement sets a longer term.</p>
    <h2>Missed pickups and violations</h2>
    <p>Report a can that was out on time by <?= e((string) $settings['missed_report_by']) ?> the next day. If it was our miss, we return the same day. Rule breaks get a door notice. The third notice in 30 days is for the office. Any lease fine or pause is the property’s decision.</p>
    <h2>Certificate</h2>
    <p>A certificate is provided before service starts. This page does not state a coverage limit and does not name a property as an additional insured.</p>
    <?php if (filled($settings['insurance_text'] ?? null)): ?>
      <p><?= nl((string) $settings['insurance_text']) ?></p>
    <?php endif; ?>
    <h2>The website</h2>
    <p>Information on this site can change, including price and service area, when the owner updates it. Do not rely on a cached page in place of a written quote. You may not use the forms to send someone else’s personal information without a reason related to this service, and you may not try to break into the staff tools.</p>
    <h2>Liability</h2>
    <p>To the extent the law allows, we are responsible for our own negligence on the route. The property is responsible for the compactor, the premises, and its lease enforcement. Neither side is responsible for indirect or consequential losses. Nothing here limits liability that cannot legally be limited.</p>
    <h2>Georgia</h2>
    <p>These terms are governed by the laws of the State of Georgia, without regard to conflict-of-law rules. If a court finds one sentence unenforceable, the rest still applies.</p>
    <p class="muted">Updated October 23, 2026.</p>
  </div>
</section>
