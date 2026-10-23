<?php
/** @var string $content */
/** @var array $settings */
$documentTitle = $documentTitle ?? (($title ?? 'Loaded Valet Trash') . ' | Loaded Valet Trash');
$description = $description ?? 'Doorstep valet trash for apartment communities in Valdosta and Lowndes County, Georgia.';
$path = $path ?? '/';
$canonical = base_url($path === '/' ? '/' : ltrim($path, '/'));
$nav = [
    '/services' => 'Services',
    '/how-it-works' => 'How it works',
    '/pricing' => 'Pricing',
    '/who-we-serve' => 'Who we serve',
    '/reviews' => 'Reviews',
    '/faq' => 'FAQ',
    '/about' => 'About',
];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($documentTitle) ?></title>
  <meta name="description" content="<?= e($description) ?>">
  <link rel="canonical" href="<?= e($canonical) ?>">
  <meta property="og:title" content="<?= e($documentTitle) ?>">
  <meta property="og:description" content="<?= e($description) ?>">
  <meta property="og:type" content="website">
  <meta property="og:url" content="<?= e($canonical) ?>">
  <meta property="og:image" content="<?= e(base_url('assets/brand/og-share.jpg')) ?>">
  <meta property="og:image:alt" content="Loaded Valet Trash logo">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="theme-color" content="#000000">
  <link rel="icon" href="/favicon.ico" sizes="any">
  <link rel="icon" type="image/png" href="/assets/brand/favicon-32.png" sizes="32x32">
  <link rel="apple-touch-icon" href="/assets/brand/apple-touch-icon.png">
  <link rel="manifest" href="/manifest.webmanifest">
  <link rel="stylesheet" href="<?= e(asset_href('/assets/css/app.css')) ?>">
  <?php if (!empty($jsonLd)): ?>
    <script type="application/ld+json" nonce="<?= e((string) ($GLOBALS['csp_nonce'] ?? '')) ?>"><?= json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>
  <?php endif; ?>
</head>
<body>
<a class="skip" href="#main">Skip to content</a>
<header class="site-header">
  <div class="wrap header-row">
    <a class="brand" href="/">
      <img src="/assets/brand/loaded-valet-trash-logo.png" alt="Loaded Valet Trash" width="773" height="897">
    </a>
    <nav class="nav-desktop" aria-label="Primary">
      <?php foreach ($nav as $href => $label): ?>
        <a href="<?= e($href) ?>"<?= $path === $href ? ' aria-current="page"' : '' ?>><?= e($label) ?></a>
      <?php endforeach; ?>
      <a class="btn btn-primary" href="/contact">Request a proposal</a>
    </nav>
    <details class="nav-toggle">
      <summary class="menu-button">Menu</summary>
      <nav class="nav-links" aria-label="Primary">
        <?php foreach ($nav as $href => $label): ?>
          <a href="<?= e($href) ?>"<?= $path === $href ? ' aria-current="page"' : '' ?>><?= e($label) ?></a>
        <?php endforeach; ?>
        <a class="btn btn-primary" href="/contact">Request a proposal</a>
      </nav>
    </details>
  </div>
</header>
<main id="main">
  <div class="wrap">
    <?php foreach (flashes() as $type => $message): ?>
      <div class="banner banner-<?= e((string) $type) ?>" role="status"><?= e((string) $message) ?></div>
    <?php endforeach; ?>
  </div>
  <?= $content ?>
</main>
<footer class="site-footer">
  <div class="wrap footer-brand">
    <picture>
      <source srcset="/assets/brand/loaded-valet-trash-logo.webp" type="image/webp">
      <img src="/assets/brand/loaded-valet-trash-logo.png" alt="Loaded Valet Trash" width="773" height="897" loading="lazy">
    </picture>
  </div>
  <div class="wrap footer-grid">
    <div>
      <strong><?= e((string) $settings['business_name']) ?></strong>
      <p class="fine">Doorstep valet trash. Locally owned by Anthony Jaamal Jones.</p>
      <?php if (filled($settings['service_area'] ?? null)): ?>
        <p class="fine"><?= e((string) $settings['service_area']) ?></p>
      <?php endif; ?>
      <?php if (filled($settings['phone'] ?? null)): ?>
        <p><a href="tel:<?= e(preg_replace('/[^\d+]/', '', (string) $settings['phone'])) ?>"><?= e((string) $settings['phone']) ?></a></p>
      <?php endif; ?>
      <?php if (filled($settings['email'] ?? null)): ?>
        <p><a href="mailto:<?= e((string) $settings['email']) ?>"><?= e((string) $settings['email']) ?></a></p>
      <?php endif; ?>
      <?php if (filled($settings['address'] ?? null)): ?>
        <p class="fine"><?= nl((string) $settings['address']) ?></p>
      <?php endif; ?>
    </div>
    <div>
      <strong>For properties</strong>
      <p><a href="/contact">Request a proposal</a></p>
      <p><a href="/contact?intent=walkthrough">Book a walkthrough</a></p>
      <p><a href="/services">Services</a></p>
      <p><a href="/pricing">Pricing</a></p>
      <p><a href="/reviews/new">Share a review</a></p>
    </div>
    <div>
      <strong>For residents</strong>
      <p><a href="/missed-pickup">Report a missed pickup</a></p>
      <p><a href="/how-it-works">Bag rules</a></p>
      <p><a href="/privacy">Privacy</a></p>
      <p><a href="/terms">Terms</a></p>
      <p><a href="/apply">Apply now</a></p>
    </div>
  </div>
  <div class="wrap fine footer-copy">© <?= date('Y') ?> Anthony Jaamal Jones / Loaded Valet Trash. All rights reserved.</div>
</footer>
<script src="<?= e(asset_href('/assets/js/app.js')) ?>" defer></script>
</body>
</html>
