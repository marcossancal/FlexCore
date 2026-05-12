<?php
$page_title  = __('automations.title');
$active_page = 'automations';
$breadcrumbs = [['label' => __('automations.title')]];
partial('layout/header');
?>

<div class="sec-head">
  <div>
    <div class="sec-title">⚡ <?= __('automations.title') ?></div>
    <div class="sec-sub"><?= __('automations.no_automations') ?></div>
  </div>
  <button class="btn btn-primary" onclick="openBuilder()"> <?= __('automations.new') ?></button>
</div>

<div class="stats" style="margin-bottom:20px">
  <div class="stat"><div class="stat-ico">⚡</div><div class="stat-val"><?= count($automations) ?></div><div class="stat-lbl"><?= __('general.total') ?></div></div>
  <div class="stat"><div class="stat-ico">✅</div><div class="stat-val"><?= count(array_filter($automations,fn($a)=>$a['active'])) ?></div><div class="stat-lbl"><?= __('general.active') ?></div></div>
  <div class="stat"><div class="stat-ico">🔄</div><div class="stat-val"><?= array_sum(array_column($automations,'run_count')) ?></div><div class="stat-lbl"><?= __('automations.runs') ?></div></div>
</div>

<?php if (empty($automations)): ?>
<div class="card" style="text-align:center;padding:48px">
  <div style="font-size:2.5rem;margin-bottom:14px">⚡</div>
  <div style="font-weight:700;margin-bottom:8px"><?= __('automations.no_automations') ?></div>
  <button class="btn btn-primary" onclick="openBuilder()" style="margin:0 auto"> <?= __('automations.new') ?></button>
</div>
<?php else: ?>
<div class="card" style="padding:0">
  <div class="tbl-wrap">
    <table>
      <thead><tr>
        <th><?= __('general.name') ?></th>
        <th><?= __('automations.trigger') ?></th>
        <th><?= __('automations.action') ?></th>
        <th><?= __('automations.entity') ?></th>
        <th><?= __('automations.runs') ?></th>
        <th><?= __('automations.last_run') ?></th>
        <th><?= __('general.status') ?></th>
        <th></th>
      </tr></thead>
      <tbody>
        <?php foreach ($automations as $a): $ent = $entitiesMap[$a['trigger_entity_id']] ?? null; ?>
        <tr>
          <td>
            <div style="font-weight:600;color:var(--tx)"><?= h($a['name']) ?></div>
            <?php if ($a['description']): ?><div style="font-size:.75rem;color:var(--mt)"><?= h(mb_substr($a['description'],0,60)) ?></div><?php endif; ?>
          </td>
          <td><span class="badge ba"><?= __('automations.events.'.$a['trigger_event']) ?></span></td>
          <td><span class="badge bm"><?= __('automations.actions.'.$a['action_type']) ?></span></td>
          <td style="color:var(--mt)"><?= $ent ? h($ent['icon'].' '.$ent['name']) : '—' ?></td>
          <td style="color:var(--mt)"><?= number_format((int)$a['run_count']) ?></td>
          <td style="color:var(--mt);font-size:.8rem"><?= $a['last_run_at'] ? date('d/m/Y H:i',strtotime($a['last_run_at'])) : '—' ?></td>
          <td><span class="badge <?= $a['active'] ? 'bg':'br' ?>"><?= $a['active'] ? __('general.active') : __('general.inactive') ?></span></td>
          <td>
            <div class="td-actions">
              <a href="<?= url('/automations/'.$a['id'].'/logs') ?>" class="btn btn-ghost btn-xs">📋</a>
              <button class="btn btn-ghost btn-xs" onclick='openEdit(<?= json_encode($a) ?>)'>✏️</button>
              <form method="POST" action="<?= url('/automations/'.$a['id'].'/toggle') ?>" style="display:inline">
                <button class="btn btn-ghost btn-xs"><?= $a['active']?'⏸':'▶️' ?></button>
              </form>
              <form method="POST" action="<?= url('/automations/'.$a['id'].'/delete') ?>" style="display:inline"
                    onsubmit="return confirm('<?= __('general.confirm') ?>?')">
                <button class="btn btn-danger btn-xs">🗑</button>
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

<!-- Builder Modal -->
<div id="builder-overlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.65);z-index:300;align-items:flex-start;justify-content:center;overflow-y:auto;padding:20px">
  <div style="backdrop-filter:blur(7px);background:var(--sf);border:1px solid var(--bd2);border-radius:var(--r);padding:28px;width:100%;max-width:640px;margin:auto">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px">
      <div style="font-family:var(--fd);font-weight:700" id="builder-title"><?= __('automations.new') ?></div>
      <button onclick="closeBuilder()" style="background:none;border:none;color:var(--mt);cursor:pointer;font-size:1.2rem">✕</button>
    </div>

    <form method="POST" id="builder-form">
      <div class="row2">
        <div class="field" style="grid-column:1/-1"><label><?= __('general.name') ?> *</label>
          <input type="text" name="name" id="b-name" required></div>
        <div class="field" style="grid-column:1/-1"><label><?= __('general.description') ?></label>
          <input type="text" name="description" id="b-desc"></div>
      </div>

      <div class="field"><label><?= __('automations.entity') ?></label>
        <select name="trigger_entity_id" id="b-entity">
          <option value=""><?= __('general.all') ?></option>
          <?php foreach ($entities as $ent): ?>
          <option value="<?= $ent['id'] ?>"><?= h($ent['icon'].' '.$ent['name']) ?></option>
          <?php endforeach; ?>
        </select></div>

      <div class="field"><label><?= __('automations.trigger') ?> *</label>
        <select name="trigger_event" id="b-event" required>
          <option value="on_create"><?= __('automations.events.on_create') ?></option>
          <option value="on_update"><?= __('automations.events.on_update') ?></option>
          <option value="on_delete"><?= __('automations.events.on_delete') ?></option>
          <option value="on_field_change"><?= __('automations.events.on_field_change') ?></option>
        </select></div>

      <div class="field"><label><?= __('automations.action') ?> *</label>
        <select name="action_type" id="b-action" required onchange="renderActionCfg(this.value)">
          <option value="webhook"><?= __('automations.actions.webhook') ?></option>
          <option value="set_field"><?= __('automations.actions.set_field') ?></option>
          <option value="create_record"><?= __('automations.actions.create_record') ?></option>
          <option value="send_email"><?= __('automations.actions.send_email') ?></option>
        </select></div>

      <div id="action-cfg"></div>

      <div class="form-actions">
        <button type="button" onclick="closeBuilder()" class="btn btn-ghost"><?= __('general.cancel') ?></button>
        <button type="submit" class="btn btn-primary" id="builder-btn"><?= __('general.save') ?></button>
      </div>
    </form>
  </div>
</div>

<?php
$fieldOptions = '';
foreach ($entities as $e) {
    $eFields = DB::q('SELECT id,name FROM entity_fields WHERE entity_id=?', [$e['id']]);
    foreach ($eFields as $f) {
        $fieldOptions .= '<option value="'.h($f['id']).'">'.h($e['name'].' → '.$f['name']).'</option>';
    }
}
$entityOptions = '';
foreach ($entities as $e) {
    $entityOptions .= '<option value="'.h($e['id']).'">'.h($e['icon'].' '.$e['name']).'</option>';
}
?>
<script>
const BO = document.getElementById('builder-overlay');
const ACTION_TPLS = {
  webhook:`<div class="field"><label>URL *</label><input type="url" name="action_config[url]" placeholder="https://..." required></div>
    <div class="field"><label>HTTP</label><select name="action_config[method]"><option>POST</option><option>PUT</option><option>PATCH</option></select></div>`,
  set_field:`<div class="row2">
    <div class="field"><label><?= __('fields.name') ?> *</label><select name="action_config[field_id]">
      <?= $fieldOptions ?>
    </select></div>
    <div class="field"><label><?= __('general.name') ?> *</label><input type="text" name="action_config[value]" placeholder="valor fixo ou {{field_slug}}"></div></div>`,
  create_record:`<div class="field"><label><?= __('automations.entity') ?> *</label><select name="action_config[entity_id]">
    <?= $entityOptions ?>
    </select></div>
    <div class="field"><label><?= __('entities.fields') ?> (JSON)</label>
    <textarea name="action_config[fields_json]" rows="3" placeholder='{"field_1":"valor","field_2":"{{field_status}}"}'></textarea></div>`,
  send_email:`<div class="field"><label><?= __('users.email') ?> *</label><input type="text" name="action_config[to]" placeholder="{{field_email}} ou fixo@email.com"></div>
    <div class="field"><label><?= __('general.name') ?></label><input type="text" name="action_config[subject]" placeholder="Novo registro: {{field_nome}}"></div>
    <div class="field"><label>Mensagem</label><textarea name="action_config[body]" rows="4"></textarea></div>`,
};

function openBuilder() {
  document.getElementById('builder-title').textContent = '<?= __('automations.new') ?>';
  document.getElementById('builder-form').action = '<?= url('/automations/create') ?>';
  ['b-name','b-desc'].forEach(id=>document.getElementById(id).value='');
  renderActionCfg('webhook');
  BO.style.display = 'flex';
}
function openEdit(a) {
  document.getElementById('builder-title').textContent = '<?= __('general.edit') ?>';
  document.getElementById('builder-form').action = '<?= url('/automations/') ?>'+a.id+'/update';
  document.getElementById('b-name').value = a.name;
  document.getElementById('b-desc').value = a.description||'';
  document.getElementById('b-entity').value = a.trigger_entity_id||'';
  document.getElementById('b-event').value = a.trigger_event;
  document.getElementById('b-action').value = a.action_type;
  renderActionCfg(a.action_type);
  BO.style.display = 'flex';
}
function closeBuilder() { BO.style.display = 'none'; }
function renderActionCfg(type) { document.getElementById('action-cfg').innerHTML = ACTION_TPLS[type]||''; }
BO.addEventListener('click', e => { if(e.target===BO) closeBuilder(); });
</script>

<?php partial('layout/footer'); ?>
