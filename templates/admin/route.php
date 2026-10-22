<h1><?= e((string) $run['service_date']) ?></h1>
<p><?= (int) $minutes ?> minutes estimated · <?= (int) $portersNeeded ?> porter<?= (int) $portersNeeded === 1 ? '' : 's' ?> to finish inside <?= (int) $window ?> minutes · one porter would finish around <?= e((string) $finish) ?>.</p>
<?php if (!empty($over)): ?><p class="warning">This is longer than the pickup window for one porter. Split buildings between porters or agree a later window.</p><?php endif; ?>
<?php if (!empty($directions)): ?><p><a href="<?= e((string) $directions) ?>">Open the route in maps</a></p><?php endif; ?>
<form class="form card" method="post" action="/admin/schedule/<?= (int) $run['id'] ?>">
  <?= csrf_field() ?>
  <label>Porter
    <select name="porter_id">
      <option value="0">Unassigned</option>
      <?php foreach ($porters as $porter): ?>
        <option value="<?= (int) $porter['id'] ?>"<?= (int) $run['porter_id'] === (int) $porter['id'] ? ' selected' : '' ?>><?= e((string) $porter['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <label>Start <input type="time" name="start_time" value="<?= e(substr((string) $run['start_time'], 0, 5)) ?>"></label>
  <label>Status
    <select name="status">
      <?php foreach (['scheduled','in_progress','complete','cancelled'] as $status): ?>
        <option value="<?= e($status) ?>"<?= $run['status'] === $status ? ' selected' : '' ?>><?= e($status) ?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <input name="notes" value="<?= e((string) ($run['notes'] ?? '')) ?>" placeholder="Notes">
  <button class="btn btn-primary" type="submit">Save route</button>
</form>
<ol class="stack">
  <?php foreach ($stops as $stop): ?>
    <?php $address = property_address($stop); ?>
    <li class="card" id="stop-<?= (int) $stop['id'] ?>">
      <strong><?= e((string) $stop['property_name']) ?></strong>
      <?php if (filled($stop['building_name'] ?? null)): ?> · <?= e((string) $stop['building_name']) ?><?php endif; ?>
      <p><?= e((string) $stop['status']) ?></p>
      <?php if ($address !== ''): ?><p><a href="<?= e(maps_search($address)) ?>">Open this stop in maps</a><br><?= e($address) ?></p><?php endif; ?>
      <?php if (filled($stop['compactor_location'] ?? null)): ?><p>Compactor: <?= e((string) $stop['compactor_location']) ?></p><?php endif; ?>
    </li>
  <?php endforeach; ?>
</ol>
<p><a class="btn btn-primary" href="/admin/tonight?run=<?= (int) $run['id'] ?>&date=<?= e((string) $run['service_date']) ?>">Field view</a></p>
