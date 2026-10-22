<section class="section">
  <div class="wrap">
    <p class="eyebrow">Communities</p>
    <h1>Who we serve</h1>
    <p class="lede">Loaded Valet Trash is built for multifamily and community living in the Valdosta area. If your layout is a set of doors and a dumpster, we can usually build a route.</p>
    <div class="grid-2">
      <article class="card"><h2>Apartment communities</h2><p>The core route. Opt-in residents, or every door if you would rather buy it community-wide. We walk the buildings in daylight before the first night so the order ends at the compactor.</p></article>
      <article class="card"><h2>Property management companies</h2><p>Group communities under one management company in our office records. Each property still gets its own roster, invoice, and weekly issue report.</p></article>
      <article class="card"><h2>HOAs and townhomes</h2><p>Shared compactors, private streets, and a board packet. The bag rules are written so you can attach them to the community guidelines.</p></article>
      <article class="card"><h2>Student housing</h2><p>Friday night is part of the schedule on purpose. Bags go out before the weekend instead of sitting until Monday.</p></article>
      <article class="card"><h2>Senior living</h2><p>Doorstep pickup for residents who should not be crossing a property with a trash bag after dark. Porters stay outside the unit.</p></article>
      <article class="card"><h2>Short-term rental hosts</h2><p>A cluster of homes or a small community of rentals can take a quoted route. Tell us the door count and where the dumpster is.</p></article>
    </div>
    <?php if (filled($settings['service_area'] ?? null)): ?>
      <p>Service area: <?= e((string) $settings['service_area']) ?></p>
    <?php endif; ?>
    <a class="btn btn-primary" href="/contact">Tell us about the property</a>
  </div>
</section>
