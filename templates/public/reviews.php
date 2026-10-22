<section class="section">
  <div class="wrap">
    <p class="eyebrow">From the office</p>
    <h1>Reviews</h1>
    <p class="lede">These are written by real people and published only after someone on our team approves them. A new submission does not appear here on its own.</p>
    <?php if (empty($reviews)): ?>
      <div class="reviews-empty">
        <h2>No published reviews yet</h2>
        <p>We are not filling this page with sample quotes, stock logos, or a “trusted by” row. If you manage a community we serve, you can send a firsthand note. We will read it before it goes live.</p>
        <a class="btn btn-primary" href="/reviews/new">Share a firsthand review</a>
      </div>
    <?php else: ?>
      <div class="grid-2">
        <?php foreach ($reviews as $review): ?>
          <blockquote class="card">
            <p class="stars" aria-label="<?= (int) $review['rating'] ?> out of 5"><?= str_repeat('★', (int) $review['rating']) ?><?= str_repeat('☆', 5 - (int) $review['rating']) ?></p>
            <p><?= nl((string) $review['body']) ?></p>
            <footer><strong><?= e((string) $review['reviewer_name']) ?></strong>, <?= e((string) $review['role_title']) ?><br><?= e((string) $review['property_name']) ?></footer>
          </blockquote>
        <?php endforeach; ?>
      </div>
      <p><a class="btn btn-ghost" href="/reviews/new">Share a firsthand review</a></p>
    <?php endif; ?>
  </div>
</section>
