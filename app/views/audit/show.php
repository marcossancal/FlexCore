<?php
/**
 * audit/show.php — Detalhe de uma entrada de auditoria.
 *
 * Variáveis:
 *   $entry       array   — linha do audit_log
 *   $before      array   — [field_id => value] antes
 *   $after       array   — [field_id => value] depois
 *   $fieldLabels array   — [field_id => {name, field_type}]
 *   $actionLabels array
 */

$meta       = $actionLabels[$entry['action']] ?? ['label' => $entry['action'], 'icon' => '•', 'color' => '#94a3b8'];
$wasReverted = !empty($entry['reverted_at']);
$revertableActions = ['create_record','update_record','create_entity','update_entity','delete_entity'];
$canRevert   = in_array($entry['action'], $revertableActions, true)
               && !$wasReverted
               && (!empty($entry['before_json']) || $entry['action'] === 'create_record' || $entry['action'] === 'create_entity');
$hasDiff     = !empty($before) || !empty($after);

// Flash
$flashOk  = $_SESSION['flash_success'] ?? '';
$flashErr = $_SESSION['flash_error']   ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);
?>

<?php if ($flashOk): ?>
<div class="flash flash-ok">✅ <?= h($flashOk) ?></div>
<?php endif; ?>
<?php if ($flashErr): ?>
<div class="flash flash-err">❌ <?= h($flashErr) ?></div>
<?php endif; ?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:10px">
  <div>
    <a href="<?= admin_url('/audit') ?>" style="color:var(--mt);font-size:.82rem;text-decoration:none">← Voltar ao log</a>
    <div class="sec-title" style="margin-top:6px">
      <?= $meta['icon'] ?> Entrada #<?= $entry['id'] ?>
      <span class="badge" style="background:<?= $meta['color'] ?>22;color:<?= $meta['color'] ?>;border:1px solid <?= $meta['color'] ?>44;font-size:.75rem;margin-left:8px;vertical-align:middle"><?= h($meta['label']) ?></span>
      <?php if ($wasReverted): ?><span class="badge br" style="font-size:.72rem;margin-left:6px;vertical-align:middle">Desfeita</span><?php endif; ?>
    </div>
  </div>

  <?php if ($canRevert && (Auth::user()['role'] ?? '') === 'admin'): ?>
  <form method="POST" action="<?= admin_url('/audit/' . $entry['id'] . '/revert') ?>"
        onsubmit="return confirm('Confirma desfazer esta ação?\n\nO Registro #<?= $entry['record_id'] ?> voltará ao estado anterior.')">
    <button class="btn btn-primary" style="background:#f59e0b;border-color:#f59e0b">
      ↩️ Desfazer esta ação
    </button>
  </form>
  <?php elseif ($entry['action'] === 'delete_record'): ?>
  <span class="badge br" title="Exclusões não podem ser desfeitas automaticamente">🔒 Não reversível</span>
  <?php endif; ?>
</div>

<!-- Meta ----------------------------------------------------------------->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:14px;margin-bottom:20px">
  <div class="card" style="padding:16px 18px">
    <div style="font-size:.7rem;color:var(--mt);margin-bottom:4px">DATA E HORA</div>
    <div style="font-weight:600"><?= date('d/m/Y', strtotime($entry['created_at'])) ?></div>
    <div style="font-family:var(--fm);color:var(--mt);font-size:.85rem"><?= date('H:i:s', strtotime($entry['created_at'])) ?></div>
  </div>
  <div class="card" style="padding:16px 18px">
    <div style="font-size:.7rem;color:var(--mt);margin-bottom:4px">USUÁRIO</div>
    <div style="font-weight:600"><?= $entry['user_name'] ? h($entry['user_name']) : 'Sistema' ?></div>
    <div style="font-size:.78rem;color:var(--mt)"><?= h($entry['user_email'] ?? '') ?></div>
  </div>
  <div class="card" style="padding:16px 18px">
    <div style="font-size:.7rem;color:var(--mt);margin-bottom:4px">ENTIDADE / REGISTRO</div>
    <div style="font-weight:600"><?= $entry['entity_icon'] ?? '' ?> <?= $entry['entity_name'] ? h($entry['entity_name']) : '—' ?></div>
    <div style="font-family:var(--fm);color:var(--mt);font-size:.85rem">
      <?php if ($entry['record_id'] && $entry['entity_slug']): ?>
      <a href="<?= admin_url('/e/' . $entry['entity_slug'] . '/' . $entry['record_id']) ?>" style="color:var(--accent)">Registro #<?= $entry['record_id'] ?> →</a>
      <?php elseif ($entry['record_id']): ?>
      Registro #<?= $entry['record_id'] ?>
      <?php else: ?>
      —
      <?php endif; ?>
    </div>
  </div>
  <div class="card" style="padding:16px 18px">
    <div style="font-size:.7rem;color:var(--mt);margin-bottom:4px">IP DE ORIGEM</div>
    <div style="font-family:var(--fm);font-weight:600"><?= h($entry['ip'] ?? '—') ?></div>
  </div>
  <?php if ($wasReverted): ?>
  <div class="card" style="padding:16px 18px;border-color:#f59e0b44">
    <div style="font-size:.7rem;color:#f59e0b;margin-bottom:4px">DESFEITA EM</div>
    <div style="font-weight:600"><?= date('d/m/Y H:i', strtotime($entry['reverted_at'])) ?></div>
    <div style="font-size:.78rem;color:var(--mt)"><?= h($entry['reverted_by_name'] ?? '') ?></div>
  </div>
  <?php endif; ?>
  <?php if ($entry['revert_of']): ?>
  <div class="card" style="padding:16px 18px;border-color:#6c5ce744">
    <div style="font-size:.7rem;color:#6c5ce7;margin-bottom:4px">REVERTEU</div>
    <a href="<?= admin_url('/audit/' . $entry['revert_of']) ?>" style="font-weight:600;color:#6c5ce7">Entrada #<?= $entry['revert_of'] ?> →</a>
  </div>
  <?php endif; ?>
</div>

<!-- Descrição ------------------------------------------------------------->
<div class="card" style="margin-bottom:18px;padding:18px 20px">
  <div style="font-size:.7rem;color:var(--mt);margin-bottom:8px;text-transform:uppercase;letter-spacing:.05em">Descrição completa</div>
  <pre style="margin:0;white-space:pre-wrap;font-family:var(--fm);font-size:.82rem;color:var(--tx);line-height:1.6"><?= h($entry['description'] ?? '') ?></pre>
</div>

<!-- Diff antes / depois -------------------------------------------------->
<?php if ($hasDiff): ?>
<div class="card" style="margin-bottom:18px;padding:0;overflow:hidden">
  <div style="padding:14px 20px;border-bottom:1px solid var(--bd);font-size:.7rem;color:var(--mt);text-transform:uppercase;letter-spacing:.05em;font-weight:600">
    Comparação de valores — antes × depois
  </div>

  <?php
    // Monta todos os field_ids que aparecem em before ou after
    $allFieldIds = array_unique(array_merge(array_keys($before), array_keys($after)));
    $hasAnyDiff  = false;
    foreach ($allFieldIds as $fid) {
        if (($before[$fid] ?? null) !== ($after[$fid] ?? null)) { $hasAnyDiff = true; break; }
    }
  ?>

  <?php if (empty($allFieldIds)): ?>
  <div style="padding:24px;color:var(--mt);font-size:.85rem">Sem snapshot disponível.</div>
  <?php else: ?>
  <div class="tbl-wrap">
    <table>
      <thead>
        <tr>
          <th style="width:160px">Campo</th>
          <th>Antes</th>
          <th>Depois</th>
          <th style="width:80px;text-align:center">Alterado?</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($allFieldIds as $fid):
          $oldVal   = $before[$fid] ?? null;
          $newVal   = $after[$fid]  ?? null;
          $changed  = $oldVal !== $newVal;
          $label    = $fieldLabels[$fid]['name'] ?? "Campo #{$fid}";
          $ftype    = $fieldLabels[$fid]['field_type'] ?? 'text';

          // Para campos de imagem/arquivo, exibe resumo em vez do base64 completo
          $isMedia  = in_array($ftype, ['image','file'], true);
          $display  = function($v) use ($isMedia): string {
              if ($v === null) return '<em style="color:var(--mt)">vazio</em>';
              if (is_array($v)) $v = json_encode($v, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
              $v = (string)$v;
              if ($isMedia && strlen($v) > 80) return '<em style="color:var(--mt)">[arquivo — ' . round(strlen($v)*3/4/1024,1) . ' KB]</em>';
              return '<code style="white-space:pre-wrap;word-break:break-all;font-size:.78rem">' . h(mb_substr($v,0,400)) . (mb_strlen($v)>400?'…':'') . '</code>';
          };
        ?>
        <tr style="<?= $changed ? 'background:rgba(245,158,11,.04)' : '' ?>">
          <td>
            <div style="font-weight:600;font-size:.82rem"><?= h($label) ?></div>
            <div style="font-size:.68rem;color:var(--mt)"><?= h($ftype) ?> · id <?= (int)$fid ?></div>
          </td>
          <td style="vertical-align:top;padding:10px 14px">
            <?php if ($changed && $entry['action'] !== 'create_record'): ?>
            <div style="background:#ef444411;border-left:3px solid #ef4444;padding:6px 10px;border-radius:0 var(--r2) var(--r2) 0">
              <?= $display($oldVal) ?>
            </div>
            <?php else: ?>
            <div style="color:var(--mt)"><?= $display($oldVal) ?></div>
            <?php endif; ?>
          </td>
          <td style="vertical-align:top;padding:10px 14px">
            <?php if ($changed): ?>
            <div style="background:#22c55e11;border-left:3px solid #22c55e;padding:6px 10px;border-radius:0 var(--r2) var(--r2) 0">
              <?= $display($newVal) ?>
            </div>
            <?php else: ?>
            <div style="color:var(--mt)"><?= $display($newVal) ?></div>
            <?php endif; ?>
          </td>
          <td style="text-align:center"><?= $changed ? '<span style="color:#f59e0b;font-size:1rem">✏️</span>' : '<span style="color:var(--mt)">—</span>' ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>
<?php endif; ?>

<!-- Ação de desfazer (duplicada no rodapé p/ conveniência) -------------->
<?php if ($canRevert && (Auth::user()['role'] ?? '') === 'admin'): ?>
<div style="text-align:right;margin-top:4px">
  <form method="POST" action="<?= admin_url('/audit/' . $entry['id'] . '/revert') ?>"
        onsubmit="return confirm('Confirma desfazer esta ação?\n\nO Registro #<?= $entry['record_id'] ?> voltará ao estado anterior.')">
    <button class="btn" style="background:#f59e0b;border-color:#f59e0b;color:#000">
      ↩️ Desfazer esta ação
    </button>
  </form>
</div>
<?php endif; ?>

<style>
.flash { padding:12px 16px; border-radius:var(--r2); margin-bottom:16px; font-size:.85rem; font-weight:500 }
.flash-ok  { background:#22c55e22; color:#22c55e; border:1px solid #22c55e44 }
.flash-err { background:#ef444422; color:#ef4444; border:1px solid #ef444444 }
</style>