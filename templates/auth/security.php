<h1>Authenticator</h1>
<?php if (!empty($recoveryCodes)): ?>
  <div class="warning">
    <p><strong>Save these recovery codes now.</strong> Each one works a single time. This page will not show them again.</p>
    <ul>
      <?php foreach ($recoveryCodes as $code): ?>
        <li><code><?= e((string) $code) ?></code></li>
      <?php endforeach; ?>
    </ul>
  </div>
  <p><a class="btn btn-primary" href="<?= ($subject['role'] ?? '') === 'porter' ? '/admin/tonight' : '/admin' ?>">Continue</a></p>
<?php elseif ((int) ($subject['totp_enabled'] ?? 0) === 1): ?>
  <p>Two-factor authentication is on for <?= e((string) $subject['email']) ?>.</p>
  <p><a class="btn btn-primary" href="<?= ($subject['role'] ?? '') === 'porter' ? '/admin/tonight' : '/admin' ?>">Back to work</a></p>
<?php else: ?>
  <p>Add this account to an authenticator app (Google Authenticator, 1Password, Authy, or similar). Staff tools stay locked until this is on.</p>
  <p class="secret" id="totp-secret"><?= e(trim(chunk_split((string) $secret, 4, ' '))) ?></p>
  <?php if (!empty($otpauth)): ?>
    <p><a href="<?= e((string) $otpauth) ?>">Open in your authenticator app</a></p>
    <p class="muted">If that link does nothing, type the key above by hand. Account: <?= e((string) $subject['email']) ?>. Time-based, 6 digits, 30 seconds.</p>
  <?php endif; ?>
  <form class="form" method="post" action="/account/security">
    <?= csrf_field() ?>
    <div class="field">
      <label for="code">6-digit code</label>
      <input id="code" name="code" inputmode="numeric" autocomplete="one-time-code" required>
    </div>
    <button class="btn btn-primary btn-block" type="submit">Turn on authenticator</button>
  </form>
<?php endif; ?>
<form method="post" action="/logout">
  <?= csrf_field() ?>
  <button class="btn btn-ghost btn-block" type="submit">Sign out</button>
</form>
