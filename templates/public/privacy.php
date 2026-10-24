<section class="section">
  <div class="wrap">
    <p class="eyebrow">Privacy</p>
    <h1>Privacy policy</h1>
    <p>This policy covers the Loaded Valet Trash website and the doorstep pickup service operated by Anthony Jaamal Jones. It is written in plain language. It is not a substitute for advice from your own lawyer.</p>
    <h2>Who we are</h2>
    <p>Loaded Valet Trash picks up bagged household trash from the can outside the door and carries it to the property’s compactor or dumpster. Residents put the trash can outside the door with the bagged trash inside it. The public site is for property managers, and the missed-pickup form is also for residents. Party cleanup and move-out requests use the same contact form.</p>
    <h2>What the website collects</h2>
    <ul class="rule-list">
      <li>Proposal, walkthrough, event-cleanup, and move-out requests: property or location name, unit count, your name, email or phone, and whatever you write in the message.</li>
      <li>Missed-pickup reports: property, unit, your name, a way to reach you, and a description.</li>
      <li>Review submissions: your name, property, role, rating, and review text. A review is not shown publicly until it is approved.</li>
      <li>A session cookie used to protect forms from cross-site submissions. Staff sign-in uses a separate session cookie. We do not run advertising trackers on this site.</li>
    </ul>
    <h2>Job applications</h2>
    <p>When you apply for a job, we collect your name, phone number, and city. Email is optional. We also collect whether you are 18 or older, your availability, the date you can start, and the IP address of the submission for spam protection.</p>
    <p>We use this information to review applications and to contact applicants about jobs.</p>
    <p>Only the owner and managers of Loaded Valet Trash can see applications. We do not sell them and we do not share them.</p>
    <p>We keep applications for 1 year and then delete them. You can ask us to delete yours sooner by calling <a href="tel:+12295958852">(229) 595-8852</a>.</p>
    <h2>What the service uses</h2>
    <p>When a property hires us, the leasing office gives us the opted-in roster: unit, resident name, and start or cancel dates. We use that list to know which doors to stop at and to build the monthly billing roster we send back to the office. Porters may photograph a bag and a door number when they leave a violation notice, or when they document a missed door. Those photos are for the property and for our own record. They are not published on this website.</p>
    <h2>Gate and access notes</h2>
    <p>Access notes a property gives us are stored for the people who run that route. They are not included in roster exports and they are not shown on the public site.</p>
    <h2>Who we share information with</h2>
    <p>We send the monthly roster back to the leasing office that gave it to us. We do not sell personal information. Hosting is on the owner’s web host. If a law, a court, or a property’s insurer requires a record, we may provide the record that matches the request.</p>
    <h2>How long we keep it</h2>
    <p>We keep roster, billing, and issue records for as long as the property is a customer and for a reasonable period after, so we can answer a billing question. You can ask us to correct a website submission by using the contact form.</p>
    <h2>Contact</h2>
    <?php if (filled($settings['email'] ?? null) || filled($settings['phone'] ?? null) || filled($settings['address'] ?? null)): ?>
      <p>
        <?php if (filled($settings['email'] ?? null)): ?><?= e((string) $settings['email']) ?><br><?php endif; ?>
        <?php if (filled($settings['phone'] ?? null)): ?><?= e((string) $settings['phone']) ?><br><?php endif; ?>
        <?php if (filled($settings['address'] ?? null)): ?><?= nl((string) $settings['address']) ?><?php endif; ?>
      </p>
    <?php else: ?>
      <p>Use the <a href="/contact">contact form</a>. The message goes to the owner.</p>
    <?php endif; ?>
    <p class="muted">Updated October 24, 2026.</p>
  </div>
</section>
