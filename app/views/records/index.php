<?php partial('layout/header', [
  'page_title'    => $entity['name'],
  'active_entity' => $entity['slug'],
  'breadcrumbs'   => [['label' => $entity['icon'].' '.$entity['name']]],
]) ?>

<div class="sec-head">
  <div>
    <div class="sec-title" style="display:flex;align-items:center;gap:10px">
      <span style="display:inline-flex;align-items:center;justify-content:center;width:36px;height:36px;background:color-mix(in srgb,<?= h($entity['color']) ?> 15%,var(--sf2));border-radius:8px;font-size:1.1rem"><?= h($entity['icon']) ?></span>
      <?= h($entity['name']) ?>
    </div>
    <?php if ($entity['description']): ?>
    <div class="sec-sub"><?= h($entity['description']) ?></div>
    <?php endif; ?>
  </div>
  <div class="sec-actions">
    <?php if (count($fields) > 0): ?>
    <a href="<?= url('/e/' . h($entity['slug']) . '/new') ?>" class="btn btn-primary" style="background:<?= h($entity['color']) ?>">+ <?= __('records.new') ?></a>
    <?php endif; ?>
    <?php if (Auth::user()['role']==='admin'): ?>
    <a href="<?= url('/entities/' . $entity['id'] . '/fields') ?>" class="btn btn-ghost btn-sm">🔧 <?= __('entities.fields') ?></a>
    <?php endif; ?>
  </div>
</div>

<?php if (empty($fields)): ?>
<div class="card" style="text-align:center;padding:48px">
  <div style="font-size:2rem;margin-bottom:12px">🔧</div>
  <div style="font-family:var(--fd);font-size:1rem;font-weight:700;margin-bottom:8px"><?= __('records.no_fields') ?></div>
  <?php if (Auth::user()['role']==='admin'): ?>
  <a href="<?= url('/entities/' . $entity['id'] . '/fields') ?>" class="btn btn-primary">🔧 <?= __('records.configure_first') ?></a>
  <?php endif; ?>
</div>
<?php elseif (empty($records)): ?>
<div class="card" style="text-align:center;padding:48px">
  <div style="font-size:2rem;margin-bottom:12px"><?= h($entity['icon']) ?></div>
  <div style="font-family:var(--fd);font-size:1rem;font-weight:700;margin-bottom:8px"><?= __('records.no_records') ?></div>
  <a href="<?= url('/e/' . h($entity['slug']) . '/new') ?>" class="btn btn-primary" style="background:<?= h($entity['color']) ?>">+ <?= __('records.new') ?></a>
</div>
<?php else: ?>

<div style="margin-bottom:16px;display:flex;gap:10px;align-items:center;flex-wrap:wrap">
  <form method="GET" style="flex:1;display:flex;gap:8px;min-width:200px">
    <input type="text" name="q" value="<?= h(get('q')) ?>" placeholder="<?= __('records.search') ?>"
           style="background:var(--sf);border:1px solid var(--bd2);border-radius:var(--r2);color:var(--tx);padding:8px 14px;font-family:var(--fb);font-size:.875rem;outline:none;flex:1">
    <button type="submit" class="btn btn-ghost btn-sm">🔍</button>
    <?php if (get('q')): ?><a href="<?= url('/e/' . h($entity['slug'])) ?>" class="btn btn-ghost btn-sm">✕ <?= __('general.clear') ?></a><?php endif; ?>
  </form>
  <span style="color:var(--mt);font-size:.78rem"><?= number_format($total) ?> <?= __('records.title') ?></span>
</div>

<div class="card" style="padding:0;overflow:hidden">
  <div class="tbl-wrap">
    <table>
      <thead>
        <tr>
          <th style="width:50px">#</th>
          <?php foreach ($list_fields as $f): ?>
          <th><?= h($f['name']) ?></th>
          <?php endforeach; ?>
          <th><?= __('general.created_at') ?></th>
          <th style="width:80px"></th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($records as $rec): ?>
      <tr>
        <td style="color:var(--mt);font-family:monospace;font-size:.78rem"><?= $rec['id'] ?></td>
        <?php foreach ($list_fields as $f): ?>
        <td><?= renderFieldValue($f, $rec['values'][$f['id']] ?? null) ?></td>
        <?php endforeach; ?>
        <td style="font-size:.75rem;color:var(--mt)"><?= dateBr($rec['created_at']) ?></td>
        <td>
          <div class="td-actions">
            <a href="<?= url('/e/' . h($entity['slug']) . '/' . $rec['id']) ?>" class="btn btn-ghost btn-xs"><?= __('records.view') ?></a>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php if ($pages > 1): ?>
  <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 20px;border-top:1px solid var(--bd);flex-wrap:wrap;gap:8px">
    <span style="font-size:.78rem;color:var(--mt)"><?= __('general.page') ?> <?= $page ?> <?= __('general.of') ?> <?= $pages ?></span>
    <div style="display:flex;gap:6px">
      <?php if ($page > 1): ?><a href="?page=<?= $page-1 ?>&q=<?= urlencode(get('q')) ?>" class="btn btn-ghost btn-xs">←</a><?php endif; ?>
      <?php if ($page < $pages): ?><a href="?page=<?= $page+1 ?>&q=<?= urlencode(get('q')) ?>" class="btn btn-ghost btn-xs">→</a><?php endif; ?>
    </div>
  </div>
  <?php endif; ?>
</div>
<?php endif; ?>

<?php partial('layout/footer') ?>
