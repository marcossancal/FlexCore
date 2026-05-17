<?php
/**
 * audit/index.php — Log de auditoria completo com filtros e rollback.
 *
 * Variáveis injetadas pelo AuditController::index():
 *   $entries       array    — linhas do audit_log (paginadas)
 *   $total         int      — total de entradas
 *   $page          int      — página atual
 *   $pages         int      — total de páginas
 *   $allEntities   array    — lista de entidades (para filtro)
 *   $allUsers      array    — lista de usuários (para filtro)
 *   $actionLabels  array    — mapa action => [label, icon, color]
 *   $filterAction  string
 *   $filterEntity  int
 *   $filterUser    int
 *   $filterDateFrom string
 *   $filterDateTo  string
 *   $filterRecord  int
 */

// Flash messages
$flashOk  = $_SESSION['flash_success'] ?? '';
$flashErr = $_SESSION['flash_error']   ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

// Monta query string sem 'page' para links de paginação
$qsBase = http_build_query(array_filter([
    'action'    => $filterAction,
    'entity_id' => $filterEntity ?: '',
    'user_id'   => $filterUser   ?: '',
    'date_from' => $filterDateFrom,
    'date_to'   => $filterDateTo,
    'record_id' => $filterRecord  ?: '',
]));
$qsSep = $qsBase ? '&' : '';
?>

<!-- Flash ---------------------------------------------------------------->
<?php if ($flashOk): ?>
<div class="flash flash-ok">✅ <?= h($flashOk) ?></div>
<?php endif; ?>
<?php if ($flashErr): ?>
<div class="flash flash-err">❌ <?= h($flashErr) ?></div>
<?php endif; ?>

<!-- Cabeçalho ------------------------------------------------------------->
<div class="sec-head">
  <div>
    <div class="sec-title">🔍 Auditoria</div>
    <div class="sec-sub"><?= number_format($total) ?> entradas registradas</div>
  </div>
</div>

<!-- Stats ----------------------------------------------------------------->
<?php
$countByAction = [];
foreach ($entries as $e) {
    $countByAction[$e['action']] = ($countByAction[$e['action']] ?? 0) + 1;
}
$creates  = array_sum(array_map(fn($k,$v) => str_contains($k,'create') ? $v : 0, array_keys($countByAction), $countByAction));
$updates  = array_sum(array_map(fn($k,$v) => str_contains($k,'update') ? $v : 0, array_keys($countByAction), $countByAction));
$deletes  = array_sum(array_map(fn($k,$v) => str_contains($k,'delete') ? $v : 0, array_keys($countByAction), $countByAction));
$reverts  = $countByAction['revert'] ?? 0;
?>
<div class="stats" style="margin-bottom:20px">
  <div class="stat"><div class="stat-ico">📋</div><div class="stat-val"><?= number_format($total) ?></div><div class="stat-lbl">Total (geral)</div></div>
  <div class="stat"><div class="stat-ico">✅</div><div class="stat-val"><?= $creates ?></div><div class="stat-lbl">Criações (pág.)</div></div>
  <div class="stat"><div class="stat-ico">✏️</div><div class="stat-val"><?= $updates ?></div><div class="stat-lbl">Edições (pág.)</div></div>
  <div class="stat"><div class="stat-ico">🗑️</div><div class="stat-val"><?= $deletes ?></div><div class="stat-lbl">Exclusões (pág.)</div></div>
  <div class="stat"><div class="stat-ico">↩️</div><div class="stat-val"><?= $reverts ?></div><div class="stat-lbl">Rollbacks (pág.)</div></div>
</div>

<!-- Filtros --------------------------------------------------------------->
<div class="card" style="margin-bottom:18px;padding:18px 20px">
  <form method="GET" action="<?= admin_url('/audit') ?>" style="display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end">
    <div style="display:flex;flex-direction:column;gap:4px;min-width:140px">
      <label style="font-size:.72rem;color:var(--mt)">Ação</label>
      <select name="action" class="form-ctrl" style="height:36px;font-size:.82rem">
        <option value="">Todas as ações</option>
        <?php foreach ($actionLabels as $act => $meta): ?>
        <option value="<?= h($act) ?>" <?= $filterAction === $act ? 'selected' : '' ?>><?= $meta['icon'] ?> <?= h($meta['label']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div style="display:flex;flex-direction:column;gap:4px;min-width:160px">
      <label style="font-size:.72rem;color:var(--mt)">Entidade</label>
      <select name="entity_id" class="form-ctrl" style="height:36px;font-size:.82rem">
        <option value="">Todas</option>
        <?php foreach ($allEntities as $ent): ?>
        <option value="<?= $ent['id'] ?>" <?= $filterEntity === (int)$ent['id'] ? 'selected' : '' ?>><?= h($ent['icon'].' '.$ent['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div style="display:flex;flex-direction:column;gap:4px;min-width:140px">
      <label style="font-size:.72rem;color:var(--mt)">Usuário</label>
      <select name="user_id" class="form-ctrl" style="height:36px;font-size:.82rem">
        <option value="">Todos</option>
        <?php foreach ($allUsers as $usr): ?>
        <option value="<?= $usr['id'] ?>" <?= $filterUser === (int)$usr['id'] ? 'selected' : '' ?>><?= h($usr['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div style="display:flex;flex-direction:column;gap:4px">
      <label style="font-size:.72rem;color:var(--mt)">De</label>
      <input type="date" name="date_from" class="form-ctrl" style="height:36px;font-size:.82rem;width:140px" value="<?= h($filterDateFrom) ?>">
    </div>

    <div style="display:flex;flex-direction:column;gap:4px">
      <label style="font-size:.72rem;color:var(--mt)">Até</label>
      <input type="date" name="date_to" class="form-ctrl" style="height:36px;font-size:.82rem;width:140px" value="<?= h($filterDateTo) ?>">
    </div>

    <div style="display:flex;flex-direction:column;gap:4px">
      <label style="font-size:.72rem;color:var(--mt)">Registro #</label>
      <input type="number" name="record_id" class="form-ctrl" style="height:36px;font-size:.82rem;width:110px" value="<?= $filterRecord ?: '' ?>" placeholder="ID">
    </div>

    <div style="display:flex;gap:8px">
      <button type="submit" class="btn btn-primary" style="height:36px;padding:0 16px;font-size:.82rem">🔍 Filtrar</button>
      <a href="<?= admin_url('/audit') ?>" class="btn btn-ghost" style="height:36px;padding:0 14px;font-size:.82rem">✕ Limpar</a>
    </div>
  </form>
</div>

<!-- Tabela ---------------------------------------------------------------->
<div class="card" style="padding:0">
  <?php if (empty($entries)): ?>
  <div style="text-align:center;padding:60px;color:var(--mt)">
    <div style="font-size:2.5rem;margin-bottom:12px">🔍</div>
    <div style="font-weight:600;margin-bottom:6px">Nenhuma entrada encontrada</div>
    <div style="font-size:.82rem">Tente ajustar os filtros acima.</div>
  </div>
  <?php else: ?>
  <div class="tbl-wrap">
    <table>
      <thead>
        <tr>
          <th style="width:60px">#</th>
          <th style="width:130px">Data / Hora</th>
          <th style="width:110px">Ação</th>
          <th>Descrição</th>
          <th style="width:130px">Entidade</th>
          <th style="width:80px">Registro</th>
          <th style="width:130px">Usuário</th>
          <th style="width:90px">IP</th>
          <th style="width:90px"></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($entries as $e):
          $meta    = $actionLabels[$e['action']] ?? ['label' => $e['action'], 'icon' => '•', 'color' => '#94a3b8'];
          $revertableActions = ['create_record','update_record','create_entity','update_entity','delete_entity'];
          $isRevertable = in_array($e['action'], $revertableActions, true)
                          && empty($e['reverted_at'])
                          && (!empty($e['before_json']) || $e['action'] === 'create_record' || $e['action'] === 'create_entity');
          $wasReverted  = !empty($e['reverted_at']);
        ?>
        <tr style="<?= $wasReverted ? 'opacity:.55' : '' ?>">
          <td style="color:var(--mt);font-size:.78rem;font-family:var(--fm)"><?= $e['id'] ?></td>
          <td style="font-size:.78rem;color:var(--mt)">
            <?= date('d/m/Y', strtotime($e['created_at'])) ?><br>
            <span style="font-family:var(--fm)"><?= date('H:i:s', strtotime($e['created_at'])) ?></span>
          </td>
          <td>
            <span class="badge" style="background:<?= $meta['color'] ?>22;color:<?= $meta['color'] ?>;border:1px solid <?= $meta['color'] ?>44;font-size:.72rem;white-space:nowrap">
              <?= $meta['icon'] ?> <?= h($meta['label']) ?>
            </span>
            <?php if ($wasReverted): ?>
            <br><span style="font-size:.65rem;color:var(--mt)">↩ desfeito</span>
            <?php endif; ?>
          </td>
          <td style="max-width:340px">
            <div style="font-size:.82rem;color:var(--tx);white-space:pre-wrap;word-break:break-word;max-height:48px;overflow:hidden;line-height:1.4"><?= h(strtok($e['description'] ?? '', "\n")) ?></div>
            <?php if (substr_count($e['description'] ?? '', "\n") > 0): ?>
            <a href="<?= admin_url('/audit/' . $e['id']) ?>" style="font-size:.72rem;color:var(--accent)">ver diff ↓</a>
            <?php endif; ?>
          </td>
          <td style="font-size:.8rem">
            <?php if ($e['entity_name']): ?>
            <span><?= h($e['entity_icon'] ?? '📋') ?> <?= h($e['entity_name']) ?></span>
            <?php else: ?>
            <span style="color:var(--mt)">—</span>
            <?php endif; ?>
          </td>
          <td style="font-family:var(--fm);font-size:.8rem;color:var(--mt)">
            <?= $e['record_id'] ? '#' . $e['record_id'] : '—' ?>
          </td>
          <td style="font-size:.78rem">
            <?= $e['user_name'] ? h($e['user_name']) : '<span style="color:var(--mt)">Sistema</span>' ?>
          </td>
          <td style="font-size:.72rem;color:var(--mt);font-family:var(--fm)"><?= h($e['ip'] ?? '—') ?></td>
          <td>
            <div class="td-actions">
              <a href="<?= admin_url('/audit/' . $e['id']) ?>" class="btn btn-ghost btn-xs" title="Ver detalhes">🔎</a>
              <?php if ($isRevertable && (Auth::user()['role'] ?? '') === 'admin'): ?>
              <form method="POST" action="<?= admin_url('/audit/' . $e['id'] . '/revert') ?>" style="display:inline"
                    onsubmit="return confirm('Desfazer esta ação no Registro #<?= $e['record_id'] ?>?\n\nEsta operação restaurará os dados anteriores.')">
                <button class="btn btn-ghost btn-xs" title="Desfazer esta ação" style="color:#f59e0b">↩️</button>
              </form>
              <?php elseif ($e['action'] === 'delete_record' && (Auth::user()['role'] ?? '') === 'admin'): ?>
              <span title="Exclusões não podem ser desfeitas automaticamente" style="cursor:help;color:var(--mt);font-size:.8rem;padding:4px">🔒</span>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- Paginação ---------------------------------------------------------->
  <?php if ($pages > 1): ?>
  <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 20px;border-top:1px solid var(--bd)">
    <span style="font-size:.78rem;color:var(--mt)">
      Página <?= $page ?> de <?= $pages ?> &nbsp;·&nbsp; <?= number_format($total) ?> entradas
    </span>
    <div style="display:flex;gap:6px">
      <?php if ($page > 1): ?>
      <a href="?<?= $qsBase . $qsSep ?>page=<?= $page - 1 ?>" class="btn btn-ghost btn-xs">‹ Anterior</a>
      <?php endif; ?>
      <?php
        $range = range(max(1, $page - 2), min($pages, $page + 2));
        foreach ($range as $p):
      ?>
      <a href="?<?= $qsBase . $qsSep ?>page=<?= $p ?>"
         class="btn btn-xs <?= $p === $page ? 'btn-primary' : 'btn-ghost' ?>"><?= $p ?></a>
      <?php endforeach; ?>
      <?php if ($page < $pages): ?>
      <a href="?<?= $qsBase . $qsSep ?>page=<?= $page + 1 ?>" class="btn btn-ghost btn-xs">Próxima ›</a>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>

  <?php endif; ?>
</div>

<style>
.flash { padding:12px 16px; border-radius:var(--r2); margin-bottom:16px; font-size:.85rem; font-weight:500 }
.flash-ok  { background:#22c55e22; color:#22c55e; border:1px solid #22c55e44 }
.flash-err { background:#ef444422; color:#ef4444; border:1px solid #ef444444 }
</style>