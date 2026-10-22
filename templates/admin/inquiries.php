<h1>Proposal inbox</h1>
<?php if ($inquiries === []): ?><p class="card">No requests yet.</p><?php endif; ?>
<?php foreach ($inquiries as $inquiry): ?>
  <article class="card">
    <p><span class="badge"><?= e((string) $inquiry['status']) ?></span> <?= e((string) $inquiry['kind']) ?></p>
    <h2><?= e((string) $inquiry['property_name']) ?></h2>
    <p><?= e((string) $inquiry['contact_name']) ?> · <?= (int) ($inquiry['units_count'] ?? 0) ?> units</p>
    <p><?= e((string) ($inquiry['contact_email'] ?? '')) ?> <?= e((string) ($inquiry['contact_phone'] ?? '')) ?></p>
    <?php if (filled($inquiry['message'] ?? null)): ?><p><?= nl((string) $inquiry['message']) ?></p><?php endif; ?>
    <?php if (!empty($inquiry['property_id'])): ?><p><a href="/admin/properties/<?= (int) $inquiry['property_id'] ?>">Open the property</a></p><?php endif; ?>
    <form method="post" action="/admin/inquiries/<?= (int) $inquiry['id'] ?>">
      <?= csrf_field() ?>
      <select name="status">
        <?php foreach (['new','contacted','converted','closed'] as $status): ?>
          <option value="<?= e($status) ?>"<?= $inquiry['status'] === $status ? ' selected' : '' ?>><?= e($status) ?></option>
        <?php endforeach; ?>
      </select>
      <button class="btn btn-ghost btn-small" type="submit">Update</button>
    </form>
  </article>
<?php endforeach; ?>
