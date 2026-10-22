<section class="section">
  <div class="wrap faq">
    <p class="eyebrow">FAQ</p>
    <h1>Questions from leasing offices</h1>
    <?php foreach ($faqs as $faq): ?>
      <details>
        <summary><?= e($faq['q']) ?></summary>
        <p><?= e($faq['a']) ?></p>
      </details>
    <?php endforeach; ?>
    <p><a class="btn btn-primary" href="/contact">Ask about your property</a></p>
  </div>
</section>
