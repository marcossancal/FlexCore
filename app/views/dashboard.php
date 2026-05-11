<?php partial('layout/header', ['page_title' => __('dashboard.title'), 'active_page' => 'home']) ?>

<div class="sec-head">
  <div>
    <div class="sec-title">👋 <?= __('dashboard.welcome') ?>, <?= h(explode(' ', Auth::user()['name'])[0]) ?>!</div>
    <div class="sec-sub"><?= h(DB::setting('app_name','FlexCore')) ?></div>
  </div>
</div>

<?php if (empty($entities)): ?>
<div class="card" style="text-align:center;padding:56px 32px">
  <div style="font-size:3rem;margin-bottom:16px">🧩</div>
  <div style="font-family:var(--fd);font-size:1.2rem;font-weight:700;margin-bottom:8px"><?= __('dashboard.no_entities') ?></div>
  <div style="color:var(--mt);font-size:.9rem;margin-bottom:28px;max-width:420px;margin-left:auto;margin-right:auto">
    <?= __('entities.next_step_desc') ?>
  </div>
  <?php if (Auth::user()['role']==='admin'): ?>
  <a href="<?= url('/entities/new') ?>" class="btn btn-primary">⚙️ <?= __('dashboard.create_first') ?></a>
  <?php endif; ?>
</div>
<?php else: ?>

<div class="stats">
  <?php foreach ($entities as $ent): ?>
  <a href="<?= url('/e/' . h($ent['slug'])) ?>" style="text-decoration:none">
    <div class="stat" style="border-color: color-mix(in srgb, <?= h($ent['color']) ?> 25%, var(--bd));cursor:pointer;transition:all .18s"
         onmouseenter="this.style.borderColor='<?= h($ent['color']) ?>'" onmouseleave="this.style.borderColor=''">
      <div class="stat-ico"><?= h($ent['icon']) ?></div>
      <div class="stat-val" style="color:<?= h($ent['color']) ?>"><?= number_format($ent['count']) ?></div>
      <div class="stat-lbl"><?= h($ent['name']) ?></div>
    </div>
  </a>
  <?php endforeach; ?>
</div>

<div class="row2" style="gap:18px;align-items:start">
  <?php foreach (array_slice($entities, 0, 2) as $ent): ?>
  <div class="card">
    <div class="card-title" style="display:flex;align-items:center;justify-content:space-between">
      <span><?= h($ent['icon']) ?> <?= h($ent['name']) ?></span>
      <a href="<?= url('/e/' . h($ent['slug'])) ?>" class="btn btn-ghost btn-xs"><?= __('dashboard.view_all') ?></a>
    </div>
    <?php if (empty($ent['recents'])): ?>
    <p style="color:var(--mt);font-size:.85rem">—</p>
    <?php else: ?>
    <div class="tbl-wrap">
      <table>
        <tbody>
        <?php foreach ($ent['recents'] as $rec): ?>
        <tr>
          <td><a href="<?= url('/e/' . h($ent['slug']) . '/' . $rec['id']) ?>"><?= h($rec['label'] ?? '#'.$rec['id']) ?></a></td>
          <td style="font-size:.78rem;color:var(--mt);text-align:right"><?= dateBr($rec['created_at']) ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php partial('layout/footer') ?>
