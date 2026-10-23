<h1>Tonight</h1>
<p class="muted"><?= e((string) $date) ?>. Check a building when every door is done, or mark a single door.</p>
<p class="warning"><strong>Set-out.</strong> Put your trash can outside your door with the bagged trash inside it. Never put a loose bag on the ground. A second bag belongs on top of the can. Loose bags on the ground: report to the office. Mark the violation "loose bag (not in/on can)" and leave the bag.</p>
<?php if (empty($run)): ?>
  <div class="card">
    <h2>No route assigned</h2>
    <p>Nothing is on your board for this night. If you are covering for someone, an owner can reassign the route.</p>
  </div>
<?php else: ?>
  <form class="actions" method="post" action="/admin/tonight/clock">
    <?= csrf_field() ?>
    <input type="hidden" name="route_run_id" value="<?= (int) $run['id'] ?>">
    <?php if (empty($openClock)): ?>
      <button class="btn btn-primary" name="action" value="in" type="submit">Clock in</button>
    <?php else: ?>
      <button class="btn btn-primary" name="action" value="out" type="submit">Clock out</button>
      <span class="muted">In since <?= e((string) $openClock['clock_in']) ?></span>
    <?php endif; ?>
  </form>
  <?php if (count($runs) > 1): ?>
    <p>Other routes:
      <?php foreach ($runs as $other): ?>
        <?php if ((int) $other['id'] === (int) $run['id']) continue; ?>
        <a href="/admin/tonight?run=<?= (int) $other['id'] ?>&date=<?= e((string) $date) ?>">#<?= (int) $other['id'] ?></a>
      <?php endforeach; ?>
    </p>
  <?php endif; ?>
  <?php foreach ($stops as $stop): ?>
    <?php $address = property_address($stop); ?>
    <section class="check-row" id="stop-<?= (int) $stop['id'] ?>">
      <h2><?= e((string) $stop['property_name']) ?><?php if (filled($stop['building_name'] ?? null)): ?> · <?= e((string) $stop['building_name']) ?><?php endif; ?></h2>
      <p><span class="badge"><?= e((string) $stop['status']) ?></span></p>
      <?php if ($address !== ''): ?><p><a href="<?= e(maps_search($address)) ?>">Maps</a></p><?php endif; ?>
      <?php if (filled($stop['compactor_location'] ?? null)): ?><p>Compactor: <?= e((string) $stop['compactor_location']) ?></p><?php endif; ?>
      <?php if (!empty($stop['show_gate']) && filled($stop['gate_access_notes'] ?? null)): ?>
        <p class="warning">Access: <?= nl((string) $stop['gate_access_notes']) ?></p>
      <?php endif; ?>
      <?php if (filled($stop['hazards_notes'] ?? null)): ?><p>Hazards: <?= e((string) $stop['hazards_notes']) ?></p><?php endif; ?>
      <?php if ((int) ($stop['compactor_trip_after'] ?? 0) === 1): ?><p>Compactor trip after this stop.</p><?php endif; ?>
      <form method="post" action="/admin/tonight/stops/<?= (int) $stop['id'] ?>">
        <?= csrf_field() ?>
        <button class="btn btn-primary btn-block" name="action" value="done" type="submit">All remaining doors picked up</button>
      </form>
      <?php if (empty($stop['doors'])): ?>
        <p class="muted">No opted-in doors on this stop.</p>
      <?php endif; ?>
      <?php foreach ($stop['doors'] as $door): ?>
        <div class="door">
          <strong>Unit <?= e((string) $door['unit_label']) ?></strong>
          <span class="badge"><?= e(str_replace('_', ' ', (string) $door['status'])) ?></span>
          <form class="stack" method="post" action="/admin/tonight/doors/<?= (int) $door['id'] ?>" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <label for="note-<?= (int) $door['id'] ?>">Note</label>
            <input id="note-<?= (int) $door['id'] ?>" name="notes" value="<?= e((string) ($door['notes'] ?? '')) ?>">
            <label for="violation-<?= (int) $door['id'] ?>">Violation type, if any</label>
            <select id="violation-<?= (int) $door['id'] ?>" name="violation_kind">
              <option value="">None</option>
              <?php foreach ([
                  'loose_bag' => 'loose bag (not in/on can)',
                  'leaking' => 'leaking',
                  'overweight' => 'overweight',
                  'too_many' => 'too many',
                  'hazardous' => 'hazardous',
                  'wrong_time' => 'wrong time',
                  'other' => 'other',
              ] as $kind => $label): ?>
                <option value="<?= e($kind) ?>"><?= e($label) ?></option>
              <?php endforeach; ?>
            </select>
            <label>Photo <input type="file" name="photo" accept="image/*" capture="environment"></label>
            <div class="actions">
              <button class="btn btn-primary btn-small" name="status" value="picked_up" type="submit">Picked up</button>
              <button class="btn btn-ghost btn-small" name="status" value="missed" type="submit">Missed</button>
              <button class="btn btn-ghost btn-small" name="status" value="violation" type="submit">Violation</button>
            </div>
          </form>
        </div>
      <?php endforeach; ?>
    </section>
  <?php endforeach; ?>
<?php endif; ?>
