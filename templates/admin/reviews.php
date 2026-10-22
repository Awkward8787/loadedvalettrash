<h1>Reviews</h1>
<p>
  <a href="/admin/reviews?status=pending">Pending</a> ·
  <a href="/admin/reviews?status=approved">Approved</a> ·
  <a href="/admin/reviews?status=rejected">Rejected</a>
</p>
<p class="muted">Approved reviews are the only ones on the public site. Do not approve something you did not receive.</p>
<?php if ($reviews === []): ?><p class="card">No reviews in this queue.</p><?php endif; ?>
<?php foreach ($reviews as $review): ?>
  <article class="card">
    <p><span class="badge"><?= e((string) $review['status']) ?></span> <?= (int) $review['rating'] ?> / 5</p>
    <h2><?= e((string) $review['reviewer_name']) ?></h2>
    <p><?= e((string) $review['role_title']) ?> · <?= e((string) $review['property_name']) ?></p>
    <p><?= nl((string) $review['body']) ?></p>
    <form class="actions" method="post" action="/admin/reviews/<?= (int) $review['id'] ?>">
      <?= csrf_field() ?>
      <button class="btn btn-primary btn-small" name="decision" value="approved" type="submit">Approve</button>
      <button class="btn btn-ghost btn-small" name="decision" value="rejected" type="submit">Reject</button>
    </form>
  </article>
<?php endforeach; ?>
