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
  <meta name="theme-color" content="#111111">
  <link rel="icon" href="/favicon.ico">
  <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body class="auth-body">
  <main id="main" class="auth-card">
    <a class="brand" href="/"><img src="/assets/brand/loaded-valet-trash-logo.jpg" alt="Loaded Valet Trash"></a>
    <?php foreach (flashes() as $type => $message): ?>
      <div class="banner banner-<?= e((string) $type) ?>" role="status"><?= e((string) $message) ?></div>
    <?php endforeach; ?>
    <?php if (!empty($formError)): ?>
      <div class="banner banner-error" role="alert"><?= e((string) $formError) ?></div>
    <?php endif; ?>
    <?= $content ?>
  </main>
</body>
</html>
