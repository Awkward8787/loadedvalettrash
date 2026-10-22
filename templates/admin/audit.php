<h1>Audit log</h1>
<div class="table-wrap">
  <table>
    <thead><tr><th>When</th><th>Who</th><th>Action</th><th>Detail</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $row): ?>
      <tr>
        <td><?= e((string) $row['created_at']) ?></td>
        <td><?= e((string) ($row['user_name'] ?: 'Public')) ?></td>
        <td><?= e((string) $row['action']) ?></td>
        <td><?= e((string) ($row['details'] ?? '')) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
