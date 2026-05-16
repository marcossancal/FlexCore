<?php
$page_title  = __('automations.page_title_logs') . ' — ' . h($automation['name']);
$active_page = 'automations';
$breadcrumbs = [
  ['label' => __('automations.title'), 'url' => admin_url('/automations')],
  ['label' => h($automation['name'])],
  ['label' => __('automations.logs')],
];
partial('layout/header');
?>

<div class="sec-head">
  <div>
    <div class="sec-title">📋 <?= __('automations.logs') ?>: <?= h($automation['name']) ?></div>
    <div class="sec-sub"><?= number_format((int)$automation['run_count']) ?> <?= __('automations.runs') ?></div>
  </div>
  <a href="<?= admin_url('/automations') ?>" class="btn btn-ghost btn-sm">← <?= __('general.back') ?></a>
</div>

<div class="card" style="padding:0">
  <div class="tbl-wrap">
    <table>
      <thead>
        <tr>
          <th><?= __('general.created_at') ?></th>
          <th><?= __('general.status') ?></th>
          <th>ID</th>
          <th>Log</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($logs)): ?>
        <tr class="empty-row"><td colspan="4"><?= __('general.no_results') ?></td></tr>
        <?php else: foreach ($logs as $l): ?>
        <tr>
          <td style="font-size:.8rem;color:var(--mt)"><?= date('d/m/Y H:i:s',strtotime($l['created_at'])) ?></td>
          <td>
            <span class="badge <?= match($l['status']){'success'=>'bg','error'=>'br',default=>'bm'} ?>">
              <?= match($l['status']){'success'=>'✅ '.__('general.success'),'error'=>'❌ '.__('general.error'),default=>'⏭'} ?>
            </span>
          </td>
          <td style="color:var(--mt)"><?= $l['record_id'] ? '#'.$l['record_id'] : '—' ?></td>
          <td style="color:var(--mt);font-size:.82rem"><?= $l['message'] ? h($l['message']) : '—' ?></td>
        </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php partial('layout/footer'); ?>
