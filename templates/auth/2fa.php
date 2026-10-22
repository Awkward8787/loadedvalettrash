<h1>Authenticator code</h1>
<p class="muted">Enter the 6-digit code from your app, or a recovery code.</p>
<form class="form" method="post" action="/login/2fa">
  <?= csrf_field() ?>
  <div class="field">
    <label for="code">Code</label>
    <input id="code" name="code" inputmode="numeric" autocomplete="one-time-code" required autofocus>
  </div>
  <button class="btn btn-primary btn-block" type="submit">Sign in</button>
</form>
<p><a href="/login">Start over</a></p>
