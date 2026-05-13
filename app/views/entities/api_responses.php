<?php
// Load saved responses
$savedResponses = json_decode($entity['api_responses'] ?? '{}', true) ?: [];

// Avaliable operations
$operations = [
    'select_all' => [
        'label'   => 'GET — Listar todos',
        'method'  => 'GET',
        'path'    => '/api/v1/' . h($entity['slug']),
        'desc'    => 'Quando a listagem é retornada com sucesso',
        'default' => ['code' => 200, 'message' => 'Registros retornados com sucesso.', 'extra' => ''],
    ],
    'select_one' => [
        'label'   => 'GET — Buscar um',
        'method'  => 'GET',
        'path'    => '/api/v1/' . h($entity['slug']) . '/{id}',
        'desc'    => 'Quando um registro específico é encontrado',
        'default' => ['code' => 200, 'message' => 'Registro encontrado.', 'extra' => ''],
    ],
    'select_not_found' => [
        'label'   => 'GET — Não encontrado',
        'method'  => 'GET',
        'path'    => '/api/v1/' . h($entity['slug']) . '/{id}',
        'desc'    => 'Quando o registro não existe',
        'default' => ['code' => 404, 'message' => 'Registro não encontrado.', 'extra' => ''],
    ],
    'insert' => [
        'label'   => 'POST — Criar',
        'method'  => 'POST',
        'path'    => '/api/v1/' . h($entity['slug']),
        'desc'    => 'Quando um registro é criado com sucesso',
        'default' => ['code' => 201, 'message' => 'Registro criado com sucesso.', 'extra' => ''],
    ],
    'insert_validation' => [
        'label'   => 'POST — Validação falhou',
        'method'  => 'POST',
        'path'    => '/api/v1/' . h($entity['slug']),
        'desc'    => 'Quando campos obrigatórios estão ausentes ou inválidos',
        'default' => ['code' => 422, 'message' => 'Dados inválidos. Verifique os campos obrigatórios.', 'extra' => ''],
    ],
    'update' => [
        'label'   => 'PUT — Atualizar',
        'method'  => 'PUT',
        'path'    => '/api/v1/' . h($entity['slug']) . '/{id}',
        'desc'    => 'Quando um registro é atualizado com sucesso',
        'default' => ['code' => 200, 'message' => 'Registro atualizado com sucesso.', 'extra' => ''],
    ],
    'update_not_found' => [
        'label'   => 'PUT — Não encontrado',
        'method'  => 'PUT',
        'path'    => '/api/v1/' . h($entity['slug']) . '/{id}',
        'desc'    => 'Quando o registro a atualizar não existe',
        'default' => ['code' => 404, 'message' => 'Registro não encontrado para atualização.', 'extra' => ''],
    ],
    'delete' => [
        'label'   => 'DELETE — Excluir',
        'method'  => 'DELETE',
        'path'    => '/api/v1/' . h($entity['slug']) . '/{id}',
        'desc'    => 'Quando um registro é excluído com sucesso',
        'default' => ['code' => 204, 'message' => '', 'extra' => ''],
    ],
    'delete_not_found' => [
        'label'   => 'DELETE — Não encontrado',
        'method'  => 'DELETE',
        'path'    => '/api/v1/' . h($entity['slug']) . '/{id}',
        'desc'    => 'Quando o registro a excluir não existe',
        'default' => ['code' => 404, 'message' => 'Registro não encontrado para exclusão.', 'extra' => ''],
    ],
    'unauthorized' => [
        'label'   => 'Sem autenticação',
        'method'  => '*',
        'path'    => '/api/v1/' . h($entity['slug']),
        'desc'    => 'Quando a chave de API está ausente ou é inválida (global)',
        'default' => ['code' => 401, 'message' => 'Chave de API inválida ou ausente.', 'extra' => ''],
    ],
    'forbidden' => [
        'label'   => 'Sem permissão',
        'method'  => '*',
        'path'    => '/api/v1/' . h($entity['slug']),
        'desc'    => 'Quando a chave existe mas não tem permissão para esta operação (global)',
        'default' => ['code' => 403, 'message' => 'Sua chave não tem permissão para esta operação.', 'extra' => ''],
    ],
    'rate_limit' => [
        'label'   => 'Rate limit excedido',
        'method'  => '*',
        'path'    => '/api/v1/' . h($entity['slug']),
        'desc'    => 'Quando o limite de requisições por minuto é atingido',
        'default' => ['code' => 429, 'message' => 'Muitas requisições. Aguarde antes de tentar novamente.', 'extra' => ''],
    ],
];

// Method colors
$methodColors = [
    'GET'    => ['bg' => 'rgba(34,197,94,.15)',  'color' => '#86efac'],
    'POST'   => ['bg' => 'rgba(0,212,255,.12)',  'color' => 'var(--ac)'],
    'PUT'    => ['bg' => 'rgba(245,158,11,.15)', 'color' => '#fcd34d'],
    'DELETE' => ['bg' => 'rgba(239,68,68,.15)',  'color' => '#fca5a5'],
    '*'      => ['bg' => 'rgba(100,116,139,.15)','color' => '#94a3b8'],
];

// HTTP code colors
function codeColor(int $code): string {
    return match(true) {
        $code >= 200 && $code < 300 => '#86efac',
        $code >= 400 && $code < 500 => '#fcd34d',
        $code >= 500                => '#fca5a5',
        default                     => 'var(--mt2)',
    };
}
?>

<style>
.op-card        { background:var(--sf);border:1px solid var(--bd);border-radius:var(--r);margin-bottom:10px;overflow:hidden; }
.op-head        { display:flex;align-items:center;gap:12px;padding:14px 16px;cursor:pointer;transition:background .15s; }
.op-head:hover  { background:var(--sf2); }
.op-badge       { font-size:.68rem;font-weight:800;padding:3px 8px;border-radius:4px;letter-spacing:.05em;min-width:48px;text-align:center;flex-shrink:0; }
.op-path        { font-family:monospace;font-size:.82rem;color:var(--mt2); }
.op-desc        { font-size:.78rem;color:var(--mt);margin-left:auto; }
.op-code-badge  { font-family:var(--fd);font-size:.8rem;font-weight:800;padding:2px 8px;border-radius:4px;background:var(--sf2);flex-shrink:0; }
.op-body        { display:none;padding:20px;border-top:1px solid var(--bd);background:var(--bg2); }
.op-body.open   { display:block; }
.op-grid        { display:grid;grid-template-columns:120px 1fr;gap:12px 16px;align-items:center; }
.op-label       { font-size:.78rem;font-weight:700;color:var(--mt2); }
.op-input       { background:var(--sf2);border:1px solid var(--bd2);border-radius:var(--r2);color:var(--tx);padding:8px 12px;font-size:.88rem;width:100%;outline:none;transition:border-color .15s; }
.op-input:focus { border-color:var(--ac); }
.op-input.mono  { font-family:monospace; }
.op-number      { width:90px; }
.preview-box    { background:var(--bg);border:1px solid var(--bd);border-radius:var(--r2);padding:12px 14px;font-family:monospace;font-size:.76rem;color:var(--tx);white-space:pre;overflow-x:auto;margin-top:14px; }
.reset-btn      { background:none;border:1px solid var(--bd);color:var(--mt);border-radius:var(--r2);padding:4px 10px;font-size:.75rem;cursor:pointer;transition:all .15s; }
.reset-btn:hover{ color:var(--tx);border-color:var(--bd2); }
.group-sep      { font-size:.65rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--mt);margin:20px 0 10px;padding-top:12px;border-top:1px solid var(--bd); }
</style>

<form method="POST" action="<?= url('/entities/'.$entity['id'].'/api-responses') ?>" id="api-resp-form">

  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
    <div>
      <div style="font-size:.82rem;color:var(--mt);line-height:1.6">
        Personalize o código HTTP e a mensagem retornados pela API para cada operação desta entidade.<br>
        Dados extras em <code>extra_data</code> são mesclados no campo <code>data</code> da resposta.
      </div>
    </div>
    <button type="submit" class="btn btn-primary" style="flex-shrink:0">💾 Salvar Respostas</button>
  </div>

  <!-- CRUD operations -->
  <div class="group-sep">📥 Leitura</div>

  <?php foreach (['select_all','select_one','select_not_found'] as $opKey):
    $op  = $operations[$opKey];
    $mc  = $methodColors[$op['method']];
    $saved = $savedResponses[$opKey] ?? $op['default'];
    $code  = (int)($saved['code']    ?? $op['default']['code']);
    $msg   = $saved['message'] ?? $op['default']['message'];
    $extra = $saved['extra']   ?? $op['default']['extra'];
  ?>
  <div class="op-card">
    <div class="op-head" onclick="toggleOp(this)">
      <span class="op-badge" style="background:<?= $mc['bg'] ?>;color:<?= $mc['color'] ?>"><?= h($op['method']) ?></span>
      <span class="op-path"><?= h($op['path']) ?></span>
      <span class="op-code-badge" style="color:<?= codeColor($code) ?>" id="badge-<?= $opKey ?>"><?= $code ?></span>
      <span class="op-desc"><?= h($op['label']) ?></span>
      <span style="color:var(--mt);font-size:.8rem;flex-shrink:0">▼</span>
    </div>
    <div class="op-body">
      <p style="font-size:.8rem;color:var(--mt);margin-bottom:16px"><?= h($op['desc']) ?></p>
      <div class="op-grid">
        <label class="op-label">Código HTTP</label>
        <div style="display:flex;gap:8px;align-items:center">
          <input type="number" name="responses[<?= $opKey ?>][code]" class="op-input op-number"
                 value="<?= $code ?>" min="100" max="599"
                 oninput="updatePreview('<?= $opKey ?>')"
                 id="code-<?= $opKey ?>">
          <span id="codename-<?= $opKey ?>" style="font-size:.78rem;color:var(--mt)"><?= httpLabel($code) ?></span>
          <button type="button" class="reset-btn" onclick="resetOp('<?= $opKey ?>',<?= $op['default']['code'] ?>,'<?= addslashes($op['default']['message']) ?>','<?= addslashes($op['default']['extra']) ?>')">↺ Padrão</button>
        </div>
        <label class="op-label">Mensagem</label>
        <input type="text" name="responses[<?= $opKey ?>][message]" class="op-input"
               value="<?= h($msg) ?>" placeholder="Mensagem retornada em errors[] ou message"
               oninput="updatePreview('<?= $opKey ?>')"
               id="msg-<?= $opKey ?>">
        <label class="op-label">extra_data (JSON)</label>
        <input type="text" name="responses[<?= $opKey ?>][extra]" class="op-input mono"
               value="<?= h($extra) ?>" placeholder='{"chave": "valor"} — opcional'
               oninput="updatePreview('<?= $opKey ?>')"
               id="extra-<?= $opKey ?>">
      </div>
      <div class="preview-box" id="preview-<?= $opKey ?>"></div>
    </div>
  </div>
  <?php endforeach; ?>

  <div class="group-sep">📤 Escrita</div>

  <?php foreach (['insert','insert_validation','update','update_not_found','delete','delete_not_found'] as $opKey):
    $op  = $operations[$opKey];
    $mc  = $methodColors[$op['method']];
    $saved = $savedResponses[$opKey] ?? $op['default'];
    $code  = (int)($saved['code']    ?? $op['default']['code']);
    $msg   = $saved['message'] ?? $op['default']['message'];
    $extra = $saved['extra']   ?? $op['default']['extra'];
  ?>
  <div class="op-card">
    <div class="op-head" onclick="toggleOp(this)">
      <span class="op-badge" style="background:<?= $mc['bg'] ?>;color:<?= $mc['color'] ?>"><?= h($op['method']) ?></span>
      <span class="op-path"><?= h($op['path']) ?></span>
      <span class="op-code-badge" style="color:<?= codeColor($code) ?>" id="badge-<?= $opKey ?>"><?= $code ?></span>
      <span class="op-desc"><?= h($op['label']) ?></span>
      <span style="color:var(--mt);font-size:.8rem;flex-shrink:0">▼</span>
    </div>
    <div class="op-body">
      <p style="font-size:.8rem;color:var(--mt);margin-bottom:16px"><?= h($op['desc']) ?></p>
      <div class="op-grid">
        <label class="op-label">Código HTTP</label>
        <div style="display:flex;gap:8px;align-items:center">
          <input type="number" name="responses[<?= $opKey ?>][code]" class="op-input op-number"
                 value="<?= $code ?>" min="100" max="599"
                 oninput="updatePreview('<?= $opKey ?>')"
                 id="code-<?= $opKey ?>">
          <span id="codename-<?= $opKey ?>" style="font-size:.78rem;color:var(--mt)"><?= httpLabel($code) ?></span>
          <button type="button" class="reset-btn" onclick="resetOp('<?= $opKey ?>',<?= $op['default']['code'] ?>,'<?= addslashes($op['default']['message']) ?>','<?= addslashes($op['default']['extra']) ?>')">↺ Padrão</button>
        </div>
        <label class="op-label">Mensagem</label>
        <input type="text" name="responses[<?= $opKey ?>][message]" class="op-input"
               value="<?= h($msg) ?>" placeholder="Mensagem retornada"
               oninput="updatePreview('<?= $opKey ?>')"
               id="msg-<?= $opKey ?>">
        <label class="op-label">extra_data (JSON)</label>
        <input type="text" name="responses[<?= $opKey ?>][extra]" class="op-input mono"
               value="<?= h($extra) ?>" placeholder='{"chave": "valor"} — opcional'
               oninput="updatePreview('<?= $opKey ?>')"
               id="extra-<?= $opKey ?>">
      </div>
      <div class="preview-box" id="preview-<?= $opKey ?>"></div>
    </div>
  </div>
  <?php endforeach; ?>

  <div class="group-sep">🔐 Segurança & Limites (globais para esta entidade)</div>

  <?php foreach (['unauthorized','forbidden','rate_limit'] as $opKey):
    $op  = $operations[$opKey];
    $mc  = $methodColors[$op['method']];
    $saved = $savedResponses[$opKey] ?? $op['default'];
    $code  = (int)($saved['code']    ?? $op['default']['code']);
    $msg   = $saved['message'] ?? $op['default']['message'];
    $extra = $saved['extra']   ?? $op['default']['extra'];
  ?>
  <div class="op-card">
    <div class="op-head" onclick="toggleOp(this)">
      <span class="op-badge" style="background:<?= $mc['bg'] ?>;color:<?= $mc['color'] ?>"><?= h($op['method']) ?></span>
      <span class="op-path"><?= h($op['path']) ?></span>
      <span class="op-code-badge" style="color:<?= codeColor($code) ?>" id="badge-<?= $opKey ?>"><?= $code ?></span>
      <span class="op-desc"><?= h($op['label']) ?></span>
      <span style="color:var(--mt);font-size:.8rem;flex-shrink:0">▼</span>
    </div>
    <div class="op-body">
      <p style="font-size:.8rem;color:var(--mt);margin-bottom:16px"><?= h($op['desc']) ?></p>
      <div class="op-grid">
        <label class="op-label">Código HTTP</label>
        <div style="display:flex;gap:8px;align-items:center">
          <input type="number" name="responses[<?= $opKey ?>][code]" class="op-input op-number"
                 value="<?= $code ?>" min="100" max="599"
                 oninput="updatePreview('<?= $opKey ?>')"
                 id="code-<?= $opKey ?>">
          <span id="codename-<?= $opKey ?>" style="font-size:.78rem;color:var(--mt)"><?= httpLabel($code) ?></span>
          <button type="button" class="reset-btn" onclick="resetOp('<?= $opKey ?>',<?= $op['default']['code'] ?>,'<?= addslashes($op['default']['message']) ?>','<?= addslashes($op['default']['extra']) ?>')">↺ Padrão</button>
        </div>
        <label class="op-label">Mensagem</label>
        <input type="text" name="responses[<?= $opKey ?>][message]" class="op-input"
               value="<?= h($msg) ?>" placeholder="Mensagem retornada"
               oninput="updatePreview('<?= $opKey ?>')"
               id="msg-<?= $opKey ?>">
        <label class="op-label">extra_data (JSON)</label>
        <input type="text" name="responses[<?= $opKey ?>][extra]" class="op-input mono"
               value="<?= h($extra) ?>" placeholder='{"chave": "valor"} — opcional'
               oninput="updatePreview('<?= $opKey ?>')"
               id="extra-<?= $opKey ?>">
      </div>
      <div class="preview-box" id="preview-<?= $opKey ?>"></div>
    </div>
  </div>
  <?php endforeach; ?>

  <div style="display:flex;justify-content:flex-end;margin-top:20px">
    <button type="submit" class="btn btn-primary">💾 Salvar todas as respostas</button>
  </div>

</form>

<?php
function httpLabel(int $code): string {
    return match($code) {
        200 => 'OK', 201 => 'Created', 204 => 'No Content',
        400 => 'Bad Request', 401 => 'Unauthorized', 403 => 'Forbidden',
        404 => 'Not Found', 409 => 'Conflict', 410 => 'Gone',
        422 => 'Unprocessable', 423 => 'Locked', 429 => 'Too Many Requests',
        500 => 'Internal Server Error', 503 => 'Service Unavailable',
        default => 'Custom',
    };
}
?>

<script>
const HTTP_LABELS = {
  200:'OK',201:'Created',204:'No Content',
  400:'Bad Request',401:'Unauthorized',403:'Forbidden',
  404:'Not Found',409:'Conflict',410:'Gone',
  422:'Unprocessable',423:'Locked',429:'Too Many Requests',
  500:'Internal Server Error',503:'Service Unavailable',
};

function httpLabel(code) { return HTTP_LABELS[code] || 'Custom'; }

function codeColor(c) {
  if (c >= 200 && c < 300) return '#86efac';
  if (c >= 400 && c < 500) return '#fcd34d';
  if (c >= 500)             return '#fca5a5';
  return 'var(--mt2)';
}

function updatePreview(opKey) {
  const code  = parseInt(document.getElementById('code-'+opKey)?.value || 0);
  const msg   = document.getElementById('msg-'+opKey)?.value || '';
  const extra = document.getElementById('extra-'+opKey)?.value || '';

  // Update badge
  const badge = document.getElementById('badge-'+opKey);
  if (badge) { badge.textContent = code; badge.style.color = codeColor(code); }

  // Update label
  const lbl = document.getElementById('codename-'+opKey);
  if (lbl) lbl.textContent = httpLabel(code);

  // Build preview
  let extraParsed = null;
  try { if (extra.trim()) extraParsed = JSON.parse(extra); } catch(e) {}

  const isSuccess = code >= 200 && code < 300;
  const is204     = code === 204;

  let preview;
  if (is204) {
    preview = '// HTTP 204 — sem corpo na resposta';
  } else {
    const obj = {
      data:   isSuccess ? (extraParsed ? { '...': 'campos do registro', ...extraParsed } : { '...': 'campos do registro' }) : null,
      meta:   null,
      errors: isSuccess ? null : (msg ? [msg] : null),
    };
    if (isSuccess && msg) obj.data = { ...obj.data, _message: msg };
    if (!isSuccess && extraParsed) obj.errors = [...(obj.errors||[]), extraParsed];
    preview = 'HTTP ' + code + ' ' + httpLabel(code) + '\n\n' + JSON.stringify(obj, null, 2);
  }

  const box = document.getElementById('preview-'+opKey);
  if (box) box.textContent = preview;
}

function toggleOp(head) {
  const body  = head.nextElementSibling;
  const arrow = head.querySelector('span:last-child');
  const isOpen = body.classList.toggle('open');
  arrow.textContent = isOpen ? '▲' : '▼';
  if (isOpen) {
    // Init preview on first open
    const opKey = body.querySelector('[id^="code-"]')?.id.replace('code-','');
    if (opKey) updatePreview(opKey);
  }
}

function resetOp(opKey, code, msg, extra) {
  const c = document.getElementById('code-'+opKey);
  const m = document.getElementById('msg-'+opKey);
  const e = document.getElementById('extra-'+opKey);
  if (c) c.value = code;
  if (m) m.value = msg;
  if (e) e.value = extra;
  updatePreview(opKey);
}
</script>
