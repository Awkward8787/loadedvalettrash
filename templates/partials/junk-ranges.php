<?php
/** @var array{items:array<string,array{low:int,high:int}|null>,loads:array<string,array{low:int,high:int}|null>} $rateBook */
$junkRanges = \Lvt\Junk\Quote::publishedLines($rateBook);
if ($junkRanges === []) {
    return;
}
?>
<p><strong>Estimate-only ranges</strong></p>
<ul class="rule-list">
  <?php foreach ($junkRanges as $line): ?>
    <li><?= e($line['label']) ?>: <?= e($line['range']) ?>. Estimate only.</li>
  <?php endforeach; ?>
</ul>
<p>The final price is confirmed with the team. Move-out cleanout is quoted per job.</p>
