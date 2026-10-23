<h1>Staff sign-in</h1>
<p class="muted">Owners, managers, and porters. Residents do not need an account.</p>
<form class="form" method="post" action="/login">
  <?= csrf_field() ?>
  <input type="hidden" name="next" value="<?= e((string) ($next ?? '/admin')) ?>">
  <div class="field">
    <?php $describedBy = has_error('email') ? 'err-email' : (!empty($formError) ? 'form-error' : ''); ?>
    <label for="email">Email</label>
    <input id="email" name="email" type="email" autocomplete="username" required value="<?= old('email') ?>"<?= $describedBy !== '' ? ' aria-invalid="true" aria-describedby="' . e($describedBy) . '"' : '' ?>>
    <?= field_error('email') ?>
  </div>
  <div class="field">
    <label for="password">Password</label>
    <input id="password" name="password" type="password" autocomplete="current-password" required<?= $describedBy !== '' ? ' aria-invalid="true" aria-describedby="' . e($describedBy) . '"' : '' ?>>
  </div>
  <button class="btn btn-primary btn-block" type="submit">Continue</button>
</form>
