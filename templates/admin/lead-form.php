<h1>New lead</h1>
<form class="form card" method="post" action="/admin/pipeline">
  <?= csrf_field() ?>
  <div class="field"><label for="name">Property name</label><input id="name" name="name" required value="<?= old('name') ?>"><?= field_error('name') ?></div>
  <div class="field"><label for="address">Address</label><input id="address" name="address" value="<?= old('address') ?>"></div>
  <div class="grid-2">
    <div class="field"><label for="city">City</label><input id="city" name="city" value="<?= old('city') ?>"></div>
    <div class="field"><label for="units_count">Units</label><input id="units_count" name="units_count" type="number" min="0" value="<?= old('units_count', '0') ?>"><?= field_error('units_count') ?></div>
  </div>
  <div class="field"><label for="website">Website</label><input id="website" name="website" value="<?= old('website') ?>"></div>
  <div class="field">
    <label for="has_valet_trash">Already has valet trash?</label>
    <select id="has_valet_trash" name="has_valet_trash">
      <option value="unknown">Unknown</option>
      <option value="no">No</option>
      <option value="yes">Yes</option>
    </select>
  </div>
  <div class="field"><label for="company_name">Management company</label><input id="company_name" name="company_name" value="<?= old('company_name') ?>"></div>
  <div class="field"><label for="manager">Manager</label><input id="manager" name="manager" value="<?= old('manager') ?>"></div>
  <div class="grid-2">
    <div class="field"><label for="phone">Phone</label><input id="phone" name="phone" value="<?= old('phone') ?>"></div>
    <div class="field"><label for="email">Email</label><input id="email" name="email" type="email" value="<?= old('email') ?>"><?= field_error('email') ?></div>
  </div>
  <div class="field"><label for="follow_up_on">Follow-up date</label><input id="follow_up_on" name="follow_up_on" type="date" value="<?= old('follow_up_on') ?>"><?= field_error('follow_up_on') ?></div>
  <div class="field"><label for="notes">Note</label><textarea id="notes" name="notes"><?= old('notes') ?></textarea></div>
  <button class="btn btn-primary" type="submit">Save lead</button>
</form>
