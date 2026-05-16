<?php
use FlexCore\App\Controllers\RecordController;

partial('layout/header', [
  'page_title'    => $entity['name'],
  'active_entity' => $entity['slug'],
  'breadcrumbs'   => [['label' => $entity['icon'].' '.$entity['name']]],
]) ?>

<div class="sec-head">
  <div>
    <div class="sec-title" style="display:flex;align-items:center;gap:10px">
      <span style="display:inline-flex;align-items:center;justify-content:center;width:36px;height:36px;background:color-mix(in srgb,<?= h($entity['color']) ?> 15%,var(--sf2));border-radius:8px;font-size:1.1rem"><?= h($entity['icon']) ?></span>
      <?= h($entity['name']) ?>
    </div>
    <?php if ($entity['description']): ?>
    <div class="sec-sub"><?= h($entity['description']) ?></div>
    <?php endif; ?>
  </div>
  <div class="sec-actions">
    <?php if (count($fields) > 0): ?>
    <a href="<?= admin_url('/e/' . h($entity['slug']) . '/new') ?>" class="btn btn-primary" style="background:<?= h($entity['color']) ?>"> <?= __('records.new') ?></a>
    <?php endif; ?>
    <?php if (Auth::user()['role']==='admin'): ?>
    <a href="<?= admin_url('/entities/' . $entity['id'] . '/fields') ?>" class="btn btn-ghost btn-sm">🔧 <?= __('entities.fields') ?></a>
    <?php endif; ?>
    <?php echo \FlexCore\Core\Hooks\Hooks::applyFilter('records.list.actions', '', ['entity' => $entity]) ?>
  </div>
</div>

<?php if (empty($fields)): ?>
<div class="card" style="text-align:center;padding:48px">
  <div style="font-size:2rem;margin-bottom:12px">🔧</div>
  <div style="font-family:var(--fd);font-size:1rem;font-weight:700;margin-bottom:8px"><?= __('records.no_fields') ?></div>
  <?php if (Auth::user()['role']==='admin'): ?>
  <a href="<?= admin_url('/entities/' . $entity['id'] . '/fields') ?>" class="btn btn-primary">🔧 <?= __('records.configure_first') ?></a>
  <?php endif; ?>
</div>

<?php else: ?>

<?php
// ── URL Helpers ────────────────────────────────────────────────────
function buildFilterUrl(string $slug, array $rawFilters, string $q, string $sortField = '', string $sortDir = 'desc', ?string $addRaw = null, ?string $removeRaw = null): string
{
    $filters = $rawFilters;
    if ($removeRaw !== null) {
        $filters = array_values(array_filter($filters, fn($f) => $f !== $removeRaw));
    }
    if ($addRaw !== null && !in_array($addRaw, $filters, true)) {
        $filters[] = $addRaw;
    }
    $qs = [];
    if ($q !== '') $qs[] = 'q=' . urlencode($q);
    foreach ($filters as $f)    { $qs[] = 'filters[]=' . urlencode($f); }
    if ($sortField !== '' && $sortField !== 'created_at') { $qs[] = 'sort_field=' . urlencode($sortField); }
    if ($sortDir !== 'desc') { $qs[] = 'sort_dir=asc'; }
    return admin_url('/e/' . $slug) . (count($qs) ? '?' . implode('&', $qs) : '');
}

function sortUrl(string $slug, array $rawFilters, string $q, string $fid, string $currentField, string $currentDir): string
{
    $newDir = ($currentField === $fid && $currentDir === 'asc') ? 'desc' : 'asc';
    return buildFilterUrl($slug, $rawFilters, $q, $fid, $newDir);
}

$hasActiveFilters = $q !== '' || !empty($activeFilters);
$filterableFields = array_filter($fields, fn($f) => !in_array($f['field_type'], ['relation','file'], true));
$baseUrl          = buildFilterUrl($entity['slug'], $rawFilters, $q, $sortField, $sortDir);
$baseSep          = str_contains($baseUrl, '?') ? '&' : '?';
?>

<div style="display:flex;gap:18px;align-items:flex-start">

  <!-- ── FILTER SIDEBAR ─────────────────────────────────────────── -->
  <div style="width:252px;flex-shrink:0">
    <div class="card" style="padding:16px 18px">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px">
        <span style="font-family:var(--fd);font-size:.85rem;font-weight:700">🔍 Filtros</span>
        <?php if ($hasActiveFilters): ?>
        <a href="<?= admin_url('/e/' . h($entity['slug'])) ?>" class="btn btn-xs btn-ghost" style="color:var(--rd)">✕ Limpar</a>
        <?php endif; ?>
      </div>

      <!-- Global search -->
      <form method="GET" style="margin-bottom:14px">
        <?php foreach ($rawFilters as $rf): ?><input type="hidden" name="filters[]" value="<?= h($rf) ?>"><?php endforeach; ?>
        <?php if ($sortField && $sortField!=='created_at'): ?><input type="hidden" name="sort_field" value="<?= h($sortField) ?>"><?php endif; ?>
        <?php if ($sortDir==='asc'): ?><input type="hidden" name="sort_dir" value="asc"><?php endif; ?>
        <div style="display:flex;gap:6px">
          <input type="text" name="q" value="<?= h($q) ?>" placeholder="Busca rápida…"
                 style="flex:1;background:var(--sf2);border:1px solid var(--bd2);border-radius:var(--r2);color:var(--tx);padding:7px 10px;font-family:var(--fb);font-size:.83rem;outline:none">
          <button type="submit" class="btn btn-ghost btn-xs" style="padding:7px 10px">🔍</button>
        </div>
      </form>

      <?php if (!empty($activeFilters)): ?>
      <div style="margin-bottom:14px">
        <div style="font-size:.7rem;font-weight:700;color:var(--mt);text-transform:uppercase;letter-spacing:.07em;margin-bottom:8px">Ativos</div>
        <?php foreach ($activeFilters as $af):
          $ops     = RecordController::operatorsFor($af['field']['field_type']);
          $opLabel = $ops[$af['op']]['label'] ?? $af['op'];
          $rmUrl   = buildFilterUrl($entity['slug'], $rawFilters, $q, $sortField, $sortDir, null, $af['raw']);
        ?>
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:6px;background:color-mix(in srgb,var(--ac) 8%,transparent);border:1px solid color-mix(in srgb,var(--ac) 20%,transparent);border-radius:var(--r2);padding:7px 10px;margin-bottom:6px;font-size:.78rem">
          <div>
            <div style="font-weight:700;color:var(--ac);margin-bottom:2px"><?= h($af['field']['name']) ?></div>
            <div style="color:var(--mt2)"><?= h($opLabel) ?><?php if (!in_array($af['op'],['empty','not_empty'],true)): ?> <em style="color:var(--tx)">"<?= h($af['value']) ?>"</em><?php endif ?></div>
          </div>
          <a href="<?= h($rmUrl) ?>" style="color:var(--mt);text-decoration:none;font-size:.9rem;flex-shrink:0;margin-top:1px">✕</a>
        </div>
        <?php endforeach ?>
      </div>
      <div style="border-top:1px solid var(--bd);margin-bottom:14px"></div>
      <?php endif ?>

      <!-- Adicionar filtro -->
      <div>
        <div style="font-size:.7rem;font-weight:700;color:var(--mt);text-transform:uppercase;letter-spacing:.07em;margin-bottom:10px">Adicionar filtro</div>
        <form method="GET" id="add-filter-form">
          <?php foreach ($rawFilters as $rf): ?><input type="hidden" name="filters[]" value="<?= h($rf) ?>"><?php endforeach ?>
          <?php if ($q !== ''): ?><input type="hidden" name="q" value="<?= h($q) ?>"><?php endif ?>
          <?php if ($sortField && $sortField!=='created_at'): ?><input type="hidden" name="sort_field" value="<?= h($sortField) ?>"><?php endif ?>
          <?php if ($sortDir==='asc'): ?><input type="hidden" name="sort_dir" value="asc"><?php endif ?>

          <div style="margin-bottom:8px">
            <select id="filter-field" onchange="updateOperators(this.value)"
                    style="width:100%;background:var(--sf2);border:1px solid var(--bd2);border-radius:var(--r2);color:var(--tx);padding:7px 10px;font-family:var(--fb);font-size:.83rem;outline:none">
              <option value="">— Campo —</option>
              <?php foreach ($filterableFields as $ff): ?>
              <option value="<?= $ff['id'] ?>" data-type="<?= h($ff['field_type']) ?>"><?= h($ff['name']) ?></option>
              <?php endforeach ?>
            </select>
          </div>
          <div style="margin-bottom:8px">
            <select id="filter-op" style="width:100%;background:var(--sf2);border:1px solid var(--bd2);border-radius:var(--r2);color:var(--tx);padding:7px 10px;font-family:var(--fb);font-size:.83rem;outline:none">
              <option value="">— Operador —</option>
            </select>
          </div>
          <div id="filter-value-wrap" style="margin-bottom:10px;display:none">
            <input type="text" id="filter-value" placeholder="Valor…"
                   style="width:100%;background:var(--sf2);border:1px solid var(--bd2);border-radius:var(--r2);color:var(--tx);padding:7px 10px;font-family:var(--fb);font-size:.83rem;outline:none">
          </div>
          <button type="button" onclick="applyFilter()" class="btn btn-primary btn-sm" style="width:100%;justify-content:center"> Aplicar filtro</button>
          <input type="hidden" id="filter-built" name="filters[]" value="" disabled>
        </form>
      </div>
    </div>
  </div><!-- /sidebar -->

  <!-- ── CONTEÚDO PRINCIPAL ─────────────────────────────────────────── -->
  <div style="flex:1;min-width:0">

    <!-- Barra de status + seletor de view -->
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;flex-wrap:wrap;gap:8px">
      <span style="color:var(--mt);font-size:.78rem">
        <?= number_format($total) ?> <?= __('records.title') ?>
        <?php if ($hasActiveFilters): ?><span style="color:var(--ac)"> · filtrado</span><?php endif ?>
      </span>
      <!-- Seletor de view: form POST que salva preferência -->
      <div style="display:flex;gap:4px">
        <?php
        $views = ['table' => '☰', 'cards' => '⊞', 'kanban' => '⊟'];
        $viewLabels = ['table' => 'Tabela', 'cards' => 'Cards', 'kanban' => 'Kanban'];
        foreach ($views as $vk => $vico):
          $isActive = $currentView === $vk;
        ?>
        <form method="POST" action="<?= admin_url('/e/' . h($entity['slug']) . '/set-view') ?>" style="display:inline">
          <input type="hidden" name="view" value="<?= $vk ?>">
          <?php if ($q !== ''): ?><input type="hidden" name="q" value="<?= h($q) ?>"><?php endif ?>
          <?php foreach ($rawFilters as $rf): ?><input type="hidden" name="filters[]" value="<?= h($rf) ?>"><?php endforeach ?>
          <?php if ($sortField && $sortField !== 'created_at'): ?><input type="hidden" name="sort_field" value="<?= h($sortField) ?>"><?php endif ?>
          <?php if ($sortDir === 'asc'): ?><input type="hidden" name="sort_dir" value="asc"><?php endif ?>
          <button type="submit" title="<?= $viewLabels[$vk] ?>"
                  style="background:<?= $isActive ? 'var(--ac)' : 'var(--sf2)' ?>;color:<?= $isActive ? '#000' : 'var(--mt2)' ?>;border:1px solid <?= $isActive ? 'var(--ac)' : 'var(--bd)' ?>;border-radius:var(--r2);padding:5px 9px;cursor:pointer;font-size:.9rem;line-height:1">
            <?= $vico ?>
          </button>
        </form>
        <?php endforeach ?>
      </div>
    </div>

    <?php if (empty($records)): ?>
    <div class="card" style="text-align:center;padding:48px">
      <div style="font-size:2rem;margin-bottom:12px"><?= h($entity['icon']) ?></div>
      <div style="font-family:var(--fd);font-size:1rem;font-weight:700;margin-bottom:8px">
        <?= $hasActiveFilters ? 'Nenhum registro encontrado para estes filtros.' : __('records.no_records') ?>
      </div>
      <?php if ($hasActiveFilters): ?>
      <a href="<?= admin_url('/e/' . h($entity['slug'])) ?>" class="btn btn-ghost">Limpar filtros</a>
      <?php else: ?>
      <a href="<?= admin_url('/e/' . h($entity['slug']) . '/new') ?>" class="btn btn-primary" style="background:<?= h($entity['color']) ?>"> <?= __('records.new') ?></a>
      <?php endif ?>
    </div>

    <?php elseif ($currentView === 'table'): ?>
    <!-- ══ VIEW TABELA ══════════════════════════════════════════════════ -->
    <?php
    function sortIcon(string $fid, string $cur, string $dir): string {
      if ($fid !== $cur) return '<span style="opacity:.3;font-size:.7rem;margin-left:4px">↕</span>';
      return $dir === 'asc'
        ? '<span style="color:var(--ac);font-size:.7rem;margin-left:4px">↑</span>'
        : '<span style="color:var(--ac);font-size:.7rem;margin-left:4px">↓</span>';
    }
    ?>
    <div class="card" style="padding:0;overflow:hidden">
      <div class="tbl-wrap">
        <table>
          <thead>
            <tr>
              <th style="width:50px">
                <a href="<?= h(sortUrl($entity['slug'], $rawFilters, $q, 'created_at', $sortField, $sortDir)) ?>"
                   style="text-decoration:none;color:inherit;display:flex;align-items:center;gap:4px">
                  # <?= sortIcon('created_at', $sortField, $sortDir) ?>
                </a>
              </th>
              <?php foreach ($list_fields as $f): ?>
              <th>
                <?php if (!in_array($f['field_type'], ['relation','file','checkbox','multiselect'], true)): ?>
                <a href="<?= h(sortUrl($entity['slug'], $rawFilters, $q, (string)$f['id'], $sortField, $sortDir)) ?>"
                   style="text-decoration:none;color:inherit;display:flex;align-items:center;gap:4px;white-space:nowrap">
                  <?= h($f['name']) ?> <?= sortIcon((string)$f['id'], $sortField, $sortDir) ?>
                </a>
                <?php else: ?>
                <?= h($f['name']) ?>
                <?php endif ?>
              </th>
              <?php endforeach ?>
              <th><?= __('general.created_at') ?></th>
              <?php
              // -- Hook: records.list.columns (header) --
              // Plugins adicionam <th> extras na tabela de listagem.
              echo \FlexCore\Core\Hooks\Hooks::applyFilter('records.list.columns.header', '', [
                  'entity' => $entity,
                  'fields' => $fields,
              ]);
              ?>
              <th style="width:80px"></th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($records as $rec): ?>
          <tr>
            <td style="color:var(--mt);font-family:monospace;font-size:.78rem"><?= $rec['id'] ?></td>
            <?php foreach ($list_fields as $f): ?>
            <td><?= renderFieldValue($f, $rec['values'][$f['id']] ?? null) ?></td>
            <?php endforeach ?>
            <td style="font-size:.75rem;color:var(--mt)"><?= dateBr($rec['created_at']) ?></td>
            <?php
            // -- Hook: records.list.columns (row cell) --
            // Plugins adicionam <td> extras em cada linha da tabela.
            echo \FlexCore\Core\Hooks\Hooks::applyFilter('records.list.columns.cell', '', [
                'entity' => $entity,
                'record' => $rec,
                'fields' => $fields,
            ]);
            ?>
            <td>
              <div class="td-actions">
                <a href="<?= admin_url('/e/' . h($entity['slug']) . '/' . $rec['id']) ?>" class="btn btn-ghost btn-xs"><?= __('records.view') ?></a>
              </div>
            </td>
          </tr>
          <?php endforeach ?>
          </tbody>
        </table>
      </div>
      <?php if ($pages > 1): ?>
      <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 20px;border-top:1px solid var(--bd);flex-wrap:wrap;gap:8px">
        <span style="font-size:.78rem;color:var(--mt)"><?= __('general.page') ?> <?= $page ?> <?= __('general.of') ?> <?= $pages ?></span>
        <div style="display:flex;gap:6px">
          <?php if ($page > 1): ?><a href="<?= h($baseUrl . $baseSep . 'page=' . ($page-1)) ?>" class="btn btn-ghost btn-xs">←</a><?php endif ?>
          <?php if ($page < $pages): ?><a href="<?= h($baseUrl . $baseSep . 'page=' . ($page+1)) ?>" class="btn btn-ghost btn-xs">→</a><?php endif ?>
        </div>
      </div>
      <?php endif ?>
    </div>

    <?php elseif ($currentView === 'cards'): ?>
    <!-- ══ VIEW CARDS ═══════════════════════════════════════════════════ -->
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:14px">
      <?php foreach ($records as $rec):
        // First visible field as title, second as subtitle
        $lf = array_values($list_fields);
        $titleField = $lf[0] ?? null;
        $subField   = $lf[1] ?? null;
        $titleVal   = $titleField ? ($rec['values'][$titleField['id']] ?? null) : null;
      ?>
      <a href="<?= admin_url('/e/' . h($entity['slug']) . '/' . $rec['id']) ?>"
         style="text-decoration:none;display:flex;flex-direction:column;background:var(--sf);border:1px solid var(--bd);border-radius:var(--r);padding:18px 20px;transition:border-color .15s,background .15s"
         onmouseenter="this.style.borderColor='<?= h($entity['color']) ?>'"
         onmouseleave="this.style.borderColor='var(--bd)'">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px">
          <span style="font-size:.72rem;color:var(--mt);font-family:monospace">#<?= $rec['id'] ?></span>
          <span style="width:8px;height:8px;border-radius:50%;background:<?= h($entity['color']) ?>;display:inline-block"></span>
        </div>
        <?php if ($titleVal): ?>
        <div style="font-family:var(--fd);font-size:.92rem;font-weight:700;color:var(--tx);margin-bottom:6px;line-height:1.3"><?= h($titleVal) ?></div>
        <?php endif ?>
        <?php if ($subField && isset($rec['values'][$subField['id']])): ?>
        <div style="font-size:.8rem;color:var(--mt2);margin-bottom:10px"><?= h($rec['values'][$subField['id']]) ?></div>
        <?php endif ?>
        <?php foreach (array_slice(array_values($list_fields), 2, 3) as $xf): ?>
        <?php $xv = $rec['values'][$xf['id']] ?? null; if ($xv === null || $xv === '') continue; ?>
        <div style="display:flex;justify-content:space-between;font-size:.76rem;padding:4px 0;border-top:1px solid var(--bd)">
          <span style="color:var(--mt)"><?= h($xf['name']) ?></span>
          <span style="color:var(--tx)"><?= strip_tags(renderFieldValue($xf, $xv)) ?></span>
        </div>
        <?php endforeach ?>
        <div style="margin-top:auto;padding-top:10px;font-size:.72rem;color:var(--mt)"><?= dateBr($rec['created_at']) ?></div>
      </a>
      <?php endforeach ?>
    </div>
    <?php if ($pages > 1): ?>
    <div style="display:flex;align-items:center;justify-content:space-between;margin-top:14px;flex-wrap:wrap;gap:8px">
      <span style="font-size:.78rem;color:var(--mt)"><?= __('general.page') ?> <?= $page ?> <?= __('general.of') ?> <?= $pages ?></span>
      <div style="display:flex;gap:6px">
        <?php if ($page > 1): ?><a href="<?= h($baseUrl . $baseSep . 'page=' . ($page-1)) ?>" class="btn btn-ghost btn-xs">←</a><?php endif ?>
        <?php if ($page < $pages): ?><a href="<?= h($baseUrl . $baseSep . 'page=' . ($page+1)) ?>" class="btn btn-ghost btn-xs">→</a><?php endif ?>
      </div>
    </div>
    <?php endif ?>

    <?php elseif ($currentView === 'kanban'): ?>
    <!-- ══ VIEW KANBAN ══════════════════════════════════════════════════ -->
    <?php if (!$kanbanField): ?>
    <div class="card" style="text-align:center;padding:32px">
      <div style="font-size:1.5rem;margin-bottom:8px">⊟</div>
      <div style="font-weight:700;margin-bottom:6px">Nenhum campo de seleção encontrado</div>
      <div style="color:var(--mt);font-size:.85rem">O Kanban precisa de pelo menos um campo do tipo <strong>Seleção única</strong> marcado para exibição na lista.</div>
    </div>
    <?php else:
      // Group records by kanban field value
      $options  = json_decode($kanbanField['options_json'] ?? '[]', true) ?: [];
      $options[] = ''; // coluna "No value"
      $grouped  = [];
      foreach ($options as $opt) { $grouped[$opt] = []; }
      foreach ($records as $rec) {
        $val = $rec['values'][$kanbanField['id']] ?? '';
        if (!isset($grouped[$val])) $grouped[$val] = [];
        $grouped[$val][] = $rec;
      }
      // Title field (first visible field ≠ kanban field)
      $titleField = null;
      foreach ($list_fields as $f) {
        if ($f['id'] != $kanbanField['id']) { $titleField = $f; break; }
      }
    ?>
    <div style="display:flex;gap:12px;overflow-x:auto;padding-bottom:8px;align-items:flex-start">
      <?php foreach ($grouped as $colLabel => $colRecs):
        $label = $colLabel !== '' ? $colLabel : '— Sem valor —';
      ?>
      <div style="flex:0 0 230px;background:var(--sf2);border-radius:var(--r);padding:12px">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px">
          <span style="font-size:.78rem;font-weight:700;color:var(--tx)"><?= h($label) ?></span>
          <span style="font-size:.7rem;background:var(--sf3);color:var(--mt);border-radius:20px;padding:1px 7px"><?= count($colRecs) ?></span>
        </div>
        <?php foreach ($colRecs as $rec):
          $tv = $titleField ? ($rec['values'][$titleField['id']] ?? null) : null;
        ?>
        <a href="<?= admin_url('/e/' . h($entity['slug']) . '/' . $rec['id']) ?>"
           style="display:block;text-decoration:none;background:var(--sf);border:1px solid var(--bd);border-radius:var(--r2);padding:12px 14px;margin-bottom:8px;transition:border-color .15s"
           onmouseenter="this.style.borderColor='<?= h($entity['color']) ?>'"
           onmouseleave="this.style.borderColor='var(--bd)'">
          <?php if ($tv): ?>
          <div style="font-size:.85rem;font-weight:600;color:var(--tx);margin-bottom:4px;line-height:1.3"><?= h($tv) ?></div>
          <?php endif ?>
          <div style="font-size:.7rem;color:var(--mt)">#<?= $rec['id'] ?> · <?= dateBr($rec['created_at']) ?></div>
        </a>
        <?php endforeach ?>
      </div>
      <?php endforeach ?>
    </div>
    <?php if ($pages > 1): ?>
    <div style="font-size:.78rem;color:var(--mt);margin-top:10px">Página <?= $page ?>/<?= $pages ?> · <a href="<?= h($baseUrl . $baseSep . 'page=' . ($page+1)) ?>" style="color:var(--ac)">próxima →</a></div>
    <?php endif ?>
    <?php endif ?><!-- /kanban field check -->
    <?php endif ?><!-- /view switch -->

  </div><!-- /main -->
</div><!-- /flex -->

<?php endif ?><!-- /fields check -->

<script>
var operatorsMap = <?php
  $map = [];
  foreach ($filterableFields as $ff) { $map[$ff['id']] = RecordController::operatorsFor($ff['field_type']); }
  echo json_encode($map);
?>;
var noValueOps = ['empty','not_empty'];

function updateOperators(fieldId) {
  var sel = document.getElementById('filter-op');
  sel.innerHTML = '<option value="">— Operador —</option>';
  if (!fieldId || !operatorsMap[fieldId]) return;
  for (var k in operatorsMap[fieldId]) {
    var o = document.createElement('option'); o.value = k;
    o.textContent = operatorsMap[fieldId][k].label; sel.appendChild(o);
  }
  sel.onchange = function() {
    document.getElementById('filter-value-wrap').style.display =
      noValueOps.indexOf(sel.value)===-1 ? 'block' : 'none';
  };
  document.getElementById('filter-value-wrap').style.display = 'none';
}

function applyFilter() {
  var fieldId = document.getElementById('filter-field').value;
  var op      = document.getElementById('filter-op').value;
  var val     = document.getElementById('filter-value').value.trim();
  if (!fieldId || !op) { alert('Selecione campo e operador.'); return; }
  if (noValueOps.indexOf(op)===-1 && val==='') { alert('Informe um valor para o filtro.'); return; }
  var hidden = document.getElementById('filter-built');
  hidden.value = fieldId+':'+op+(noValueOps.indexOf(op)===-1 ? ':'+val : ':');
  hidden.disabled = false;
  document.getElementById('add-filter-form').submit();
}
</script>

<?php partial('layout/footer') ?>