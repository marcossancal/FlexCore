<?php
$page_title  = __('plugins.title');
$active_page = 'plugins';
$breadcrumbs = [['label' => __('plugins.title')]];
partial('layout/header');
?>

<div class="sec-head">
  <div>
    <div class="sec-title">🧩 <?= __('plugins.title') ?></div>
    <div class="sec-sub"><?= __('plugins.no_plugins') ?></div>
  </div>
  <div class="sec-actions">
    <a href="<?= url('/plugins/docs') ?>" class="btn btn-ghost btn-sm"><?= __('plugins.how_to_create') ?></a>
    <button class="btn btn-primary" onclick="document.getElementById('up-modal').style.display='flex'"><?= __('plugins.install') ?></button>
  </div>
</div>

<?php if (empty($plugins)): ?>
<div class="card" style="text-align:center;padding:48px">
  <div style="font-size:2.5rem;margin-bottom:14px">🧩</div>
  <div style="font-weight:700;margin-bottom:8px"><?= __('plugins.no_plugins') ?></div>
  <button class="btn btn-primary" onclick="document.getElementById('up-modal').style.display='flex'" style="margin:0 auto"><?= __('plugins.install') ?></button>
</div>
<?php else: ?>
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px">
  <?php foreach ($plugins as $p):
    $manifest = json_decode($p['manifest'], true) ?? [];
    $hooks    = $manifest['hooks'] ?? [];
  ?>
  <div class="card" style="margin:0;display:flex;flex-direction:column">
    <div style="display:flex;align-items:flex-start;gap:12px;margin-bottom:12px">
      <span style="font-size:1.8rem">🧩</span>
      <div style="flex:1">
        <div style="font-weight:700;font-size:.95rem"><?= h($p['name']) ?></div>
        <div style="font-size:.75rem;color:var(--mt)">v<?= h($p['version']) ?><?= $p['author'] ? ' · '.h($p['author']) : '' ?></div>
      </div>
      <span class="badge <?= $p['active']?'bg':'br' ?>"><?= $p['active']?__('general.active'):__('general.inactive') ?></span>
    </div>

    <p style="color:var(--mt);font-size:.83rem;line-height:1.6;flex:1;margin-bottom:12px"><?= h($p['description']??'') ?></p>

    <?php if (!empty($hooks)): ?>
    <div style="margin-bottom:12px">
      <div style="font-size:.7rem;color:var(--mt);font-weight:700;text-transform:uppercase;letter-spacing:.07em;margin-bottom:6px"><?= __('plugins.hooks') ?></div>
      <div style="display:flex;flex-wrap:wrap;gap:4px">
        <?php foreach ($hooks as $hk): ?>
        <code style="background:var(--sf2);padding:2px 7px;border-radius:4px;font-size:.7rem;color:var(--mt2)"><?= h($hk) ?></code>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <div style="display:flex;gap:8px;padding-top:12px;border-top:1px solid var(--bd);flex-wrap:wrap">
      <?php if (!empty($manifest['settings'])): ?>
      <button class="btn btn-ghost btn-sm" onclick='openSettings(<?= json_encode($p) ?>)'><?= __('plugins.configure') ?></button>
      <?php endif; ?>
      <form method="POST" action="<?= url('/plugins/'.$p['plugin_id'].'/toggle') ?>" style="display:inline">
        <button class="btn btn-ghost btn-sm"><?= $p['active']?'⏸ '.__('general.disable'):'▶️ '.__('general.enable') ?></button>
      </form>
      <form method="POST" action="<?= url('/plugins/'.$p['plugin_id'].'/uninstall') ?>" style="display:inline;margin-left:auto"
            onsubmit="return confirm('<?= __('plugins.uninstall_confirm') ?>')">
        <button class="btn btn-danger btn-sm"><?= __('plugins.uninstall') ?></button>
      </form>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Upload modal -->
<div id="up-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.65);z-index:300;align-items:center;justify-content:center">
  <div style="backdrop-filter:blur(7px); background:var(--sf);border:1px solid var(--bd2);border-radius:var(--r);padding:28px;width:100%;max-width:440px;margin:20px">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
      <div style="font-family:var(--fd);font-weight:700"><?= __('plugins.install') ?></div>
      <button onclick="document.getElementById('up-modal').style.display='none'"
              style="background:none;border:none;color:var(--mt);cursor:pointer;font-size:1.2rem">✕</button>
    </div>
    <form method="POST" action="<?= url('/plugins/install') ?>" enctype="multipart/form-data">
      <div class="field">
        <label><?= __('plugins.install') ?> (.zip) *</label>
        <input type="file" name="plugin_zip" accept=".zip" required
               style="background:var(--sf2);border:1px solid var(--bd2);border-radius:var(--r2);color:var(--tx);padding:9px 12px">
      </div>
      <div class="form-actions">
        <button type="button" onclick="document.getElementById('up-modal').style.display='none'" class="btn btn-ghost"><?= __('general.cancel') ?></button>
        <button type="submit" class="btn btn-primary"><?= __('plugins.install') ?></button>
      </div>
    </form>
  </div>
</div>

<!-- Settings modal -->
<div id="cfg-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.65);z-index:300;align-items:center;justify-content:center">
  <div style="background:var(--sf);border:1px solid var(--bd2);border-radius:var(--r);padding:28px;width:100%;max-width:480px;margin:20px">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
      <div style="font-family:var(--fd);font-weight:700" id="cfg-title"><?= __('plugins.configure') ?></div>
      <button onclick="document.getElementById('cfg-modal').style.display='none'"
              style="background:none;border:none;color:var(--mt);cursor:pointer;font-size:1.2rem">✕</button>
    </div>
    <form method="POST" id="cfg-form">
      <div id="cfg-fields"></div>
      <div class="form-actions">
        <button type="button" onclick="document.getElementById('cfg-modal').style.display='none'" class="btn btn-ghost"><?= __('general.cancel') ?></button>
        <button type="submit" class="btn btn-primary">💾 <?= __('general.save') ?></button>
      </div>
    </form>
  </div>
</div>

<script>
function openSettings(p) {
  const manifest  = JSON.parse(p.manifest || '{}');
  const settings  = JSON.parse(p.settings || '{}');
  const schema    = manifest.settings || [];
  document.getElementById('cfg-title').textContent = p.name + ' — <?= __('plugins.configure') ?>';
  document.getElementById('cfg-form').action = '<?= url('/plugins/') ?>' + p.plugin_id + '/settings';
  const inp = s => s.type === 'textarea'
    ? `<textarea name="settings[${s.key}]" rows="3">${settings[s.key]||''}</textarea>`
    : `<input type="${s.type||'text'}" name="settings[${s.key}]" value="${settings[s.key]||''}" ${s.required?'required':''}>`;
  document.getElementById('cfg-fields').innerHTML = schema.length
    ? schema.map(s => `<div class="field"><label>${s.label}${s.required?' *':''}</label>${inp(s)}${s.hint?`<div class="hint">${s.hint}</div>`:''}</div>`).join('')
    : '<p style="color:var(--mt)">—</p>';
  document.getElementById('cfg-modal').style.display = 'flex';
}
</script>

<?php partial('layout/footer'); ?>
