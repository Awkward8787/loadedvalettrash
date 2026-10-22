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
  <input name="name" placeholder="Name" required value="<?= old('name') ?>">
  <input name="email" type="email" placeholder="Email" required value="<?= old('email') ?>">
  <input name="phone" placeholder="Phone" value="<?= old('phone') ?>">
  <input name="password" type="password" placeholder="Temporary password, 12+ characters" required autocomplete="new-password">
  <select name="role">
    <option value="porter">Porter</option>
    <option value="manager">Manager</option>
    <?php if (($authUser['role'] ?? '') === 'owner'): ?><option value="owner">Owner</option><?php endif; ?>
  </select>
  <input name="pay_rate" placeholder="Pay rate, 15.00" value="<?= old('pay_rate', '15.00') ?>">
  <textarea name="availability" placeholder="Nights they can work"><?= old('availability') ?></textarea>
  <button class="btn btn-primary" type="submit">Add team member</button>
</form>
