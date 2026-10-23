<h1>Team</h1>
<div class="stack">
  <?php foreach ($people as $person): ?>
    <article class="card">
      <h2><a href="/admin/employees/<?= (int) $person['id'] ?>"><?= e((string) $person['name']) ?></a></h2>
      <p><span class="badge"><?= e((string) $person['role']) ?></span> <?= (int) $person['active'] === 1 ? 'Active' : 'Inactive' ?></p>
      <p><?= e((string) $person['email']) ?> · <?= e(money((int) $person['pay_rate_cents'])) ?>/hr</p>
      <?php if (filled($person['availability'] ?? null)): ?><p class="muted"><?= nl((string) $person['availability']) ?></p><?php endif; ?>
    </article>
  <?php endforeach; ?>
</div>
<form class="form card" method="post" action="/admin/employees">
  <?= csrf_field() ?>
  <h2>Add someone</h2>
  <div class="field"><label for="new-name">Name</label><input id="new-name" name="name" required value="<?= old('name') ?>"></div>
  <div class="field"><label for="new-email">Email</label><input id="new-email" name="email" type="email" required value="<?= old('email') ?>"></div>
  <div class="field"><label for="new-phone">Phone</label><input id="new-phone" name="phone" value="<?= old('phone') ?>"></div>
  <div class="field"><label for="new-password">Temporary password</label><input id="new-password" name="password" type="password" required autocomplete="new-password" minlength="12"></div>
  <p class="muted">At least 12 characters, with a letter and a number.</p>
  <div class="field"><label for="new-role">Role</label>
  <select id="new-role" name="role">
    <option value="porter">Porter</option>
    <option value="manager">Manager</option>
    <?php if (($authUser['role'] ?? '') === 'owner'): ?><option value="owner">Owner</option><?php endif; ?>
  </select>
  </div>
  <div class="field"><label for="new-pay">Pay rate per hour</label><input id="new-pay" name="pay_rate" inputmode="decimal" value="<?= old('pay_rate', '15.00') ?>"></div>
  <div class="field"><label for="new-availability">Nights they can work</label><textarea id="new-availability" name="availability"><?= old('availability') ?></textarea></div>
  <button class="btn btn-primary" type="submit">Add team member</button>
</form>
