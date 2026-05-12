<?php partial('layout/header', ['page_title' => __('settings.title'), 'active_page' => 'settings', 'breadcrumbs' => [['label' => __('settings.title')]]]) ?>

<div class="sec-head">
  <div class="sec-title">🔧 <?= __('settings.title') ?></div>
</div>

<?php $tab = get('tab','geral'); ?>
<div class="tabs" style="display: flex;
    gap: 10px;
    margin-bottom: 22px;
    border-bottom: 1px solid var(--bd);
    width: 100%;
    overflow-x: overlay;
    overflow-y: hidden;
    padding-bottom: 10px;">
  <?php foreach ([
    'geral'    => __('settings.tab_general'),
    'tema'     => __('settings.tab_theme'),
    'usuarios' => __('settings.tab_users'),
    'lang'     => __('settings.tab_lang'),
  ] as $k => $label): ?>
  <a href="?tab=<?= $k ?>" style="padding:10px 16px;font-size:.855rem;font-weight:600;text-decoration:none;border-bottom:2px solid <?= $tab===$k?'var(--ac)':'transparent' ?>;color:<?= $tab===$k?'var(--ac)':'var(--mt2)' ?>;margin-bottom:-1px;transition:all .18s;white-space:nowrap">
    <?= $label ?>
  </a>
  <?php endforeach; ?>
</div>

<?php if ($tab === 'geral'): ?>
<div class="card">
  <div class="card-title"><?= __('settings.system_data') ?></div>
  <form method="POST" action="<?= url('settings');?>">
    <input type="hidden" name="tab" value="geral">
    <div class="row2">
      <div class="field"><label><?= __('settings.app_name') ?></label>
        <input type="text" name="app_name" value="<?= h(DB::setting('app_name','FlexCore')) ?>"></div>
      <div class="field"><label><?= __('settings.app_url') ?></label>
        <input type="url" name="app_url" value="<?= h(DB::setting('app_url')) ?>" placeholder="https://"></div>
    </div>
    <div class="field"><label><?= __('settings.app_logo') ?></label>
      <input type="text" name="app_logo" value="<?= h(DB::setting('app_logo')) ?>" placeholder="https://...">
      <div class="hint"><?= __('settings.app_logo_hint') ?></div>
      <?php if (DB::setting('app_logo')): ?>
      <img src="<?= h(DB::setting('app_logo')) ?>" style="margin-top:8px;max-height:48px;border-radius:6px;background:var(--sf2);padding:4px">
      <?php endif; ?>
    </div>
    <div class="field"><label><?= __('settings.app_favicon') ?></label>
      <input type="text" name="app_favicon" value="<?= h(DB::setting('app_favicon')) ?>" placeholder="https://.../favicon.ico">
      <div class="hint"><?= __('settings.app_favicon_hint') ?></div>
    </div>
    <div class="form-actions"><button type="submit" class="btn btn-primary">💾 <?= __('general.save') ?></button></div>
  </form>
</div>

<?php elseif ($tab === 'tema'): ?>
<?php
$presets = [
  'default'=>['label'=>__('theme.presets.default'),'ac'=>'#00d4ff','ac2'=>'#6c5ce7'],
  'ocean'  =>['label'=>__('theme.presets.ocean'),  'ac'=>'#3b82f6','ac2'=>'#06b6d4'],
  'forest' =>['label'=>__('theme.presets.forest'), 'ac'=>'#22c55e','ac2'=>'#10b981'],
  'sunset' =>['label'=>__('theme.presets.sunset'), 'ac'=>'#f97316','ac2'=>'#ef4444'],
  'violet' =>['label'=>__('theme.presets.violet'), 'ac'=>'#a855f7','ac2'=>'#ec4899'],
  'rose'   =>['label'=>__('theme.presets.rose'),   'ac'=>'#f43f5e','ac2'=>'#fb923c'],
  'mono'   =>['label'=>__('theme.presets.mono'),   'ac'=>'#94a3b8','ac2'=>'#64748b'],
];
$curPreset = DB::setting('theme_preset','default');
$curMode   = DB::setting('theme_mode','dark');
$curAc     = DB::setting('color_accent','');
$curAc2    = DB::setting('color_accent2','');
?>
<form method="POST" action="<?= url('settings') ?>">
  <input type="hidden" name="tab" value="tema">

  <div class="card" style="margin-bottom:14px">
    <div class="card-title">🌓 <?= __('theme.mode') ?></div>
    <div style="display:flex;gap:10px;flex-wrap:wrap">
      <?php foreach(['dark'=>'🌙 '.__('theme.dark'),'light'=>'☀️ '.__('theme.light')] as $m=>$ml): ?>
      <label style="flex:1;cursor:pointer;min-width:120px">
        <input type="radio" name="theme_mode" value="<?= $m ?>" <?= $curMode===$m?'checked':'' ?> style="display:none" onchange="applyMode(this.value)">
        <div class="mode-opt <?= $curMode===$m?'active':'' ?>" data-mode="<?= $m ?>">
          <div style="font-size:1.4rem;margin-bottom:6px"><?= $m==='dark'?'🌙':'☀️' ?></div>
          <div style="font-weight:700;font-size:.88rem"><?= $ml ?></div>
        </div>
      </label>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="card" style="margin-bottom:14px">
    <div class="card-title">🎨 <?= __('theme.preset') ?></div>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(130px,1fr));gap:10px;margin-bottom:16px">
      <?php foreach($presets as $pk=>$pv): ?>
      <label style="cursor:pointer">
        <input type="radio" name="theme_preset" value="<?= $pk ?>" <?= $curPreset===$pk?'checked':'' ?> style="display:none"
               onchange="applyPreset('<?= $pk ?>','<?= $pv['ac'] ?>','<?= $pv['ac2'] ?>')">
        <div class="preset-card <?= $curPreset===$pk?'active':'' ?>" data-preset="<?= $pk ?>">
          <div style="display:flex;gap:6px;margin-bottom:8px">
            <div style="width:20px;height:20px;border-radius:50%;background:<?= $pv['ac'] ?>"></div>
            <div style="width:20px;height:20px;border-radius:50%;background:<?= $pv['ac2'] ?>"></div>
          </div>
          <div style="font-size:.78rem;font-weight:600"><?= $pv['label'] ?></div>
        </div>
      </label>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="card" style="margin-bottom:14px">
    <div class="card-title">🖌️ <?= __('theme.custom_colors') ?></div>
    <div class="row2">
      <div class="field">
        <label><?= __('theme.accent') ?></label>
        <div style="display:flex;gap:8px;align-items:center">
          <input type="color" id="inp-ac" name="color_accent" value="<?= h($curAc ?: '#00d4ff') ?>" style="height:42px;padding:4px 8px;width:60px" oninput="previewColors()">
          <input type="text" id="txt-ac" value="<?= h($curAc) ?>" placeholder="#00d4ff" style="flex:1" oninput="syncColor('inp-ac',this.value);previewColors()">
          <button type="button" onclick="clearColor('inp-ac','txt-ac','color_accent')" class="btn btn-ghost btn-sm">✕</button>
        </div>
      </div>
      <div class="field">
        <label><?= __('theme.accent2') ?></label>
        <div style="display:flex;gap:8px;align-items:center">
          <input type="color" id="inp-ac2" name="color_accent2" value="<?= h($curAc2 ?: '#6c5ce7') ?>" style="height:42px;padding:4px 8px;width:60px" oninput="previewColors()">
          <input type="text" id="txt-ac2" value="<?= h($curAc2) ?>" placeholder="#6c5ce7" style="flex:1" oninput="syncColor('inp-ac2',this.value);previewColors()">
          <button type="button" onclick="clearColor('inp-ac2','txt-ac2','color_accent2')" class="btn btn-ghost btn-sm">✕</button>
        </div>
      </div>
    </div>
    <div style="padding:14px;background:var(--sf2);border-radius:var(--r2);margin-top:4px">
      <div style="font-size:.72rem;color:var(--mt);margin-bottom:10px;text-transform:uppercase;letter-spacing:.08em"><?= __('theme.preview') ?></div>
      <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
        <button type="button" id="prev-btn" class="btn btn-primary"><?= __('general.save') ?></button>
        <span id="prev-badge" class="badge bc">Badge</span>
      </div>
    </div>
  </div>

  <div class="form-actions">
    <button type="submit" class="btn btn-primary">💾 <?= __('general.save') ?></button>
  </div>
</form>

<style>
.mode-opt { border:2px solid var(--bd);border-radius:var(--r2);padding:14px;text-align:center;transition:all .18s; }
.mode-opt:hover,.mode-opt.active { border-color:var(--ac);background:color-mix(in srgb,var(--ac) 8%,transparent); }
.preset-card { border:2px solid var(--bd);border-radius:var(--r2);padding:12px;transition:all .18s; }
.preset-card:hover,.preset-card.active { border-color:var(--ac);background:color-mix(in srgb,var(--ac) 8%,transparent); }
</style>

<script>
function applyMode(m) {
  document.documentElement.setAttribute('data-theme', m);
  document.querySelectorAll('.mode-opt').forEach(el => el.classList.toggle('active', el.dataset.mode===m));
}
function applyPreset(pk, ac, ac2) {
  document.documentElement.style.setProperty('--ac', ac);
  document.documentElement.style.setProperty('--ac2', ac2);
  document.querySelectorAll('.preset-card').forEach(el => el.classList.toggle('active', el.dataset.preset===pk));
  document.getElementById('txt-ac').value = '';
  document.getElementById('txt-ac2').value = '';
  document.querySelector('[name="color_accent"]').value = ac;
  document.querySelector('[name="color_accent2"]').value = ac2;
}
function previewColors() {
  const ac = document.getElementById('inp-ac').value;
  document.documentElement.style.setProperty('--ac', ac);
}
function syncColor(inputId, val) {
  if (/^#[0-9a-fA-F]{6}$/.test(val)) document.getElementById(inputId).value = val;
}
function clearColor(inputId, txtId, name) {
  document.getElementById(inputId).value = '#00d4ff';
  document.getElementById(txtId).value = '';
  document.querySelector('[name="'+name+'"]').value = '';
}
</script>

<?php elseif ($tab === 'usuarios'): ?>
<div class="row2" style="gap:18px;align-items:start;overflow:hidden">
  <div style="flex:2;min-width:0;overflow:hidden">
    <div class="card">
      <div class="card-title"><?= __('users.title') ?></div>
      <div class="tbl-wrap">
        <table>
          <thead><tr>
            <th><?= __('general.name') ?></th>
            <th><?= __('users.email') ?></th>
            <th><?= __('users.role') ?></th>
            <th><?= __('users.last_login') ?></th>
            <th><?= __('general.status') ?></th>
            <th></th>
          </tr></thead>
          <tbody>
          <?php foreach ($users as $u): ?>
          <tr>
            <td style="font-weight:600;color:var(--tx)"><?= h($u['name']) ?></td>
            <td><?= h($u['email']) ?></td>
            <td>
              <?= match($u['role']) {
                'admin'  => '<span class="badge bc">'.__('users.roles.admin').'</span>',
                'editor' => '<span class="badge bg">'.__('users.roles.editor').'</span>',
                default  => '<span class="badge bm">'.__('users.roles.viewer').'</span>',
              } ?>
            </td>
            <td style="font-size:.78rem;color:var(--mt)"><?= $u['last_login'] ? dateBr($u['last_login']) : '—' ?></td>
            <td><?= $u['active'] ? '<span class="badge bg">'.__('general.active').'</span>' : '<span class="badge bm">'.__('general.inactive').'</span>' ?></td>
            <td>
              <?php if ($u['id'] !== Auth::id()): ?>
              <div class="td-actions">
                <button type="button" onclick="editarUsuario(<?= h(json_encode($u)) ?>)" class="btn btn-ghost btn-xs"><?= __('general.edit') ?></button>
                <form method="POST" action="<?= url('users/'. $u['id'].'/delete');?>" style="display:inline"
                      onsubmit="return confirm('<?= __('users.delete_confirm') ?>')">
                  <button type="submit" class="btn btn-danger btn-xs"><?= __('general.delete') ?></button>
                </form>
              </div>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div>
    <div class="card" id="user-form-card">
      <div class="card-title" id="user-form-title">👤 <?= __('users.new') ?></div>
      <form method="POST" id="user-form" action="<?= url('users/create');?>">
        <input type="hidden" name="user_id" id="usr-id" value="">
        <div class="field"><label><?= __('users.name') ?> *</label>
          <input type="text" name="name" id="usr-name" required></div>
        <div class="field"><label><?= __('users.email') ?> *</label>
          <input type="email" name="email" id="usr-email" required></div>
        <div class="field"><label><?= __('users.password') ?> <span id="pwd-hint" style="color:var(--mt);font-weight:400"><?= __('general.required') ?></span></label>
          <input type="password" name="password" id="usr-pwd" autocomplete="new-password"></div>
        <div class="field"><label><?= __('users.role') ?></label>
          <select name="role" id="usr-role">
            <option value="viewer"><?= __('users.roles.viewer') ?></option>
            <option value="editor"><?= __('users.roles.editor') ?></option>
            <option value="admin"><?= __('users.roles.admin') ?></option>
          </select>
        </div>
        <div class="form-actions">
          <button type="button" onclick="resetUserForm()" id="btn-usr-cancel" class="btn btn-ghost" style="display:none"><?= __('general.cancel') ?></button>
          <button type="submit" id="btn-usr-submit" class="btn btn-primary"><?= __('general.create') ?></button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php elseif ($tab === 'lang'): ?>
<?php $langs = availableLanguages(); $curLang = DB::setting('app_lang','pt_BR'); ?>
<div class="card">
  <div class="card-title">🌐 <?= __('settings.tab_lang') ?></div>
  <form method="POST" action="<?= url('settings') ?>">
    <input type="hidden" name="tab" value="lang">
    <div class="field">
      <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:10px;margin-top:8px">
        <?php foreach ($langs as $lk => $lm): ?>
        <label style="cursor:pointer">
          <input type="radio" name="app_lang" value="<?= h($lk) ?>" <?= $curLang===$lk?'checked':'' ?> style="display:none">
          <div style="border:2px solid <?= $curLang===$lk?'var(--ac)':'var(--bd)' ?>;border-radius:var(--r2);padding:14px;text-align:center;transition:all .18s"
               onmouseover="this.style.borderColor='var(--ac)'" onmouseout="this.style.borderColor='<?= $curLang===$lk?'var(--ac)':'var(--bd)' ?>'">
            <div style="font-size:1.8rem;margin-bottom:6px"><?= h($lm['flag'] ?? '🌐') ?></div>
            <div style="font-weight:700;font-size:.88rem"><?= h($lm['name'] ?? $lk) ?></div>
            <div style="font-size:.72rem;color:var(--mt);margin-top:2px"><?= h($lk) ?></div>
          </div>
        </label>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="form-actions"><button type="submit" class="btn btn-primary">💾 <?= __('general.save') ?></button></div>
  </form>
</div>
<?php endif; ?>

<script>
function editarUsuario(u) {
  document.getElementById('user-form-title').textContent = '✏️ <?= __('users.edit') ?>';
  document.getElementById('user-form').action = '<?= url('/users/') ?>' + u.id + '/update';
  document.getElementById('usr-id').value   = u.id;
  document.getElementById('usr-name').value = u.name;
  document.getElementById('usr-email').value = u.email;
  document.getElementById('usr-role').value  = u.role;
  document.getElementById('usr-pwd').required = false;
  document.getElementById('pwd-hint').textContent = '(<?= __('users.password_hint') ?>)';
  document.getElementById('btn-usr-submit').textContent = '💾 <?= __('general.save') ?>';
  document.getElementById('btn-usr-cancel').style.display = '';
}
function resetUserForm() {
  document.getElementById('user-form-title').textContent = '👤 <?= __('users.new') ?>';
  document.getElementById('user-form').action = '<?= url('/users/create') ?>';
  document.getElementById('user-form').reset();
  document.getElementById('usr-id').value = '';
  document.getElementById('usr-pwd').required = true;
  document.getElementById('pwd-hint').textContent = '<?= __('general.required') ?>';
  document.getElementById('btn-usr-submit').textContent = '<?= __('general.create') ?>';
  document.getElementById('btn-usr-cancel').style.display = 'none';
}
</script>

<?php partial('layout/footer') ?>