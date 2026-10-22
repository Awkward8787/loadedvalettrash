<h1>Schedule</h1>
<p><a href="/admin/schedule/calendar">Calendar</a></p>
<h2>Upcoming nights</h2>
<div class="stack">
  <?php if ($runs === []): ?><p class="card">No upcoming routes.</p><?php endif; ?>
  <?php foreach ($runs as $run): ?>
    <article class="card">
      <h3><a href="/admin/schedule/<?= (int) $run['id'] ?>"><?= e((string) $run['service_date']) ?></a></h3>
      <p><?= e((string) ($run['porter_name'] ?: 'Unassigned')) ?> · <?= e(clock_label((string) $run['start_time'])) ?> · <?= e((string) $run['status']) ?></p>
      <a href="/admin/tonight?run=<?= (int) $run['id'] ?>&date=<?= e((string) $run['service_date']) ?>">Open in the field view</a>
    </article>
  <?php endforeach; ?>
</div>
<form class="form card" method="post" action="/admin/schedule/runs">
  <?= csrf_field() ?>
  <h2>Build one night</h2>
  <input type="date" name="service_date" required>
  <select name="porter_id">
    <option value="0">Porter</option>
    <?php foreach ($porters as $porter): ?><option value="<?= (int) $porter['id'] ?>"><?= e((string) $porter['name']) ?></option><?php endforeach; ?>
  </select>
  <input type="time" name="start_time" value="20:00">
  <?php for ($i = 1; $i <= 6; $i++): ?>
    <label>Stop <?= $i ?>
      <select name="property_ids[]">
        <option value="0">None</option>
        <?php foreach ($properties as $property): ?><option value="<?= (int) $property['id'] ?>"><?= e((string) $property['name']) ?></option><?php endforeach; ?>
      </select>
    </label>
  <?php endfor; ?>
  <button class="btn btn-primary" type="submit">Build route</button>
</form>
<form class="form card" method="post" action="/admin/schedule/templates">
  <?= csrf_field() ?>
  <h2>Weekly template</h2>
  <input name="name" placeholder="Template name" required>
  <select name="day_of_week">
    <?php foreach ([1=>'Monday',2=>'Tuesday',3=>'Wednesday',4=>'Thursday',5=>'Friday'] as $n => $label): ?>
      <option value="<?= $n ?>"><?= e($label) ?></option>
    <?php endforeach; ?>
  </select>
  <select name="porter_id">
    <option value="0">Porter</option>
    <?php foreach ($porters as $porter): ?><option value="<?= (int) $porter['id'] ?>"><?= e((string) $porter['name']) ?></option><?php endforeach; ?>
  </select>
  <input type="time" name="start_time" value="20:00">
  <?php for ($i = 1; $i <= 4; $i++): ?>
    <select name="property_ids[]">
      <option value="0">Stop <?= $i ?></option>
      <?php foreach ($properties as $property): ?><option value="<?= (int) $property['id'] ?>"><?= e((string) $property['name']) ?></option><?php endforeach; ?>
    </select>
  <?php endfor; ?>
  <button class="btn btn-ghost" type="submit">Save weekly template</button>
</form>
<?php if ($templates !== []): ?>
  <section class="card">
    <h2>Templates</h2>
    <ul>
      <?php foreach ($templates as $template): ?>
        <li><?= e((string) $template['name']) ?> · day <?= (int) $template['day_of_week'] ?> · <?= e((string) ($template['porter_name'] ?: 'unassigned')) ?></li>
      <?php endforeach; ?>
    </ul>
    <form method="post" action="/admin/schedule/generate">
      <?= csrf_field() ?>
      <label>Weeks ahead <input name="weeks" type="number" min="1" max="8" value="4"></label>
      <button class="btn btn-primary" type="submit">Generate nights</button>
    </form>
  </section>
<?php endif; ?>
