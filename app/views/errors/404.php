<?php partial('layout/header', ['page_title' => __('errors.404_title')]) ?>
<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:55vh;text-align:center;padding:24px">
  <div style="font-size:3.5rem;margin-bottom:16px">🔍</div>
  <div style="font-family:var(--fd);font-size:1.4rem;font-weight:800;margin-bottom:8px"><?= __('errors.404_title') ?></div>
  <div style="color:var(--mt);max-width:360px;margin-bottom:28px"><?= __('errors.404_desc') ?></div>
  <a href="<?= url('/') ?>" class="btn btn-primary"><?= __('errors.go_home') ?></a>
</div>
<?php partial('layout/footer') ?>
