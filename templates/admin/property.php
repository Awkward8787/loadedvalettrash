<p><a href="/admin/pipeline">Pipeline</a> · <a href="/admin/properties/<?= (int) $property['id'] ?>/residents">Roster</a></p>
<h1><?= e((string) $property['name']) ?></h1>
<p><span class="badge"><?= e((string) $property['stage']) ?></span> Estimate <?= (int) $doorEstimate ?> doors · about <?= (int) $minutes ?> minutes on site.</p>
<form class="form card" method="post" action="/admin/properties/<?= (int) $property['id'] ?>">
  <?= csrf_field() ?>
  <div class="field"><label for="name">Name</label><input id="name" name="name" required value="<?= e((string) $property['name']) ?>"></div>
  <div class="field"><label for="address">Address</label><input id="address" name="address" value="<?= e((string) ($property['address'] ?? '')) ?>"></div>
  <div class="grid-2">
    <div class="field"><label for="city">City</label><input id="city" name="city" value="<?= e((string) ($property['city'] ?? '')) ?>"></div>
    <div class="field"><label for="postal_code">ZIP</label><input id="postal_code" name="postal_code" value="<?= e((string) ($property['postal_code'] ?? '')) ?>"></div>
  </div>
  <div class="field"><label for="state">State</label><input id="state" name="state" maxlength="2" value="<?= e((string) $property['state']) ?>"></div>
  <div class="grid-2">
    <div class="field"><label for="units_count">Units</label><input id="units_count" name="units_count" type="number" min="0" value="<?= (int) $property['units_count'] ?>"></div>
    <div class="field"><label for="planned_doors">Planned doors for estimates</label><input id="planned_doors" name="planned_doors" type="number" min="0" value="<?= e((string) ($property['planned_doors'] ?? '')) ?>"></div>
  </div>
  <div class="field"><label for="website">Website</label><input id="website" name="website" value="<?= e((string) ($property['website'] ?? '')) ?>"></div>
  <div class="field">
    <label for="stage">Stage</label>
    <select id="stage" name="stage">
      <?php foreach ($stages as $stage): ?>
        <option value="<?= e($stage) ?>"<?= $property['stage'] === $stage ? ' selected' : '' ?>><?= e(ucfirst($stage)) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="field"><label for="lost_reason">Lost reason</label><input id="lost_reason" name="lost_reason" value="<?= e((string) ($property['lost_reason'] ?? '')) ?>"></div>
  <div class="field">
    <label for="has_valet_trash">Has valet trash today</label>
    <select id="has_valet_trash" name="has_valet_trash">
      <?php foreach (['unknown','no','yes'] as $flag): ?>
        <option value="<?= e($flag) ?>"<?= $property['has_valet_trash'] === $flag ? ' selected' : '' ?>><?= e($flag) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="field">
    <label for="management_company_id">Management company</label>
    <select id="management_company_id" name="management_company_id">
      <option value="0">None</option>
      <?php foreach ($companies as $company): ?>
        <option value="<?= (int) $company['id'] ?>"<?= (int) $property['management_company_id'] === (int) $company['id'] ? ' selected' : '' ?>><?= e((string) $company['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="field"><label for="company_name">Or add a company by name</label><input id="company_name" name="company_name"></div>
  <div class="field">
    <label for="pricing_model">Pricing model</label>
    <select id="pricing_model" name="pricing_model">
      <?php foreach (['opt_in' => 'Opt-in', 'per_door' => 'Community-wide per door', 'flat' => 'Flat monthly'] as $value => $label): ?>
        <option value="<?= e($value) ?>"<?= $property['pricing_model'] === $value ? ' selected' : '' ?>><?= e($label) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="grid-2">
    <div class="field"><label for="opt_in_price">Opt-in price (blank uses the site default)</label><input id="opt_in_price" name="opt_in_price" value="<?= e(cents_to_dollars($property['opt_in_price_cents'] !== null ? (int) $property['opt_in_price_cents'] : null)) ?>"></div>
    <div class="field"><label for="property_share_percent">Property share %</label><input id="property_share_percent" name="property_share_percent" value="<?= e((string) ($property['property_share_percent'] ?? '')) ?>"></div>
    <div class="field"><label for="per_door_rate">Per-door rate</label><input id="per_door_rate" name="per_door_rate" value="<?= e(cents_to_dollars($property['per_door_rate_cents'] !== null ? (int) $property['per_door_rate_cents'] : null)) ?>"></div>
    <div class="field"><label for="flat_rate">Flat monthly amount</label><input id="flat_rate" name="flat_rate" value="<?= e(cents_to_dollars($property['flat_rate_cents'] !== null ? (int) $property['flat_rate_cents'] : null)) ?>"></div>
  </div>
  <fieldset>
    <legend>Service nights</legend>
    <?php foreach (['mon'=>'Mon','tue'=>'Tue','wed'=>'Wed','thu'=>'Thu','fri'=>'Fri'] as $key => $label): ?>
      <label><input type="checkbox" name="service_days[]" value="<?= e($key) ?>"<?= checked_in((string) $property['service_days'], $key) ?>> <?= e($label) ?></label>
    <?php endforeach; ?>
  </fieldset>
  <div class="grid-2">
    <div class="field"><label for="setout_start">Set-out start</label><input id="setout_start" name="setout_start" type="time" value="<?= e(substr((string) ($property['setout_start'] ?? ''), 0, 5)) ?>"></div>
    <div class="field"><label for="setout_end">Set-out end</label><input id="setout_end" name="setout_end" type="time" value="<?= e(substr((string) ($property['setout_end'] ?? ''), 0, 5)) ?>"></div>
    <div class="field"><label for="pickup_start">Pickup start</label><input id="pickup_start" name="pickup_start" type="time" value="<?= e(substr((string) ($property['pickup_start'] ?? ''), 0, 5)) ?>"></div>
    <div class="field"><label for="pickup_end">Pickup end</label><input id="pickup_end" name="pickup_end" type="time" value="<?= e(substr((string) ($property['pickup_end'] ?? ''), 0, 5)) ?>"></div>
  </div>
  <div class="field"><label for="compactor_location">Compactor location</label><input id="compactor_location" name="compactor_location" value="<?= e((string) ($property['compactor_location'] ?? '')) ?>"></div>
  <?php if (!empty($showGate)): ?>
    <div class="field">
      <label for="gate_access_notes">Gate and access notes</label>
      <textarea id="gate_access_notes" name="gate_access_notes"><?= e((string) ($property['gate_access_notes'] ?? '')) ?></textarea>
      <p class="warning">Visible to admins and the porter assigned to this property. Left out of CSV exports. Do not paste a code you would not want in a photo of this screen.</p>
    </div>
  <?php endif; ?>
  <div class="grid-2">
    <div class="field"><label for="contract_start">Contract start</label><input id="contract_start" type="date" name="contract_start" value="<?= e((string) ($property['contract_start'] ?? '')) ?>"></div>
    <div class="field"><label for="contract_end">Contract end</label><input id="contract_end" type="date" name="contract_end" value="<?= e((string) ($property['contract_end'] ?? '')) ?>"></div>
    <div class="field"><label for="trial_start">Trial start</label><input id="trial_start" type="date" name="trial_start" value="<?= e((string) ($property['trial_start'] ?? '')) ?>"></div>
    <div class="field"><label for="trial_end">Trial end</label><input id="trial_end" type="date" name="trial_end" value="<?= e((string) ($property['trial_end'] ?? '')) ?>"></div>
    <div class="field"><label for="follow_up_on">Follow-up</label><input id="follow_up_on" type="date" name="follow_up_on" value="<?= e((string) ($property['follow_up_on'] ?? '')) ?>"></div>
    <div class="field"><label for="supplies">Supplies / month override</label><input id="supplies" name="supplies" value="<?= e(cents_to_dollars($property['supplies_cents'] !== null ? (int) $property['supplies_cents'] : null)) ?>"></div>
    <div class="field"><label for="gas">Gas / month override</label><input id="gas" name="gas" value="<?= e(cents_to_dollars($property['gas_cents'] !== null ? (int) $property['gas_cents'] : null)) ?>"></div>
  </div>
  <button class="btn btn-primary" type="submit">Save property</button>
</form>

<section class="card">
  <h2>Buildings and route order</h2>
  <p class="muted">Walk the property in daylight. Start farthest from the compactor and end at it.</p>
  <?php foreach ($buildings as $building): ?>
    <form class="form" method="post" action="/admin/properties/<?= (int) $property['id'] ?>/buildings">
      <?= csrf_field() ?>
      <input type="hidden" name="building_id" value="<?= (int) $building['id'] ?>">
      <div class="grid-2">
        <div class="field"><label>Name</label><input name="name" value="<?= e((string) $building['name']) ?>"></div>
        <div class="field"><label>Order</label><input name="sort_order" type="number" value="<?= (int) $building['sort_order'] ?>"></div>
        <div class="field"><label>Floors</label><input name="floors" type="number" value="<?= e((string) ($building['floors'] ?? '')) ?>"></div>
        <div class="field"><label>Units</label><input name="unit_count" type="number" value="<?= e((string) ($building['unit_count'] ?? '')) ?>"></div>
      </div>
      <div class="field"><label>Hazards</label><input name="hazards_notes" value="<?= e((string) ($building['hazards_notes'] ?? '')) ?>"></div>
      <label><input type="checkbox" name="compactor_trip_after" value="1" <?= (int) $building['compactor_trip_after'] === 1 ? 'checked' : '' ?>> Compactor trip after this stop</label>
      <button class="btn btn-ghost btn-small" type="submit">Update building</button>
    </form>
    <form method="post" action="/admin/properties/<?= (int) $property['id'] ?>/buildings">
      <?= csrf_field() ?>
      <input type="hidden" name="delete_building_id" value="<?= (int) $building['id'] ?>">
      <button class="btn btn-ghost btn-small" type="submit">Remove</button>
    </form>
  <?php endforeach; ?>
  <h3>Add a building</h3>
  <form class="form" method="post" action="/admin/properties/<?= (int) $property['id'] ?>/buildings">
    <?= csrf_field() ?>
    <input name="name" placeholder="Building or breezeway" required>
    <input name="sort_order" type="number" value="<?= count($buildings) + 1 ?>">
    <button class="btn btn-primary" type="submit">Add building</button>
  </form>
</section>

<section class="card">
  <h2>Contacts</h2>
  <?php foreach ($contacts as $contact): ?>
    <p><strong><?= e((string) $contact['name']) ?></strong> <?= e((string) ($contact['role_title'] ?? '')) ?><br><?= e((string) ($contact['phone'] ?? '')) ?> <?= e((string) ($contact['email'] ?? '')) ?></p>
  <?php endforeach; ?>
  <form class="form" method="post" action="/admin/properties/<?= (int) $property['id'] ?>/contacts">
    <?= csrf_field() ?>
    <input name="name" placeholder="Name" required>
    <input name="role_title" placeholder="Role">
    <input name="phone" placeholder="Phone">
    <input name="email" placeholder="Email">
    <label><input type="checkbox" name="is_primary" value="1"> Primary</label>
    <button class="btn btn-ghost" type="submit">Add contact</button>
  </form>
</section>

<section class="card">
  <h2>Notes</h2>
  <?php foreach ($notes as $note): ?>
    <p><strong><?= e((string) ($note['author'] ?: 'Website')) ?></strong> · <?= e((string) $note['created_at']) ?><br><?= nl((string) $note['body']) ?></p>
  <?php endforeach; ?>
  <form class="form" method="post" action="/admin/properties/<?= (int) $property['id'] ?>/notes">
    <?= csrf_field() ?>
    <textarea name="body" required placeholder="Follow-up note"></textarea>
    <button class="btn btn-ghost" type="submit">Add note</button>
  </form>
</section>
