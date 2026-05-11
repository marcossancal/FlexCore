<?php partial('layout/header', [
  'page_title'    => $entity['name'].' #'.$record['id'],
  'active_entity' => $entity['slug'],
  'breadcrumbs'   => [
    ['label' => $entity['icon'].' '.$entity['name'], 'url' => '/e/'.$entity['slug']],
    ['label' => '#'.$record['id']],
  ],
]) ?>

<div class="sec-head">
  <div>
    <div class="sec-title"><?= h($entity['icon']) ?> <?= h($entity['name']) ?> <span style="color:var(--mt);font-weight:400">#<?= $record['id'] ?></span></div>
    <div class="sec-sub"><?= __('general.created_at') ?>: <?= dateBr($record['created_at']) ?></div>
  </div>
  <div class="sec-actions">
    <a href="<?= url('/e/' . h($entity['slug']) . '/' . $record['id'] . '/edit') ?>" class="btn btn-ghost btn-sm">✏️ <?= __('general.edit') ?></a>
    <a href="<?= url('/e/' . h($entity['slug'])) ?>" class="btn btn-ghost btn-sm">← <?= __('general.back') ?></a>
  </div>
</div>

<div class="row2" style="gap:18px;align-items:start">
  <div style="flex:2">
    <div class="card">
      <div class="card-title"><?= __('records.view') ?></div>
      <?php if (empty($fields)): ?>
      <p style="color:var(--mt)"><?= __('records.no_fields') ?></p>
      <?php else: ?>
      <dl style="display:grid;grid-template-columns:minmax(120px,180px) 1fr;gap:0">
        <?php foreach ($fields as $f):
          $val = $record['values'][$f['id']] ?? null;
        ?>
        <dt style="padding:12px 0;border-bottom:1px solid var(--bd);font-size:.78rem;font-weight:700;color:var(--mt);display:flex;align-items:center;gap:6px">
          <?= fieldTypeIcon($f['field_type']) ?> <?= h($f['name']) ?>
        </dt>
        <dd style="padding:12px 0 12px 16px;border-bottom:1px solid var(--bd);font-size:.88rem;color:var(--mt2)">
          <?php if ($val === null || $val === ''): ?>
          <span style="color:var(--mt);font-style:italic">—</span>
          <?php else: ?>
          <?= renderFieldValue($f, $val, true) ?>
          <?php endif; ?>
        </dd>
        <?php endforeach; ?>
      </dl>
      <?php endif; ?>
    </div>
  </div>

  <div>
    <div class="card">
      <div class="card-title" style="font-size:.82rem"><?= __('general.actions') ?></div>
      <div style="display:flex;flex-direction:column;gap:8px">
        <a href="<?= url('/e/' . h($entity['slug']) . '/' . $record['id'] . '/edit') ?>" class="btn btn-ghost" style="justify-content:center">✏️ <?= __('records.edit') ?></a>
        <form method="POST" action="<?= url('e/'. h($entity['slug']).'/'.$record['id'].'/delete');?>"
              onsubmit="return confirm('<?= __('records.delete_confirm') ?>')">
          <button type="submit" class="btn btn-danger" style="width:100%;justify-content:center">🗑️ <?= __('general.delete') ?></button>
        </form>
      </div>
    </div>

    <div class="card">
      <div class="card-title" style="font-size:.82rem"><?= __('general.status') ?></div>
      <div style="font-size:.8rem;line-height:2.4;color:var(--mt2)">
        <div><span style="color:var(--mt)">ID:</span> #<?= $record['id'] ?></div>
        <div><span style="color:var(--mt)"><?= __('general.created_at') ?>:</span> <?= dateBr($record['created_at']) ?></div>
        <div><span style="color:var(--mt)"><?= __('general.updated_at') ?>:</span> <?= dateBr($record['updated_at']) ?></div>
        <div><span style="color:var(--mt)"><?= __('nav.entities') ?>:</span> <?= h($entity['name']) ?></div>
      </div>
    </div>
  </div>
</div>

<?php partial('layout/footer') ?>
