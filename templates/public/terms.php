<?php $price = money_plain((int) $settings['opt_in_price_cents']); ?>
<section class="section">
  <div class="wrap">
    <p class="eyebrow">Terms</p>
    <h1>Terms of service</h1>
    <p>These terms describe the Loaded Valet Trash website and the default service offer. A signed service agreement with a property controls if it says something different. This page is not legal advice. Have your own counsel read any agreement before you sign it.</p>
    <h2>The service</h2>
    <p>We collect bagged household trash from outside doors on Monday through Friday evenings and take it to the property’s compactor or dumpster. We do not haul waste off the property, and we do not enter homes. The set-out window, pickup window, bag limits, and holiday list on the <a href="/how-it-works">how it works</a> page are the default rules.</p>
    <h2>Opt-in price</h2>
    <p>The default resident price is <?= e($price) ?> per opted-in door each month, billed by the leasing office as a rent add-on. The property remits collections, minus any property share written into the agreement, by the 10th. Billing is not prorated for the holiday nights or weather cancellations built into the schedule.</p>
    <h2>Community-wide service</h2>
    <p>A community-wide price is whatever written quote we send for that property. It may be a per-door rate or a flat monthly amount. The public site does not lock in a community-wide number.</p>
    <h2>Trial</h2>
    <p>A free 30-day trial, when we offer one, covers Monday–Friday service on the buildings the manager names. The property gives porters access to the gate and compactor and joins a short check-in around day 14 and day 30. Either side can stop at the end of the trial. After a trial, service continues month to month unless a signed agreement sets a longer term.</p>
    <h2>Missed pickups and violations</h2>
    <p>Report a bag that was out on time by <?= e((string) $settings['missed_report_by']) ?> the next day. If it was our miss, we return the same day. Rule breaks get a door notice. The third notice in 30 days is for the office. Any lease fine or pause is the property’s decision.</p>
    <h2>Insurance</h2>
    <?php if (filled($settings['insurance_text'] ?? null)): ?>
      <p><?= nl((string) $settings['insurance_text']) ?></p>
    <?php else: ?>
      <p>Any insurance we carry is described in the certificate and the service agreement, not assumed from this website. We will not state a coverage limit here until the owner has entered it. Porters do not enter apartments.</p>
    <?php endif; ?>
    <h2>The website</h2>
    <p>Information on this site can change, including price and service area, when the owner updates it. Do not rely on a cached page in place of a written quote. You may not use the forms to send someone else’s personal information without a reason related to this service, and you may not try to break into the staff tools.</p>
    <h2>Liability</h2>
    <p>To the extent the law allows, we are responsible for our own negligence on the route. The property is responsible for the compactor, the premises, and its lease enforcement. Neither side is responsible for indirect or consequential losses. Nothing here limits liability that cannot legally be limited.</p>
    <h2>Georgia</h2>
    <p>These terms are governed by the laws of the State of Georgia, without regard to conflict-of-law rules. If a court finds one sentence unenforceable, the rest still applies.</p>
    <p class="muted">Updated October 22, 2026.</p>
  </div>
</section>
