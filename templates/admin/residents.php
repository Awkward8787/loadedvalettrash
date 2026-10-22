<p><a href="/admin/properties/<?= (int) $property['id'] ?>">Back to <?= e((string) $property['name']) ?></a></p>
<h1>Roster</h1>
<p><?= (int) $billable ?> billable in <?= e(sprintf('%04d-%02d', (int) $year, (int) $month)) ?>.</p>
<p><a href="/admin/properties/<?= (int) $property['id'] ?>/residents.csv?year=<?= (int) $year ?>&month=<?= (int) $month ?>">Download this month for the leasing office</a> · <a href="/admin/residents-template.csv">Import template</a></p>
<form method="get" class="form" action="/admin/properties/<?= (int) $property['id'] ?>/residents">
  <div class="grid-2">
    <div class="field"><label for="month">Month</label><input id="month" name="month" type="number" min="1" max="12" value="<?= (int) $month ?>"></div>
    <div class="field"><label for="year">Year</label><input id="year" name="year" type="number" value="<?= (int) $year ?>"></div>
  </div>
  <button class="btn btn-ghost" type="submit">Show month</button>
</form>
<form class="form card" method="post" action="/admin/properties/<?= (int) $property['id'] ?>/residents/import" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <label for="csv">Import CSV</label>
  <input id="csv" type="file" name="csv" accept=".csv,text/csv" required>
  <button class="btn btn-primary" type="submit">Import</button>
</form>
<?php foreach ($residents as $resident): ?>
  <form class="form card" method="post" action="/admin/properties/<?= (int) $property['id'] ?>/residents/<?= (int) $resident['id'] ?>">
    <?= csrf_field() ?>
    <div class="grid-2">
      <div class="field"><label>Unit</label><input name="unit_number" value="<?= e((string) $resident['unit_number']) ?>"></div>
      <div class="field"><label>Name</label><input name="name" value="<?= e((string) ($resident['name'] ?? '')) ?>"></div>
      <div class="field">
        <label>Building</label>
        <select name="building_id">
          <option value="0">Unassigned</option>
          <?php foreach ($buildings as $building): ?>
            <option value="<?= (int) $building['id'] ?>"<?= (int) $resident['building_id'] === (int) $building['id'] ? ' selected' : '' ?>><?= e((string) $building['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label>Status</label>
        <select name="status">
          <?php foreach (['pending','active','cancelled'] as $status): ?>
            <option value="<?= e($status) ?>"<?= $resident['status'] === $status ? ' selected' : '' ?>><?= e($status) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field"><label>Start</label><input type="date" name="start_date" value="<?= e((string) ($resident['start_date'] ?? '')) ?>"></div>
      <div class="field"><label>Cancel</label><input type="date" name="cancel_date" value="<?= e((string) ($resident['cancel_date'] ?? '')) ?>"></div>
    </div>
    <input name="notes" value="<?= e((string) ($resident['notes'] ?? '')) ?>" placeholder="Notes">
    <button class="btn btn-ghost btn-small" type="submit">Save</button>
  </form>
<?php endforeach; ?>
<form class="form card" method="post" action="/admin/properties/<?= (int) $property['id'] ?>/residents">
  <?= csrf_field() ?>
  <h2>Add a door</h2>
  <input name="unit_number" placeholder="Unit" required>
  <input name="name" placeholder="Resident name">
  <select name="building_id">
    <option value="0">Building</option>
    <?php foreach ($buildings as $building): ?>
      <option value="<?= (int) $building['id'] ?>"><?= e((string) $building['name']) ?></option>
    <?php endforeach; ?>
  </select>
  <select name="status">
    <option value="active">active</option>
    <option value="pending">pending</option>
    <option value="cancelled">cancelled</option>
  </select>
  <input type="date" name="start_date">
  <input type="date" name="cancel_date">
  <button class="btn btn-primary" type="submit">Add resident</button>
</form>
