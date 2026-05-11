<?php partial('layout/header', ['page_title' => __('entities.page_title_list'), 'active_page' => 'entities', 'breadcrumbs' => [['label' => __('entities.page_title_list')]]]) ?>

<div class="sec-head">
  <div>
    <div class="sec-title">⚙️ <?= __('entities.title') ?></div>
    <div class="sec-sub"><?= __('entities.next_step_desc') ?></div>
  </div>
  <a href="<?= url('/entities/new') ?>" class="btn btn-primary">+ <?= __('entities.new') ?></a>
</div>

<?php if (empty($entities)): ?>
<div class="card" style="text-align:center;padding:48px">
  <div style="font-size:2.5rem;margin-bottom:12px">🧩</div>
  <div style="font-family:var(--fd);font-size:1.1rem;font-weight:700;margin-bottom:8px"><?= __('entities.no_entities') ?></div>
  <div style="color:var(--mt);margin-bottom:24px"><?= __('entities.next_step_desc') ?></div>
  <a href="<?= url('/entities/new') ?>" class="btn btn-primary">+ <?= __('entities.create_first') ?></a>
</div>
<?php else: ?>
<div class="card">
  <div class="tbl-wrap">
    <table>
      <thead><tr>
        <th><?= __('general.name') ?></th>
        <th>Slug</th>
        <th><?= __('entities.field_count') ?></th>
        <th><?= __('entities.record_count') ?></th>
        <th><?= __('general.status') ?></th>
        <th><?= __('entities.position') ?></th>
        <th></th>
      </tr></thead>
      <tbody>
      <?php foreach ($entities as $ent): ?>
      <tr>
        <td>
          <div class="ent-chip">
            <div class="ent-dot" style="background:<?= h($ent['color']) ?>"></div>
            <strong style="color:var(--tx)"><?= h($ent['icon']) ?> <?= h($ent['name']) ?></strong>
          </div>
          <?php if ($ent['description']): ?>
          <div style="font-size:.75rem;color:var(--mt);margin-top:3px"><?= h(mb_substr($ent['description'],0,60)) ?>…</div>
          <?php endif; ?>
        </td>
        <td><code style="background:var(--sf2);padding:2px 7px;border-radius:4px;font-size:.78rem;color:var(--ac)"><?= h($ent['slug']) ?></code></td>
        <td style="text-align:center;color:var(--mt2)"><?= $ent['field_count'] ?></td>
        <td style="text-align:center;color:var(--mt2)"><?= number_format($ent['record_count']) ?></td>
        <td><?= $ent['active'] ? '<span class="badge bg">'.__('general.active').'</span>' : '<span class="badge bm">'.__('general.inactive').'</span>' ?></td>
        <td style="text-align:center;color:var(--mt)"><?= $ent['position'] ?></td>
        <td>
          <div class="td-actions">
            <a href="<?= url('/e/' . h($ent['slug'])) ?>" class="btn btn-ghost btn-xs">👁 <?= __('records.view') ?></a>
            <a href="<?= url('/entities/' . $ent['id'] . '/edit') ?>" class="btn btn-ghost btn-xs">✏️ <?= __('general.edit') ?></a>
            <a href="<?= url('/entities/' . $ent['id'] . '/fields') ?>" class="btn btn-ghost btn-xs">🔧 <?= __('entities.fields') ?></a>
            <a href="<?= url('/entities/' . $ent['id'] . '/edit?tab=api') ?>" class="btn btn-ghost btn-xs">📡 API</a>
            <form method="POST" action="<?= url('entities/'. $ent['id'] .'/delete');?>" style="display:inline"
                  onsubmit="return confirm('<?= __('entities.delete_confirm') ?>')">
              <button type="submit" class="btn btn-danger btn-xs"><?= __('general.delete') ?></button>
            </form>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<?php partial('layout/footer') ?>
