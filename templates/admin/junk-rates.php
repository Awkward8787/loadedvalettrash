<?php
/** @var array<string,mixed> $rates */
$lines = [];
foreach (\Lvt\Junk\Quote::items() as $code => $label) {
    $lines[] = ['code' => $code, 'label' => $label, 'group' => 'Items'];
}
foreach (\Lvt\Junk\Quote::loads() as $code => $label) {
    $lines[] = ['code' => $code, 'label' => $label, 'group' => 'Load size'];
}
$oldBag = $GLOBALS['_old'] ?? [];
$amount = static function (string $key) use ($rates, $oldBag): string {
    if (array_key_exists($key, $oldBag) && is_scalar($oldBag[$key])) {
        return e((string) $oldBag[$key]);
    }
    $cents = $rates[$key . '_cents'] ?? null;
    return e(cents_to_dollars($cents === null ? null : (int) $cents));
};
?>
<h1>Junk pickup rates</h1>
<p class="muted">Leave a row blank when you have not set a rate. Each saved range is printed on the public junk page, labeled estimate only. The calculator shows no dollar total until every chosen item or the chosen load has a rate.</p>
<form class="form card" method="post" action="/admin/junk-rates">
  <?= csrf_field() ?>
  <?php $group = ''; ?>
  <?php foreach ($lines as $line): ?>
    <?php if ($group !== $line['group']): ?>
      <?php $group = $line['group']; ?>
      <h2><?= e($group) ?></h2>
    <?php endif; ?>
    <div class="grid-2">
      <div class="field">
        <label for="<?= e($line['code']) ?>-low"><?= e($line['label']) ?> low</label>
        <input id="<?= e($line['code']) ?>-low" name="<?= e($line['code']) ?>_low" inputmode="decimal" value="<?= $amount($line['code'] . '_low') ?>" <?= has_error($line['code'] . '_low') ? 'aria-invalid="true" aria-describedby="err-' . e($line['code'] . '_low') . '"' : '' ?>>
        <?= field_error($line['code'] . '_low') ?>
      </div>
      <div class="field">
        <label for="<?= e($line['code']) ?>-high"><?= e($line['label']) ?> high</label>
        <input id="<?= e($line['code']) ?>-high" name="<?= e($line['code']) ?>_high" inputmode="decimal" value="<?= $amount($line['code'] . '_high') ?>" <?= has_error($line['code'] . '_high') ? 'aria-invalid="true" aria-describedby="err-' . e($line['code'] . '_high') . '"' : '' ?>>
        <?= field_error($line['code'] . '_high') ?>
      </div>
    </div>
  <?php endforeach; ?>
  <button class="btn btn-primary" type="submit">Save rates</button>
</form>
