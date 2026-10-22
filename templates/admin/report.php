<h1>Weekly report</h1>
<form class="form no-print" method="get" action="/admin/issues/report">
  <label>Property
    <select name="property_id" required>
      <option value="">Choose</option>
      <?php foreach ($properties as $row): ?>
        <option value="<?= (int) $row['id'] ?>"<?= !empty($property) && (int) $property['id'] === (int) $row['id'] ? ' selected' : '' ?>><?= e((string) $row['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <label>Week starting <input type="date" name="start" value="<?= e((string) $start) ?>"></label>
  <button class="btn btn-primary" type="submit">Show</button>
</form>
<?php if (!empty($property)): ?>
  <article class="card">
    <h2><?= e((string) $property['name']) ?></h2>
    <p><?= e((string) $start) ?> through <?= e((string) $end) ?></p>
    <?php if ($issues === []): ?>
      <p>No missed pickups or violations were logged this week.</p>
    <?php else: ?>
      <table>
        <thead><tr><th>When</th><th>Type</th><th>Unit</th><th>Status</th><th>Note</th><th>Photo</th></tr></thead>
        <tbody>
        <?php foreach ($issues as $issue): ?>
          <tr>
            <td><?= e((string) $issue['created_at']) ?></td>
            <td><?= e((string) $issue['type']) ?></td>
            <td><?= e((string) ($issue['unit_label'] ?? '')) ?></td>
            <td><?= e((string) $issue['status']) ?></td>
            <td><?= e((string) ($issue['description'] ?? '')) ?></td>
            <td><?= filled($issue['photo_path'] ?? null) ? 'Yes' : 'No' ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
    <p class="no-print"><a class="btn btn-ghost" href="/admin/issues/report.pdf?property_id=<?= (int) $property['id'] ?>&start=<?= e((string) $start) ?>">Download PDF</a>
    <button class="btn btn-primary" type="button" data-print>Print</button></p>
  </article>
<?php endif; ?>
