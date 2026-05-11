<?php
$page_title  = __('api.page_title_keys');
$active_page = 'api';
$breadcrumbs = [['label' => __('api.page_title_keys')]];
partial('layout/header');
?>

<div class="sec-head">
  <div>
    <div class="sec-title">🔑 <?= __('api.title') ?></div>
    <div class="sec-sub"><?= __('api.how_to_use') ?></div>
  </div>
  <div class="sec-actions">
    <a href="<?= url('/api/docs') ?>" class="btn btn-ghost btn-sm">📄 <?= __('nav.api_docs') ?></a>
    <button class="btn btn-primary" onclick="openCreate()">+ <?= __('api.new_key') ?></button>
  </div>
</div>

<div class="card" style="border-color:rgba(0,212,255,.2);background:rgba(0,212,255,.04);margin-bottom:18px;padding:16px 20px">
  <div style="display:flex;gap:14px;align-items:flex-start">
    <span style="font-size:1.3rem">🔌</span>
    <div>
      <div style="font-weight:700;margin-bottom:4px;font-size:.875rem"><?= __('api.how_to_use') ?></div>
      <div style="color:var(--mt);font-size:.82rem;line-height:1.8">
        Header: <code style="background:var(--sf2);padding:1px 7px;border-radius:4px;color:var(--ac)">Authorization: Bearer SUA_CHAVE</code><br>
        <?= __('api.base_url') ?>: <code style="background:var(--sf2);padding:1px 7px;border-radius:4px;color:var(--ac)"><?= h(rtrim(DB::setting('app_url'),'/')) ?>/api/v1/{<?= __('nav.entities') ?>}</code>
      </div>
    </div>
  </div>
</div>

<div class="card" style="padding:0">
  <div class="tbl-wrap">
    <table>
      <thead>
        <tr>
          <th><?= __('general.name') ?></th>
          <th><?= __('api.key_preview') ?></th>
          <th><?= __('api.scope') ?></th>
          <th><?= __('api.rate_limit') ?></th>
          <th><?= __('api.last_used') ?></th>
          <th><?= __('api.expires') ?></th>
          <th><?= __('general.status') ?></th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($keys)): ?>
        <tr class="empty-row"><td colspan="8"><?= __('general.no_results') ?></td></tr>
        <?php else: foreach ($keys as $k):
          $perms = json_decode($k['permissions'], true) ?? [];
        ?>
        <tr>
          <td style="font-weight:600;color:var(--tx)"><?= h($k['name']) ?></td>
          <td><code style="background:var(--sf2);padding:3px 8px;border-radius:6px;font-size:.78rem;color:var(--ac)"><?= h($k['key_preview']) ?>••••••••</code></td>
          <td><?= ($perms['scope']??'') === 'all'
              ? '<span class="badge bg">'.__('api.scope_all').'</span>'
              : '<span class="badge bm">'.count($perms['entities']??[]).' '.strtolower(__('nav.entities')).'</span>' ?></td>
          <td style="color:var(--mt)"><?= (int)$k['rate_limit'] ?>/min</td>
          <td style="color:var(--mt);font-size:.8rem"><?= $k['last_used_at'] ? date('d/m/Y H:i',strtotime($k['last_used_at'])) : '—' ?></td>
          <td style="color:var(--mt);font-size:.8rem"><?= $k['expires_at'] ? date('d/m/Y',strtotime($k['expires_at'])) : __('api.expires_never') ?></td>
          <td><span class="badge <?= $k['active'] ? 'bg' : 'br' ?>"><?= $k['active'] ? __('general.active') : __('general.inactive') ?></span></td>
          <td>
            <div class="td-actions">
              <button class="btn btn-ghost btn-xs" onclick='openEdit(<?= json_encode($k) ?>)'>✏️</button>
              <form method="POST" action="<?= url('/api/keys/'.$k['id'].'/toggle') ?>" style="display:inline">
                <button class="btn btn-ghost btn-xs"><?= $k['active'] ? '⏸' : '▶️' ?></button>
              </form>
              <form method="POST" action="<?= url('/api/keys/'.$k['id'].'/delete') ?>" style="display:inline"
                    onsubmit="return confirm('<?= __('api.revoke_confirm') ?>')">
                <button class="btn btn-danger btn-xs">🗑</button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal -->
<div id="modal-overlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:300;align-items:center;justify-content:center">
  <div style="background:var(--sf);border:1px solid var(--bd2);border-radius:var(--r);padding:28px;width:100%;max-width:540px;margin:20px;max-height:90vh;overflow-y:auto">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
      <div style="font-family:var(--fd);font-weight:700" id="modal-title"><?= __('api.new_key') ?></div>
      <button onclick="closeModal()" style="background:none;border:none;color:var(--mt);cursor:pointer;font-size:1.2rem">✕</button>
    </div>

    <div id="key-reveal" style="display:none;background:rgba(34,197,94,.08);border:1px solid rgba(34,197,94,.25);border-radius:var(--r2);padding:14px;margin-bottom:18px">
      <div style="font-size:.75rem;color:#86efac;font-weight:700;margin-bottom:8px"><?= __('api.copy_now') ?></div>
      <div style="display:flex;gap:8px;align-items:center">
        <code id="key-value" style="flex:1;background:var(--sf2);padding:8px;border-radius:6px;font-size:.8rem;color:var(--ac);word-break:break-all"></code>
        <button onclick="copyKey()" class="btn btn-ghost btn-sm">📋 <?= __('general.copied') ?></button>
      </div>
    </div>

    <form method="POST" id="modal-form">
      <div class="field"><label><?= __('api.key_name') ?> *</label>
        <input type="text" name="name" id="f-name" placeholder="Ex: Integração n8n, App Mobile..." required></div>
      <div class="row2">
        <div class="field"><label><?= __('api.rate_limit') ?></label>
          <input type="number" name="rate_limit" id="f-rate" value="60" min="1"></div>
        <div class="field"><label><?= __('api.expires') ?></label>
          <input type="date" name="expires_at" id="f-expires">
          <div class="hint"><?= __('api.expires_never') ?></div></div>
      </div>
      <div class="field"><label><?= __('api.scope') ?></label>
        <select name="scope" id="f-scope" onchange="togglePerms(this.value)">
          <option value="all"><?= __('api.scope_all') ?></option>
          <option value="custom"><?= __('api.scope_custom') ?></option>
        </select></div>
      <div id="entity-perms" style="display:none">
        <div style="font-size:.73rem;font-weight:700;color:var(--mt);text-transform:uppercase;letter-spacing:.08em;margin-bottom:10px"><?= __('general.actions') ?></div>
        <?php foreach ($entities as $ent): ?>
        <div style="background:var(--sf2);border-radius:var(--r2);padding:10px 12px;margin-bottom:6px">
          <div style="font-weight:600;font-size:.85rem;margin-bottom:8px"><?= h($ent['icon'].' '.$ent['name']) ?></div>
          <div style="display:flex;gap:14px;flex-wrap:wrap">
            <?php foreach(['read','create','update','delete'] as $op): ?>
            <label style="display:flex;align-items:center;gap:5px;font-size:.8rem;cursor:pointer">
              <input type="checkbox" name="perm[<?= $ent['slug'] ?>][]" value="<?= $op ?>" style="width:auto"> <?= ucfirst($op) ?>
            </label>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <div class="form-actions">
        <button type="button" onclick="closeModal()" class="btn btn-ghost"><?= __('general.cancel') ?></button>
        <button type="submit" class="btn btn-primary" id="modal-btn"><?= __('api.new_key') ?></button>
      </div>
    </form>
  </div>
</div>

<script>
const OV = document.getElementById('modal-overlay');
function openCreate() {
  document.getElementById('modal-title').textContent = '<?= __('api.new_key') ?>';
  document.getElementById('modal-form').action = '<?= url('/api/keys/create') ?>';
  document.getElementById('modal-btn').textContent = '<?= __('api.new_key') ?>';
  document.getElementById('key-reveal').style.display = 'none';
  ['f-name','f-rate','f-expires'].forEach(id => document.getElementById(id).value = id==='f-rate'?'60':'');
  document.getElementById('f-scope').value = 'all'; togglePerms('all');
  OV.style.display = 'flex';
}
function openEdit(k) {
  document.getElementById('modal-title').textContent = '<?= __('general.edit') ?>';
  document.getElementById('modal-form').action = '<?= url('/api/keys/') ?>'+k.id+'/update';
  document.getElementById('modal-btn').textContent = '<?= __('general.save') ?>';
  document.getElementById('key-reveal').style.display = 'none';
  document.getElementById('f-name').value = k.name;
  document.getElementById('f-rate').value = k.rate_limit;
  document.getElementById('f-expires').value = k.expires_at ? k.expires_at.substring(0,10) : '';
  const p = JSON.parse(k.permissions||'{}');
  const scope = p.scope === 'all' ? 'all' : 'custom';
  document.getElementById('f-scope').value = scope; togglePerms(scope);
  OV.style.display = 'flex';
}
function closeModal() { OV.style.display = 'none'; }
function togglePerms(v) { document.getElementById('entity-perms').style.display = v==='custom'?'block':'none'; }
function copyKey() { navigator.clipboard.writeText(document.getElementById('key-value').textContent); }
<?php if (!empty($newKey)): ?>
document.addEventListener('DOMContentLoaded',() => {
  openCreate();
  document.getElementById('key-reveal').style.display = 'block';
  document.getElementById('key-value').textContent = <?= json_encode($newKey) ?>;
});
<?php endif; ?>
OV.addEventListener('click', e => { if(e.target===OV) closeModal(); });
</script>

<?php partial('layout/footer'); ?>
