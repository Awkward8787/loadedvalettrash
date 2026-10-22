<section class="section">
  <div class="wrap">
    <p class="eyebrow">The company</p>
    <h1>Locally owned in Valdosta.</h1>
    <p class="lede">Loaded Valet Trash is owned by Anthony Jaamal Jones, who goes by Dondon. The work is simple: pick up bagged trash outside apartment doors on weeknights and take it to the property’s compactor.</p>
    <div class="grid-2">
      <article class="card">
        <h2>What we do</h2>
        <p>We run Monday through Friday, including Friday night. Porters wear a vest, stay outside the apartment, and leave a written notice when a bag does not meet the rules. The office gets a roster for billing and a record of misses and violations.</p>
      </article>
      <article class="card">
        <h2>What we will not pretend</h2>
        <p>This site does not list years in business, a license number, insurance limits, or a phone number unless those details have been entered by the owner. Empty fields stay off the page. Reviews appear only after they are approved, and the page starts empty on purpose.</p>
        <?php if (filled($settings['insurance_text'] ?? null)): ?>
          <h3>Insurance</h3>
          <p><?= nl((string) $settings['insurance_text']) ?></p>
        <?php endif; ?>
      </article>
    </div>
    <?php if (filled($settings['service_area'] ?? null)): ?>
      <p>We are set up to serve <?= e((string) $settings['service_area']) ?>.</p>
    <?php endif; ?>
    <a class="btn btn-primary" href="/contact">Request a proposal</a>
  </div>
</section>
