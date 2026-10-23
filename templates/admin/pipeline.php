<h1>Pipeline</h1>
<form class="form" method="get" action="/admin/pipeline">
  <div class="grid-2">
    <div class="field">
      <label for="stage">Stage</label>
      <select id="stage" name="stage">
        <option value="">All stages</option>
        <?php foreach ($stages as $name): ?>
          <option value="<?= e($name) ?>"<?= $stage === $name ? ' selected' : '' ?>><?= e(ucfirst($name)) ?> (<?= (int) ($counts[$name] ?? 0) ?>)</option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field">
      <label for="q">Search</label>
      <input id="q" name="q" value="<?= e((string) $q) ?>">
    </div>
  </div>
  <button class="btn btn-ghost" type="submit">Filter</button>
  <a class="btn btn-primary" href="/admin/pipeline/new">New lead</a>
</form>
<div class="stack">
  <?php if ($rows === []): ?>
    <p class="card">No properties in this view. Add a lead or import a CSV. The template row is an example, not a real community.</p>
  <?php endif; ?>
  <?php foreach ($rows as $row): ?>
    <article class="card">
      <p><span class="badge"><?= e((string) $row['stage']) ?></span></p>
      <h2><a href="/admin/properties/<?= (int) $row['id'] ?>"><?= e((string) $row['name']) ?></a></h2>
      <p class="muted"><?= (int) $row['units_count'] ?> units<?php if (filled($row['city'] ?? null)): ?> · <?= e((string) $row['city']) ?><?php endif; ?><?php if (filled($row['company_name'] ?? null)): ?> · <?= e((string) $row['company_name']) ?><?php endif; ?></p>
      <?php if (filled($row['follow_up_on'] ?? null)): ?><p>Follow up <?= e((string) $row['follow_up_on']) ?></p><?php endif; ?>
    </article>
  <?php endforeach; ?>
</div>
<section class="card">
  <h2>Import leads</h2>
  <p class="muted">Columns: name, address, units, manager, phone, email, website, has_valet_trash. Gate codes are not imported.</p>
  <form class="form" method="post" action="/admin/pipeline/import" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <label for="lead-csv">CSV file</label>
    <input id="lead-csv" type="file" name="csv" accept=".csv,text/csv" required>
    <button class="btn btn-primary" type="submit">Import CSV</button>
    <a href="/admin/pipeline/template.csv">Download the example template</a>
  </form>
</section>
<section class="card">
  <h2>Management company</h2>
  <form class="form" method="post" action="/admin/companies">
    <?= csrf_field() ?>
    <label for="company">Name</label>
    <input id="company" name="name" required>
    <button class="btn btn-ghost" type="submit">Save company</button>
  </form>
</section>
