<?php
$firstDow = (int) date('N', strtotime($start . ' 12:00:00'));
$days = (int) date('t', strtotime($start . ' 12:00:00'));
$prev = date('Y-m', strtotime($start . ' -1 month'));
$next = date('Y-m', strtotime($start . ' +1 month'));
?>
<h1><?= e(date('F Y', strtotime($start))) ?></h1>
<p><a href="/admin/schedule/calendar?month=<?= e($prev) ?>">Previous</a> · <a href="/admin/schedule/calendar?month=<?= e($next) ?>">Next</a> · <a href="/admin/schedule">List</a></p>
<div class="calendar-scroll">
<div class="calendar" aria-label="Route calendar">
  <?php foreach (['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $label): ?><div class="muted"><?= e($label) ?></div><?php endforeach; ?>
  <?php for ($i = 1; $i < $firstDow; $i++): ?><div></div><?php endfor; ?>
  <?php for ($d = 1; $d <= $days; $d++): ?>
    <?php $key = sprintf('%s-%02d', $month, $d); ?>
    <div class="day">
      <strong><?= $d ?></strong>
      <?php foreach ($byDay[$key] ?? [] as $run): ?>
        <div><a href="/admin/schedule/<?= (int) $run['id'] ?>"><?= e((string) ($run['porter_name'] ?: 'Open')) ?></a></div>
      <?php endforeach; ?>
    </div>
  <?php endfor; ?>
</div>
</div>
