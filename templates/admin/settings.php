<h1>Settings</h1>
<p class="muted">Phone, email, address, and insurance text are hidden on the public site when they are blank. Do not type a placeholder.</p>
<form class="form card" method="post" action="/admin/settings">
  <?= csrf_field() ?>
  <label>Business name <input name="business_name" value="<?= e((string) $record['business_name']) ?>"></label>
  <label>Owner name <input name="owner_name" value="<?= e((string) $record['owner_name']) ?>"></label>
  <label>Phone <input name="phone" value="<?= e((string) ($record['phone'] ?? '')) ?>"></label>
  <label>Public email <input name="email" value="<?= e((string) ($record['email'] ?? '')) ?>"></label>
  <label>Address <textarea name="address"><?= e((string) ($record['address'] ?? '')) ?></textarea></label>
  <label>Service area <input name="service_area" value="<?= e((string) ($record['service_area'] ?? '')) ?>"></label>
  <label>Insurance text <textarea name="insurance_text"><?= e((string) ($record['insurance_text'] ?? '')) ?></textarea></label>
  <label>Porter job details <textarea name="porter_job_details"><?= e((string) ($record['porter_job_details'] ?? '')) ?></textarea></label>
  <p class="muted">Shown on the apply page under the heading Job details when this has text. Leave it blank and that heading is omitted. Pay, benefits, background checks, and licence rules stay out of this box until you decide them.</p>
  <label>Opt-in price <input name="opt_in_price" value="<?= e(cents_to_dollars((int) $record['opt_in_price_cents'])) ?>"></label>
  <label>Default property share % <input name="default_property_share_percent" value="<?= e((string) $record['default_property_share_percent']) ?>"></label>
  <label>Minutes per door <input name="minutes_per_door" value="<?= e((string) $record['minutes_per_door']) ?>"></label>
  <label>Minutes per site <input name="minutes_per_site" value="<?= e((string) $record['minutes_per_site']) ?>"></label>
  <label>Pickup window minutes <input name="pickup_window_minutes" value="<?= e((string) $record['pickup_window_minutes']) ?>"></label>
  <label>Set-out start <input type="time" name="setout_start" value="<?= e(substr((string) $record['setout_start'], 0, 5)) ?>"></label>
  <label>Set-out end <input type="time" name="setout_end" value="<?= e(substr((string) $record['setout_end'], 0, 5)) ?>"></label>
  <label>Pickup start <input type="time" name="pickup_start" value="<?= e(substr((string) $record['pickup_start'], 0, 5)) ?>"></label>
  <label>Pickup end <input type="time" name="pickup_end" value="<?= e(substr((string) $record['pickup_end'], 0, 5)) ?>"></label>
  <label>Max bags <input name="max_bags_per_night" value="<?= e((string) $record['max_bags_per_night']) ?>"></label>
  <label>Holiday bags <input name="holiday_bags" value="<?= e((string) $record['holiday_bags']) ?>"></label>
  <label>Max bag weight (lb) <input name="max_bag_weight_lb" value="<?= e((string) $record['max_bag_weight_lb']) ?>"></label>
  <label>Default wage <input name="default_wage" value="<?= e(cents_to_dollars((int) $record['default_wage_cents'])) ?>"></label>
  <label>Payroll multiplier <input name="payroll_multiplier" value="<?= e((string) $record['payroll_multiplier']) ?>"></label>
  <label>Supplies per site / month <input name="per_site_supplies" value="<?= e(cents_to_dollars((int) $record['per_site_supplies_cents'])) ?>"></label>
  <label>Gas per site / month <input name="per_site_gas" value="<?= e(cents_to_dollars((int) $record['per_site_gas_cents'])) ?>"></label>
  <label>Vehicle reserve per site / month <input name="per_site_vehicle_reserve" value="<?= e(cents_to_dollars((int) $record['per_site_vehicle_reserve_cents'])) ?>"></label>
  <label>Monthly fixed costs <input name="monthly_fixed_costs" value="<?= e(cents_to_dollars((int) $record['monthly_fixed_costs_cents'])) ?>"></label>
  <label>Remittance due day <input name="remittance_due_day" value="<?= e((string) $record['remittance_due_day']) ?>"></label>
  <label>Session timeout minutes <input name="session_timeout_minutes" value="<?= e((string) $record['session_timeout_minutes']) ?>"></label>
  <label>Missed pickup report-by <input name="missed_report_by" value="<?= e((string) $record['missed_report_by']) ?>"></label>
  <button class="btn btn-primary" type="submit">Save settings</button>
</form>
<section class="card">
  <h2>Holidays</h2>
  <ul>
    <?php foreach ($holidays as $holiday): ?>
      <li>
        <?= e((string) $holiday['observed_on']) ?> · <?= e((string) $holiday['name']) ?> · <?= (int) $holiday['service_off'] === 1 ? 'off' : 'on' ?>
        <form method="post" action="/admin/holidays/<?= (int) $holiday['id'] ?>/delete"><?= csrf_field() ?><button class="btn btn-ghost btn-small" type="submit">Remove</button></form>
      </li>
    <?php endforeach; ?>
  </ul>
  <form class="form" method="post" action="/admin/holidays">
    <?= csrf_field() ?>
    <div class="field"><label for="holiday-name">Holiday name</label><input id="holiday-name" name="name" required></div>
    <div class="field"><label for="holiday-date">Date</label><input id="holiday-date" type="date" name="observed_on" required></div>
    <label><input type="checkbox" name="service_off" value="1" checked> No service</label>
    <div class="field"><label for="holiday-instructions">What residents should do</label><input id="holiday-instructions" name="instructions"></div>
    <button class="btn btn-ghost" type="submit">Save holiday</button>
  </form>
</section>
