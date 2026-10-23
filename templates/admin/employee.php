<h1><?= e((string) $person['name']) ?></h1>
<p><?= e(number_format((float) $monthHours, 2)) ?> hours logged so far this month.</p>
<form class="form card" method="post" action="/admin/employees/<?= (int) $person['id'] ?>">
  <?= csrf_field() ?>
  <div class="field"><label for="emp-name">Name</label><input id="emp-name" name="name" value="<?= e((string) $person['name']) ?>" required></div>
  <div class="field"><label for="emp-email">Email</label><input id="emp-email" name="email" type="email" value="<?= e((string) $person['email']) ?>" required></div>
  <div class="field"><label for="emp-phone">Phone</label><input id="emp-phone" name="phone" value="<?= e((string) ($person['phone'] ?? '')) ?>"></div>
  <div class="field"><label for="emp-role">Role</label>
  <select id="emp-role" name="role">
    <?php foreach (['porter','manager','owner'] as $role): ?>
      <?php if ($role === 'owner' && ($authUser['role'] ?? '') !== 'owner') continue; ?>
      <option value="<?= e($role) ?>"<?= $person['role'] === $role ? ' selected' : '' ?>><?= e($role) ?></option>
    <?php endforeach; ?>
  </select>
  </div>
  <label for="pay_rate">Pay rate / hour</label>
  <input id="pay_rate" name="pay_rate" value="<?= e(cents_to_dollars((int) $person['pay_rate_cents'])) ?>">
  <div class="field"><label for="emp-availability">Availability</label><textarea id="emp-availability" name="availability"><?= e((string) ($person['availability'] ?? '')) ?></textarea></div>
  <label><input type="checkbox" name="active" value="1" <?= (int) $person['active'] === 1 ? 'checked' : '' ?>> Active</label>
  <div class="field"><label for="emp-password">New password</label><input id="emp-password" name="password" type="password" autocomplete="new-password"></div>
  <p class="muted">Leave blank to keep the current password.</p>
  <p class="muted">Authenticator: <?= (int) $person['totp_enabled'] === 1 ? 'on' : 'not set up yet' ?>.</p>
  <button class="btn btn-primary" type="submit">Save</button>
</form>
<section class="card">
  <h2>Hours</h2>
  <div class="table-wrap">
    <table>
      <thead><tr><th>In</th><th>Out</th><th>Route night</th><th>Notes</th></tr></thead>
      <tbody>
      <?php foreach ($entries as $entry): ?>
        <tr>
          <td><?= e((string) $entry['clock_in']) ?></td>
          <td><?= e((string) ($entry['clock_out'] ?? 'open')) ?></td>
          <td><?= e((string) ($entry['service_date'] ?? '')) ?></td>
          <td><?= e((string) ($entry['notes'] ?? '')) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <form class="form" method="post" action="/admin/employees/<?= (int) $person['id'] ?>">
    <?= csrf_field() ?>
    <h3>Add hours</h3>
    <label for="clock_in">Clock in</label>
    <input id="clock_in" name="clock_in" type="datetime-local" required>
    <label for="clock_out">Clock out</label>
    <input id="clock_out" name="clock_out" type="datetime-local" required>
    <div class="field"><label for="time-notes">Notes</label><input id="time-notes" name="time_notes"></div>
    <button class="btn btn-ghost" type="submit">Add hours</button>
  </form>
</section>
