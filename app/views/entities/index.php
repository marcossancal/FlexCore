<?php partial('layout/header', ['page_title' => __('entities.page_title_list'), 'active_page' => 'entities', 'breadcrumbs' => [['label' => __('entities.page_title_list')]]]) ?>

<style>
  /* ── Bulk toolbar ── */
  .bulk-bar {
    display: none; align-items: center; gap: 10px;
    background: var(--sf2); border: 1px solid var(--bd2);
    border-radius: var(--r); padding: 10px 16px;
    margin-bottom: 14px; font-size: .85rem; color: var(--tx);
  }
  .bulk-bar.active { display: flex; }
  .bulk-bar .bulk-count { font-weight: 700; color: var(--ac); }
  .bulk-bar .spacer { flex: 1; }

  /* ── Checkbox styling ── */
  .cb-wrap { display: flex; align-items: center; justify-content: center; }
  input[type=checkbox] {
    width: 15px; height: 15px; accent-color: var(--ac);
    cursor: pointer; border-radius: 3px;
  }
  th.cb-col, td.cb-col { width: 36px; padding-left: 14px; padding-right: 0; }

  /* row selected highlight */
  tr.row-selected td { background: rgba(0,212,255,.045) !important; }
</style>

<div class="sec-head">
  <div>
    <div class="sec-title">⚙️ <?= __('entities.title') ?></div>
    <div class="sec-sub"><?= __('entities.next_step_desc') ?></div>
  </div>
  <a href="<?= url('/entities/new') ?>" class="btn btn-primary"> <?= __('entities.new') ?></a>
</div>

<?php if (empty($entities)): ?>
<div class="card" style="text-align:center;padding:48px">
  <div style="font-size:2.5rem;margin-bottom:12px">🧩</div>
  <div style="font-family:var(--fd);font-size:1.1rem;font-weight:700;margin-bottom:8px"><?= __('entities.no_entities') ?></div>
  <div style="color:var(--mt);margin-bottom:24px"><?= __('entities.next_step_desc') ?></div>
  <a href="<?= url('/entities/new') ?>" class="btn btn-primary"> <?= __('entities.create_first') ?></a>
</div>
<?php else: ?>

<!-- Bulk action toolbar (aparece quando há seleção) -->
<div class="bulk-bar" id="bulkBar">
  <span><span class="bulk-count" id="bulkCount">0</span> entidade(s) selecionada(s)</span>
  <span class="spacer"></span>
  <form method="POST" action="<?= url('/entities/bulk-delete') ?>" id="bulkDeleteForm">
    <input type="hidden" name="ids" id="bulkIds">
    <button type="button" class="btn btn-danger btn-sm" onclick="confirmBulkDelete()">
      🗑 Excluir selecionadas
    </button>
  </form>
  <button class="btn btn-ghost btn-sm" onclick="clearSelection()">✕ Cancelar</button>
</div>

<div class="card">
  <div class="tbl-wrap">
    <table id="entitiesTable">
      <thead><tr>
        <th class="cb-col">
          <div class="cb-wrap">
            <input type="checkbox" id="checkAll" title="Selecionar todas" onchange="toggleAll(this)">
          </div>
        </th>
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
      <tr data-id="<?= $ent['id'] ?>">
        <td class="cb-col">
          <div class="cb-wrap">
            <input type="checkbox" class="row-cb" value="<?= $ent['id'] ?>" onchange="onRowCheck()">
          </div>
        </td>
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

<script>
function getChecked() {
  return [...document.querySelectorAll('.row-cb:checked')];
}

function onRowCheck() {
  const checked = getChecked();
  const total   = document.querySelectorAll('.row-cb').length;
  const bar     = document.getElementById('bulkBar');
  const countEl = document.getElementById('bulkCount');
  const checkAll = document.getElementById('checkAll');

  // highlight rows
  document.querySelectorAll('.row-cb').forEach(cb => {
    cb.closest('tr').classList.toggle('row-selected', cb.checked);
  });

  countEl.textContent = checked.length;
  bar.classList.toggle('active', checked.length > 0);
  checkAll.indeterminate = checked.length > 0 && checked.length < total;
  checkAll.checked = checked.length === total;
}

function toggleAll(masterCb) {
  document.querySelectorAll('.row-cb').forEach(cb => {
    cb.checked = masterCb.checked;
  });
  onRowCheck();
}

function clearSelection() {
  document.querySelectorAll('.row-cb').forEach(cb => cb.checked = false);
  document.getElementById('checkAll').checked = false;
  document.getElementById('checkAll').indeterminate = false;
  onRowCheck();
}

function confirmBulkDelete() {
  const ids = getChecked().map(cb => cb.value);
  if (!ids.length) return;

  const names = ids.map(id => {
    const row = document.querySelector(`tr[data-id="${id}"]`);
    return row ? row.querySelector('strong').textContent.trim() : '#' + id;
  });

  const msg = `Excluir ${ids.length} entidade(s)?\n\n${names.join('\n')}\n\nEssa ação também excluirá todos os registros e campos vinculados.`;
  if (!confirm(msg)) return;

  document.getElementById('bulkIds').value = ids.join(',');
  document.getElementById('bulkDeleteForm').submit();
}
</script>

<?php endif; ?>

<?php partial('layout/footer') ?>
