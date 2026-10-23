<h1><?= e(ucfirst(str_replace('_', ' ', (string) $issue['type']))) ?></h1>
<p><?= e((string) ($issue['property_name'] ?: $issue['external_property_name'] ?: 'No property linked')) ?> · unit <?= e((string) ($issue['unit_label'] ?: 'n/a')) ?></p>
<p>Reported by <?= e((string) ($issue['reporter_name'] ?? '')) ?> <?= e((string) ($issue['reporter_contact'] ?? '')) ?> · <?= e((string) ($issue['reporter_role'] ?? '')) ?></p>
<?php if (filled($issue['photo_path'] ?? null)): ?>
  <p><img src="/admin/issues/<?= (int) $issue['id'] ?>/photo" alt="Photo attached to this issue" width="1200" height="900" loading="lazy"></p>
<?php endif; ?>
<form class="form card" method="post" action="/admin/issues/<?= (int) $issue['id'] ?>">
  <?= csrf_field() ?>
  <label>Property
    <select name="property_id">
      <option value="0">Unlinked</option>
      <?php foreach ($properties as $property): ?>
        <option value="<?= (int) $property['id'] ?>"<?= (int) $issue['property_id'] === (int) $property['id'] ? ' selected' : '' ?>><?= e((string) $property['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <label>Status
    <select name="status">
      <?php foreach (['open','scheduled_return','resolved'] as $status): ?>
        <option value="<?= e($status) ?>"<?= $issue['status'] === $status ? ' selected' : '' ?>><?= e(str_replace('_', ' ', $status)) ?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <textarea name="description"><?= e((string) ($issue['description'] ?? '')) ?></textarea>
  <button class="btn btn-primary" type="submit">Save</button>
</form>
