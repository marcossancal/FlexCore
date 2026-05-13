<?php
$isEdit = isset($entity);
$title  = $isEdit ? __('entities.page_title_edit') : __('entities.page_title_new');
partial('layout/header', [
  'page_title'  => $title,
  'active_page' => 'entities',
  'breadcrumbs' => [
    ['label' => __('entities.breadcrumb_list'), 'url' => '/entities'],
    ['label' => $title],
  ],
])
?>

<div class="sec-head">
  <div class="sec-title"><?= $isEdit ? '✏️ '.__('entities.edit') : '+ '.__('entities.new') ?></div>
  <a href="<?= url('/entities') ?>" class="btn btn-ghost btn-sm">← <?= __('general.back') ?></a>
</div>

<?php if ($isEdit): ?>
<div style="display:flex;gap:0;border-bottom:1px solid var(--bd);margin-bottom:24px">
  <?php
  $currentTab = $_GET['tab'] ?? 'geral';
  $tabs = [
    'geral'      => ['ico' => '⚙️', 'label' => __('entities.identity')],
    'permissoes' => ['ico' => '🔐', 'label' => 'Permissões'],
    'api'        => ['ico' => '📡', 'label' => __('fields.api_responses')],
  ];
  foreach ($tabs as $tKey => $tVal):
  ?>
  <a href="<?= url('/entities/'.$entity['id'].'/edit?tab='.$tKey) ?>"
     style="display:flex;align-items:center;gap:6px;padding:10px 18px;font-size:.875rem;font-weight:600;text-decoration:none;border-bottom:2px solid <?= $currentTab===$tKey?'var(--ac)':'transparent' ?>;color:<?= $currentTab===$tKey?'var(--ac)':'var(--mt)' ?>;margin-bottom:-1px;transition:all .15s">
    <?= $tVal['ico'] ?> <?= $tVal['label'] ?>
  </a>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if (!$isEdit || ($_GET['tab']??'geral')==='geral'): ?>
<form method="POST" action="<?= url( $isEdit ? '/entities/'.$entity['id'].'/update' : '/entities/create'); ?>">
  <div class="row2" style="gap:18px;align-items:start">
    <div>
      <div class="card">
        <div class="card-title"><?= __('entities.identity') ?></div>

        <div class="row2">
          <div class="field">
            <label><?= __('entities.icon') ?></label>
            <input type="text" name="icon" id="inp-icon" value="<?= h($entity['icon'] ?? '📋') ?>" maxlength="4"
                   style="font-size:1.4rem;text-align:center;cursor:pointer" readonly onclick="abrirEmojiPicker()">
          </div>
          <div class="field">
            <label><?= __('entities.color') ?></label>
            <input type="color" name="color" id="inp-color" value="<?= h($entity['color'] ?? '#00d4ff') ?>"
                   style="height:42px;padding:4px 8px">
          </div>
        </div>

        <div class="field">
          <label><?= __('entities.name') ?> *</label>
          <input type="text" name="name" id="inp-name" value="<?= h($entity['name'] ?? '') ?>"
                 required autofocus placeholder="Ex: Clientes, Processos, Leads…"
                 oninput="atualizarSlug(this.value)">
        </div>

        <div class="field">
          <label><?= __('entities.slug') ?></label>
          <input type="text" name="slug" id="inp-slug" value="<?= h($entity['slug'] ?? '') ?>"
                 required pattern="[a-z0-9\-]+" placeholder="clientes"
                 style="font-family:monospace">
        </div>

        <div class="field">
          <label><?= __('entities.description') ?></label>
          <textarea name="description"><?= h($entity['description'] ?? '') ?></textarea>
        </div>
      </div>
    </div>

    <div>
      <div class="card">
        <div class="card-title"><?= __('entities.config') ?></div>

        <div class="field">
          <label><?= __('entities.position') ?></label>
          <input type="number" name="position" value="<?= $entity['position'] ?? 0 ?>" min="0">
        </div>

        <div class="field">
          <label><?= __('entities.status') ?></label>
          <select name="active">
            <option value="1" <?= ($entity['active'] ?? 1) ? 'selected' : '' ?>><?= __('entities.active') ?></option>
            <option value="0" <?= !($entity['active'] ?? 1) ? 'selected' : '' ?>><?= __('entities.inactive') ?></option>
          </select>
        </div>

        <div style="border-top:1px solid var(--bd);padding-top:16px;margin-top:4px">
          <div style="font-size:.72rem;font-weight:700;color:var(--mt);text-transform:uppercase;letter-spacing:.08em;margin-bottom:12px"><?= __('entities.preview') ?></div>
          <div id="menu-preview" style="display:flex;align-items:center;gap:10px;padding:8px 10px;background:color-mix(in srgb,var(--ac) 12%,transparent);border-radius:var(--r2);max-width:200px">
            <span id="prev-icon" style="font-size:1rem"><?= h($entity['icon'] ?? '📋') ?></span>
            <span id="prev-name" style="font-size:.875rem;font-weight:600;color:var(--ac)"><?= h($entity['name'] ?? __('entities.name')) ?></span>
          </div>
        </div>

        <div class="form-actions">
          <a href="<?= url('/entities') ?>" class="btn btn-ghost"><?= __('general.cancel') ?></a>
          <button type="submit" class="btn btn-primary">💾 <?= $isEdit ? __('general.save') : __('entities.new') ?></button>
        </div>
      </div>

      <?php if ($isEdit): ?>
      <div class="card" style="border-color:rgba(0,212,255,.2)">
        <div class="card-title" style="font-size:.82rem">🔧 <?= __('entities.next_step') ?></div>
        <p style="color:var(--mt);font-size:.85rem;margin-bottom:14px"><?= __('entities.next_step_desc') ?></p>
        <a href="<?= url('/entities/' . $entity['id'] . '/fields') ?>" class="btn btn-primary btn-sm" style="width:100%;justify-content:center"><?= __('entities.manage_fields') ?></a>
      </div>
      <?php endif; ?>
    </div>
  </div>
</form>

<!-- Emoji picker -->
<div id="emoji-picker" style="display:none;position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);background:var(--sf);border:1px solid var(--bd2);border-radius:var(--r);padding:20px;z-index:999;width:340px;box-shadow:0 20px 60px rgba(0,0,0,.6)">
  <div style="font-family:var(--fd);font-weight:700;margin-bottom:12px"><?= __('entities.icon') ?></div>
  <div style="display:flex;flex-wrap:wrap;gap:6px;max-height:240px;overflow-y:auto">
    <?php
    $emojis = ['📋','👥','💼','📄','🎫','📊','💰','🏢','📞','✉️','📅','🎯','🔑','📦','🚀','🛠️','📌','🔔','💡','📈','🤝','📝','🗂️','⭐','🏆','💎','🔒','🌐','📱','🖥️','📸','🎨','🔧','⚙️','🧩','📐','🔍','💬','📣','🎁'];
    foreach ($emojis as $e): ?>
    <button type="button" onclick="selecionarEmoji('<?= $e ?>')"
            style="background:var(--sf2);border:1px solid var(--bd);border-radius:6px;width:38px;height:38px;cursor:pointer;font-size:1.2rem;transition:all .1s"
            onmouseenter="this.style.background='var(--sf3)'" onmouseleave="this.style.background='var(--sf2)'">
      <?= $e ?>
    </button>
    <?php endforeach; ?>
  </div>
  <div style="margin-top:14px">
    <input type="text" id="emoji-custom" placeholder="<?= __('general.add') ?>…" style="background:var(--sf2);border:1px solid var(--bd2);border-radius:var(--r2);color:var(--tx);padding:8px 12px;font-size:1.1rem;width:100%;outline:none">
  </div>
  <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:12px">
    <button type="button" onclick="document.getElementById('emoji-picker').style.display='none'" class="btn btn-ghost btn-sm"><?= __('general.cancel') ?></button>
    <button type="button" onclick="usarEmojiCustom()" class="btn btn-primary btn-sm"><?= __('general.confirm') ?></button>
  </div>
</div>

<script>
function slugify(s) {
  return s.toLowerCase()
    .replace(/[àáâãä]/g,'a').replace(/[èéêë]/g,'e').replace(/[ìíî]/g,'i')
    .replace(/[òóôõö]/g,'o').replace(/[ùúûü]/g,'u').replace(/[ç]/g,'c')
    .replace(/[^a-z0-9]+/g,'-').replace(/^-+|-+$/g,'');
}
function atualizarSlug(v) {
  var s = slugify(v);
  document.getElementById('inp-slug').value = s;
  document.getElementById('prev-name').textContent = v || '<?= __('entities.name') ?>';
}
document.getElementById('inp-slug').addEventListener('input', function() {});
document.getElementById('inp-color').addEventListener('input', function() {
  document.getElementById('menu-preview').style.background = 'color-mix(in srgb,'+this.value+' 12%,transparent)';
  document.getElementById('prev-name').style.color = this.value;
});
function abrirEmojiPicker() { document.getElementById('emoji-picker').style.display='block'; }
function selecionarEmoji(e) {
  document.getElementById('inp-icon').value = e;
  document.getElementById('prev-icon').textContent = e;
  document.getElementById('emoji-picker').style.display = 'none';
}
function usarEmojiCustom() {
  var v = document.getElementById('emoji-custom').value.trim();
  if (v) selecionarEmoji(v);
}
</script>
<?php endif; // geral tab ?>

<?php if ($isEdit && ($_GET['tab']??'geral')==='api'): ?>
<?php partial('entities/api_responses', ['entity'=>$entity]) ?>
<?php endif; ?>

<?php if ($isEdit && ($_GET['tab']??'geral')==='permissoes'):
  // Loads saved permissions indexed by roles
  $permsRows = DB::q('SELECT * FROM entity_permissions WHERE entity_id = ?', [$entity['id']]);
  $perms = [];
  foreach ($permsRows as $p) { $perms[$p['role']] = $p; }

  // Defaults when there is no saved lines: admin and editor can do anythin, viewer only reads
  $defaults = [
    'admin'  => ['can_create' => 1, 'can_edit' => 1, 'can_delete' => 1],
    'editor' => ['can_create' => 1, 'can_edit' => 1, 'can_delete' => 0],
    'viewer' => ['can_create' => 0, 'can_edit' => 0, 'can_delete' => 0],
  ];
  $roleLabels = ['admin' => '👑 Admin', 'editor' => '✏️ Editor', 'viewer' => '👁️ Viewer'];
  $ops = ['can_create' => 'Criar', 'can_edit' => 'Editar', 'can_delete' => 'Excluir'];
?>
<form method="POST" action="<?= url('/entities/'.$entity['id'].'/permissions') ?>">
  <div class="card">
    <div class="card-title">🔐 Permissões por papel</div>
    <p style="color:var(--mt);font-size:.83rem;margin-bottom:20px">
      Define o que cada papel pode fazer nos registros desta entidade.<br>
      Quando não configurado, o comportamento padrão se aplica (admin e editor têm acesso total, viewer só lê).
    </p>

    <div style="overflow-x:auto">
      <table style="width:100%;border-collapse:collapse">
        <thead>
          <tr>
            <th style="text-align:left;padding:10px 16px;font-size:.75rem;font-weight:700;color:var(--mt);text-transform:uppercase;letter-spacing:.06em;border-bottom:1px solid var(--bd)">Papel</th>
            <?php foreach ($ops as $opKey => $opLabel): ?>
            <th style="text-align:center;padding:10px 16px;font-size:.75rem;font-weight:700;color:var(--mt);text-transform:uppercase;letter-spacing:.06em;border-bottom:1px solid var(--bd)"><?= $opLabel ?></th>
            <?php endforeach; ?>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($roleLabels as $role => $roleLabel): ?>
          <?php $row = $perms[$role] ?? $defaults[$role]; ?>
          <tr style="border-bottom:1px solid var(--bd)">
            <td style="padding:14px 16px;font-weight:600;font-size:.88rem"><?= $roleLabel ?></td>
            <?php foreach ($ops as $opKey => $opLabel): ?>
            <td style="padding:14px 16px;text-align:center">
              <label style="display:inline-flex;align-items:center;justify-content:center;cursor:pointer">
                <input type="checkbox" name="<?= $opKey ?>_<?= $role ?>" value="1"
                       <?= $row[$opKey] ? 'checked' : '' ?>
                       style="width:18px;height:18px;accent-color:var(--ac);cursor:pointer">
              </label>
            </td>
            <?php endforeach; ?>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div style="background:color-mix(in srgb,var(--am) 8%,transparent);border:1px solid color-mix(in srgb,var(--am) 25%,transparent);border-radius:var(--r2);padding:12px 16px;margin-top:18px;font-size:.8rem;color:var(--mt2)">
      ⚠️ <strong>Admins</strong> sempre têm acesso total à configuração de entidades — esta tabela controla apenas a operação de <em>registros</em>.
    </div>

    <div class="form-actions">
      <a href="<?= url('/entities/'.$entity['id'].'/edit?tab=permissoes') ?>" class="btn btn-ghost">Cancelar</a>
      <button type="submit" class="btn btn-primary">💾 Salvar permissões</button>
    </div>
  </div>
</form>
<?php endif; ?>

<?php partial('layout/footer') ?>