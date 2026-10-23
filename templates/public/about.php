<section class="section">
  <div class="wrap">
    <p class="eyebrow">The company</p>
    <h1>Locally owned in Valdosta.</h1>
    <p class="lede">Loaded Valet Trash is owned by Anthony Jaamal Jones, who goes by Dondon. The weeknight work is doorstep valet: put your trash can outside your door with the bagged trash inside it, and porters take it to the property’s compactor. Never put a loose bag on the ground. We also clean up after parties and events, quoted per event, and we do move-out cleanout and trash haul-off, quoted per job.</p>
    <div class="grid-2">
      <article class="card">
        <h2>What we do</h2>
        <p>Doorstep valet runs Monday through Friday, including Friday night. Residents opt in through the leasing office, and the office bills it as a rent add-on. Porters wear a vest, stay outside the apartment, and leave a written notice when a set-out does not meet the rules. The office gets a roster for billing and a record of misses and violations.</p>
        <p>Party and event cleanup is for residents, properties and clubhouses, and private events. Move-out cleanout and trash haul-off is for leasing offices, apartment complexes, and homes. <a href="/services">See the three services</a>.</p>
      </article>
      <article class="card">
        <h2>What we will not pretend</h2>
        <p>This site does not list years in business, a license number, a coverage limit, or a phone number unless those details have been entered by the owner. A certificate is provided before service starts. This page does not name a property as an additional insured. Empty fields stay off the page. Reviews appear only after they are approved, and the page starts empty on purpose.</p>
        <?php if (filled($settings['insurance_text'] ?? null)): ?>
          <h3>Certificate note</h3>
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
