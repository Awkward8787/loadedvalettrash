<?php
$price = money_plain((int) $settings['opt_in_price_cents']);
$share = rtrim(rtrim(number_format((float) $settings['default_property_share_percent'], 2), '0'), '.');
$setout = clock_label((string) $settings['setout_start']) . '–' . clock_label((string) $settings['setout_end']);
$pickup = clock_label((string) $settings['pickup_start']) . '–' . clock_label((string) $settings['pickup_end']);
?>
<section class="hero">
  <div class="wrap hero-grid">
    <div class="hero-mark">
      <picture>
        <source srcset="/assets/brand/loaded-valet-trash-logo.webp" type="image/webp">
        <img src="/assets/brand/loaded-valet-trash-logo.png" alt="" width="773" height="897">
      </picture>
    </div>
    <div class="hero-copy">
      <?php if (filled($settings['service_area'] ?? null)): ?>
        <p class="eyebrow"><?= e((string) $settings['service_area']) ?></p>
      <?php endif; ?>
      <h1>Doorstep valet trash for your residents, Monday through Friday.</h1>
      <p class="lede">Residents who want it opt in for <?= e($price) ?> a month. Your office bills it like pet rent. Our porters take bagged trash from the can outside each door to your compactor. No extra staff.</p>
      <div class="cta-row">
        <a class="btn btn-primary" href="/contact?intent=walkthrough">Book a walkthrough</a>
        <a class="btn btn-ghost" href="/contact">Request a proposal</a>
      </div>
      <aside class="hero-card">
        <div class="fact"><span>Set-out</span> <?= e($setout) ?></div>
        <div class="fact"><span>Pickup</span> <?= e($pickup) ?></div>
        <div class="fact"><span>Bags</span> Up to <?= (int) $settings['max_bags_per_night'] ?> a night</div>
        <div class="fact"><span>Trial</span> 30 days, buildings you choose</div>
      </aside>
    </div>
  </div>
</section>

<section class="section section-alt" id="services">
  <div class="wrap">
    <div class="section-head">
      <h2>Services</h2>
      <p>Doorstep valet for apartment complexes, cleanup after parties and events, and move-out haul-off.</p>
    </div>
    <div class="grid-3">
      <article class="card">
        <h3>Doorstep valet trash</h3>
        <p class="price"><?= e($price) ?> <small>/ door / month</small></p>
        <p>For apartment complexes. Residents opt in through the leasing office. Your office bills <?= e($price) ?> a month as a rent add-on, the same way it bills pet rent. Porters pick up outside each door Monday through Friday evenings.</p>
        <a class="btn btn-primary" href="/contact">Request a proposal</a>
      </article>
      <article class="card">
        <h3>Party and event cleanup</h3>
        <p class="service-price">Quoted per event</p>
        <p>Cleanup after parties and events, for residents, for properties and clubhouses, and for private events.</p>
        <a class="btn btn-ghost" href="/contact?intent=event_cleanup">Request a quote</a>
      </article>
      <article class="card">
        <h3>Move-outs</h3>
        <p class="service-price">Quoted per job</p>
        <p>Move-out cleanout and trash haul-off for leasing offices, apartment complexes, and homes.</p>
        <a class="btn btn-ghost" href="/contact?intent=move_out">Request a quote</a>
      </article>
    </div>
    <p><a href="/services">Service details</a></p>
  </div>
</section>

<section class="section">
  <div class="wrap">
    <div class="section-head">
      <h2>How it works</h2>
      <p>Built so the leasing office adds one line and sends one payment.</p>
    </div>
    <div class="grid-4">
      <article class="card"><div class="step-no">1</div><h3>Resident opts in</h3><p>They sign a one-page addendum at the office or in the resident portal.</p></article>
      <article class="card"><div class="step-no">2</div><h3>Office bills <?= e($price) ?></h3><p>It sits on the ledger like pet rent. Only residents who want it are charged.</p></article>
      <article class="card"><div class="step-no">3</div><h3>We collect weeknights</h3><p>The can goes out <?= e($setout) ?>, with the bagged trash inside it. Never put a loose bag on the ground. Porters haul it <?= e($pickup) ?>. They never enter the apartment.</p></article>
      <article class="card"><div class="step-no">4</div><h3>One remittance</h3><p>On the 1st we send the roster. By the 10th you remit, minus the share we agreed.</p></article>
    </div>
    <?php include LVT_ROOT . '/templates/partials/setout-callout.php'; ?>
    <p><a href="/how-it-works">Set-out rules, holidays, and missed pickups</a></p>
  </div>
</section>

<section class="section section-alt">
  <div class="wrap grid-2">
    <div>
      <h2>What the property gets</h2>
      <ul class="rule-list">
        <li>Cleaner breezeways, grounds, and dumpster areas. Fewer bags dropped beside the compactor.</li>
        <li>A short set-out window, so trash is not sitting out overnight.</li>
        <li>An amenity you can point to on a tour and at renewal.</li>
        <li>Friday-night service, before the weekend starts.</li>
        <li>A revenue share. The starting point we model is <?= e($share) ?>% of each opt-in fee. You can also buy it community-wide and set your own resident fee.</li>
        <li>Missed-pickup reports handled the same day, plus a short morning note for the office.</li>
      </ul>
    </div>
    <div class="card">
      <h3>Revenue-share estimate</h3>
      <p class="muted">If this many residents opt in. It is not a promise of occupancy or opt-in.</p>
      <form class="calc" data-calculator data-price="<?= e((string) ((int) $settings['opt_in_price_cents'] / 100)) ?>">
        <div class="field">
          <label for="calc-units">Units</label>
          <input id="calc-units" name="units" type="number" min="0" max="5000" value="200" inputmode="numeric">
        </div>
        <div class="field">
          <label for="calc-rate">Opt-in %</label>
          <input id="calc-rate" name="opt_in" type="number" min="0" max="100" value="30" inputmode="decimal">
        </div>
        <div class="field">
          <label for="calc-share">Property share %</label>
          <input id="calc-share" name="share" type="number" min="0" max="100" value="<?= e($share) ?>" inputmode="decimal">
        </div>
        <div class="calc-result">
          <p>About <strong data-out="doors">0</strong> doors</p>
          <p>Residents would pay <strong data-out="residents">$0</strong> a month.</p>
          <p>Property keeps <strong data-out="keep">$0</strong>.</p>
          <p>Remitted to Loaded Valet Trash <strong data-out="remit">$0</strong>.</p>
        </div>
        <noscript><p>Without JavaScript: doors = units × opt-in rate. Resident total = doors × <?= e($price) ?>. Property keeps its share. The rest is the remittance.</p></noscript>
      </form>
    </div>
  </div>
</section>

<section class="section">
  <div class="wrap">
    <div class="section-head">
      <h2>Who we serve</h2>
      <p>Weeknight doorstep routes for communities, plus cleanup after events and move-outs.</p>
    </div>
    <div class="grid-3">
      <article class="card"><h3>Apartments</h3><p>Garden and mid-rise communities that want cleaner breezeways without staffing a trash run. Residents opt in through the leasing office.</p></article>
      <article class="card"><h3>Property management</h3><p>One local operator across several Valdosta communities, with a roster your offices can bill from.</p></article>
      <article class="card"><h3>HOAs and townhomes</h3><p>Shared dumpsters, private drives, and a board that wants a written set of can rules.</p></article>
      <article class="card"><h3>Student housing</h3><p>Weeknight pickup, including Friday, when the dumpster trip is the last thing anyone wants to do.</p></article>
      <article class="card"><h3>Senior living</h3><p>Doorstep service for residents who should not be hauling a can across a property at night.</p></article>
      <article class="card"><h3>Short-term rentals</h3><p>Hosts with a cluster of doors who need a recurring weeknight pickup. Ask for a quote.</p></article>
      <article class="card">
        <h3>Party and event cleanup</h3>
        <p>Cleanup after parties and events, for residents, for properties and clubhouses, and for private events.</p>
        <p class="service-price">Quoted per event</p>
        <a class="btn btn-ghost" href="/contact?intent=event_cleanup">Request a quote</a>
      </article>
      <article class="card">
        <h3>Move-outs</h3>
        <p>Move-out cleanout and trash haul-off for leasing offices, apartment complexes, and homes.</p>
        <p class="service-price">Quoted per job</p>
        <a class="btn btn-ghost" href="/contact?intent=move_out">Request a quote</a>
      </article>
    </div>
  </div>
</section>

<section class="section section-alt" id="pricing">
  <div class="wrap">
    <div class="section-head">
      <h2>Two ways to buy</h2>
    </div>
    <div class="grid-2">
      <article class="card price-card featured">
        <h3>Opt-in add-on</h3>
        <p class="price"><?= e($price) ?> <small>/ door / month</small></p>
        <p>Only residents who sign up. The office keeps the agreed share and remits the rest. No separate vendor invoice.</p>
        <a class="btn btn-primary" href="/contact">Request a proposal</a>
      </article>
      <article class="card price-card">
        <h3>Community-wide</h3>
        <p class="price">Quote</p>
        <p>A flat per-door price, or one flat monthly amount, for every unit. You decide what residents pay. Contact us for a community-wide quote.</p>
        <a class="btn btn-ghost" href="/contact">Ask for a quote</a>
      </article>
    </div>
  </div>
</section>

<section class="section">
  <div class="wrap">
    <div class="section-head">
      <h2>Reviews</h2>
      <p>Only reviews we have approved. Nothing here is invented.</p>
    </div>
    <?php if (empty($reviews)): ?>
      <div class="reviews-empty">
        <h3>No published reviews yet</h3>
        <p>When a community manager or staff member writes about the service, and we approve it, it will show here. We do not post sample quotes.</p>
        <a class="btn btn-ghost" href="/reviews/new">Share a firsthand review</a>
      </div>
    <?php else: ?>
      <div class="grid-2">
        <?php foreach ($reviews as $review): ?>
          <blockquote class="card">
            <p class="stars" aria-label="<?= (int) $review['rating'] ?> out of 5"><?= str_repeat('★', (int) $review['rating']) ?><?= str_repeat('☆', 5 - (int) $review['rating']) ?></p>
            <p><?= nl((string) $review['body']) ?></p>
            <footer><strong><?= e((string) $review['reviewer_name']) ?></strong>, <?= e((string) $review['role_title']) ?> · <?= e((string) $review['property_name']) ?></footer>
          </blockquote>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<section class="section section-alt">
  <div class="wrap faq">
    <div class="section-head"><h2>Questions offices ask</h2></div>
    <?php foreach ($faqs as $faq): ?>
      <details>
        <summary><?= e($faq['q']) ?></summary>
        <p><?= e($faq['a']) ?></p>
      </details>
    <?php endforeach; ?>
    <p><a href="/faq">All questions</a></p>
  </div>
</section>

<section class="band">
  <div class="wrap">
    <h2>Walk the property with us.</h2>
    <p>Ten minutes on site is enough to set the route, the compactor stop, and which buildings to start with. The 30-day trial is free on the buildings you pick.</p>
    <div class="cta-row">
      <a class="btn btn-primary" href="/contact?intent=walkthrough">Book a walkthrough</a>
      <a class="btn btn-ghost" href="/contact">Request a proposal</a>
    </div>
  </div>
</section>
