<section class="section">
  <div class="wrap">
    <p class="eyebrow"><?= (int) ($code ?? 404) ?></p>
    <h1><?= e((string) ($title ?? 'Page not found')) ?></h1>
    <p class="lede"><?= e((string) ($message ?? 'That page is not on this site.')) ?></p>
    <a class="btn btn-primary" href="/">Back to the home page</a>
  </div>
</section>
