<?php
$documentTitle = ($title ?? 'Sign in') . ' | Loaded Valet Trash';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex">
  <title><?= e($documentTitle) ?></title>
  <meta name="theme-color" content="#000000">
  <link rel="icon" href="/favicon.ico" sizes="any">
  <link rel="icon" type="image/png" href="/assets/brand/favicon-32.png" sizes="32x32">
  <link rel="apple-touch-icon" href="/assets/brand/apple-touch-icon.png">
  <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body class="auth-body">
  <main id="main" class="auth-card">
    <a class="brand" href="/">
      <picture>
        <source srcset="/assets/brand/loaded-valet-trash-logo.webp" type="image/webp">
        <img src="/assets/brand/loaded-valet-trash-logo.png" alt="Loaded Valet Trash" width="773" height="897">
      </picture>
    </a>
    <?php foreach (flashes() as $type => $message): ?>
      <div class="banner banner-<?= e((string) $type) ?>" role="status"><?= e((string) $message) ?></div>
    <?php endforeach; ?>
    <?php if (!empty($formError)): ?>
      <div class="banner banner-error" id="form-error" role="alert"><?= e((string) $formError) ?></div>
    <?php endif; ?>
    <?= $content ?>
  </main>
</body>
</html>
