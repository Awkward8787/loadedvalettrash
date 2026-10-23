<?php
$documentTitle = ($title ?? 'Admin') . ' | Loaded';
$user = $authUser;
$items = [];
if ($user && $user['role'] !== 'porter') {
    $items = [
        '/admin' => 'Dashboard',
        '/admin/pipeline' => 'Pipeline',
        '/admin/properties' => 'Properties',
        '/admin/schedule' => 'Schedule',
        '/admin/schedule/calendar' => 'Calendar',
        '/admin/tonight' => 'Tonight',
        '/admin/issues' => 'Issues',
        '/admin/billing' => 'Billing',
        '/admin/employees' => 'Team',
        '/admin/reviews' => 'Reviews',
        '/admin/inquiries' => 'Inbox',
        '/admin/applications' => 'Applications',
        '/admin/audit' => 'Audit',
    ];
    if ($user['role'] === 'owner') {
        $items['/admin/settings'] = 'Settings';
    }
} else {
    $items = [
        '/admin/tonight' => 'Tonight',
        '/account/security' => 'Security',
    ];
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex">
  <title><?= e($documentTitle) ?></title>
  <meta name="theme-color" content="#000000">
  <link rel="icon" href="/favicon.ico">
  <link rel="apple-touch-icon" href="/assets/brand/apple-touch-icon.png">
  <link rel="manifest" href="/manifest.webmanifest">
  <link rel="stylesheet" href="<?= e(asset_href('/assets/css/app.css')) ?>">
</head>
<body class="admin">
<a class="skip" href="#main">Skip to content</a>
<header class="admin-bar">
  <div class="wrap admin-bar-row">
    <a class="brand" href="<?= ($user['role'] ?? '') === 'porter' ? '/admin/tonight' : '/admin' ?>">
      <img src="/assets/brand/loaded-valet-trash-logo.png" alt="Loaded Valet Trash" width="773" height="897">
    </a>
    <details class="mobile-nav">
      <summary class="menu-button">Menu</summary>
      <nav aria-label="Admin">
        <?php foreach ($items as $href => $label): ?>
          <a href="<?= e($href) ?>"<?= ($path ?? '') === $href ? ' aria-current="page"' : '' ?>><?= e($label) ?></a>
        <?php endforeach; ?>
        <form method="post" action="/logout"><?= csrf_field() ?><button class="btn btn-primary btn-block" type="submit">Sign out</button></form>
      </nav>
    </details>
  </div>
</header>
<div class="wrap admin-shell">
  <nav class="side-nav" aria-label="Admin">
    <?php foreach ($items as $href => $label): ?>
      <a href="<?= e($href) ?>"<?= ($path ?? '') === $href ? ' aria-current="page"' : '' ?>><?= e($label) ?></a>
    <?php endforeach; ?>
    <form method="post" action="/logout"><?= csrf_field() ?><button class="btn btn-ghost btn-small" type="submit">Sign out</button></form>
  </nav>
  <main id="main" class="admin-main">
    <?php foreach (flashes() as $type => $message): ?>
      <div class="banner banner-<?= e((string) $type) ?>" role="status"><?= e((string) $message) ?></div>
    <?php endforeach; ?>
    <?= $content ?>
  </main>
  </div>
<script src="<?= e(asset_href('/assets/js/app.js')) ?>" defer></script>
</body>
</html>
