<?php
$page_title  = 'Documentação da API';
$active_page = 'api-docs';
$breadcrumbs = [
    ['label' => 'API & Chaves', 'url' => url('/api')],
    ['label' => 'Documentação'],
];
partial('layout/header');

$baseUrl  = rtrim(DB::setting('app_url'), '/') . '/api/v1';
$appName  = DB::setting('app_name', 'FlexCore');
$version  = defined('VERSION') ? VERSION : '1.0.0';
?>

<style>
.doc-layout    { display:grid; grid-template-columns:175px 1fr; gap:0; min-height:calc(100vh - 120px); }
.doc-nav       { border-right:1px solid var(--bd); padding:20px 0; position:sticky; top:52px; max-height:calc(100vh - 52px); overflow-y:auto; }
.doc-nav-head  { font-size:.65rem; font-weight:700; letter-spacing:.1em; text-transform:uppercase; color:var(--mt); padding:0 16px; margin:16px 0 6px; }
.doc-nav a     { display:block; padding:7px 16px; font-size:.82rem; color:var(--mt2); text-decoration:none; border-left:2px solid transparent; transition:all .15s; }
.doc-nav a:hover  { color:var(--tx); background:var(--sf); }
.doc-nav a.active { color:var(--ac); border-left-color:var(--ac); background:color-mix(in srgb,var(--ac) 8%,transparent); }
.doc-content   { padding:28px 32px; }
.doc-section   { margin-bottom:48px; scroll-margin-top:72px; }
.doc-h1        { font-family:var(--fd); font-size:1.4rem; font-weight:800; margin-bottom:8px; }
.doc-h2        { font-family:var(--fd); font-size:1.05rem; font-weight:700; margin:32px 0 12px; padding-top:20px; border-top:1px solid var(--bd); }
.doc-p         { color:var(--mt2); font-size:.88rem; line-height:1.7; margin-bottom:12px; }
.method-block  { border:1px solid var(--bd); border-radius:var(--r); overflow:hidden; margin-bottom:12px; }
.method-head   { display:flex; align-items:center; gap:12px; padding:13px 16px; cursor:pointer; background:var(--sf); user-select:none; transition:background .15s; }
.method-head:hover { background:var(--sf2); }
.method-badge  { font-size:.7rem; font-weight:800; padding:3px 9px; border-radius:4px; letter-spacing:.06em; min-width:52px; text-align:center; flex-shrink:0; }
.GET     { background:rgba(34,197,94,.15);  color:#86efac; }
.POST    { background:rgba(0,212,255,.12);  color:var(--ac); }
.PUT     { background:rgba(245,158,11,.15); color:#fcd34d; }
.DELETE  { background:rgba(239,68,68,.15);  color:#fca5a5; }
.method-path   { font-family:monospace; font-size:.88rem; color:var(--tx); font-weight:600; }
.method-desc   { font-size:.8rem; color:var(--mt); margin-left:auto; }
.method-body   { display:none; padding:20px; border-top:1px solid var(--bd); background:var(--bg2); }
.method-body.open { display:block; }
.param-table   { width:100%; border-collapse:collapse; font-size:.82rem; margin:10px 0; }
.param-table th{ text-align:left; color:var(--mt); font-size:.7rem; font-weight:700; letter-spacing:.08em; text-transform:uppercase; padding:0 10px 8px; border-bottom:1px solid var(--bd); }
.param-table td{ padding:8px 10px; border-bottom:1px solid var(--bd); vertical-align:top; }
.param-table tr:last-child td { border:none; }
.param-name    { font-family:monospace; color:var(--ac); font-size:.82rem; }
.param-req     { color:var(--rd); font-size:.72rem; font-weight:700; }
.param-opt     { color:var(--mt); font-size:.72rem; }
.code-block    { background:var(--bg); border:1px solid var(--bd); border-radius:var(--r2); padding:14px 16px; font-family:monospace; font-size:.78rem; line-height:1.6; color:var(--tx); overflow-x:auto; position:relative; margin:10px 0; white-space:pre; }
.copy-btn      { position:absolute; top:8px; right:8px; background:var(--sf2); border:1px solid var(--bd); color:var(--mt); border-radius:4px; padding:3px 8px; font-size:.72rem; cursor:pointer; transition:all .15s; }
.copy-btn:hover{ color:var(--tx); background:var(--sf3); }
.status-grid   { display:grid; grid-template-columns:repeat(auto-fill,minmax(200px,1fr)); gap:8px; margin:10px 0; }
.status-card   { border-radius:var(--r2); padding:10px 14px; }
.s200 { background:rgba(34,197,94,.08);  border:1px solid rgba(34,197,94,.2); }
.s201 { background:rgba(0,212,255,.07);  border:1px solid rgba(0,212,255,.2); }
.s204 { background:rgba(100,116,139,.1); border:1px solid rgba(100,116,139,.2); }
.s400 { background:rgba(245,158,11,.08); border:1px solid rgba(245,158,11,.2); }
.s401 { background:rgba(239,68,68,.08);  border:1px solid rgba(239,68,68,.2); }
.s403 { background:rgba(239,68,68,.08);  border:1px solid rgba(239,68,68,.2); }
.s404 { background:rgba(239,68,68,.08);  border:1px solid rgba(239,68,68,.2); }
.s422 { background:rgba(245,158,11,.08); border:1px solid rgba(245,158,11,.2); }
.s429 { background:rgba(239,68,68,.08);  border:1px solid rgba(239,68,68,.2); }
.status-code   { font-family:var(--fd); font-size:1rem; font-weight:800; margin-bottom:2px; }
.status-label  { font-size:.75rem; color:var(--mt2); margin-bottom:4px; }
.status-when   { font-size:.72rem; color:var(--mt); line-height:1.4; }
.tabs          { display:flex; gap:0; border-bottom:1px solid var(--bd); margin-bottom:14px; }
.tab           { padding:7px 14px; font-size:.8rem; font-weight:600; color:var(--mt); cursor:pointer; border-bottom:2px solid transparent; margin-bottom:-1px; transition:all .15s; }
.tab.active    { color:var(--ac); border-bottom-color:var(--ac); }
.tab-pane      { display:none; }
.tab-pane.active { display:block; }
.try-section   { background:var(--sf);border:1px solid var(--bd2);border-radius:var(--r2);padding:16px;margin-top:14px; }
.try-row       { display:flex;gap:8px;margin-bottom:10px;align-items:center; }
.try-label     { font-size:.75rem;font-weight:700;color:var(--mt2);width:80px;flex-shrink:0; }
.try-input     { flex:1;background:var(--sf2);border:1px solid var(--bd2);border-radius:var(--r2);color:var(--tx);padding:7px 10px;font-size:.82rem;font-family:monospace; }
.try-select    { flex:1;background:var(--sf2);border:1px solid var(--bd2);border-radius:var(--r2);color:var(--tx);padding:7px 10px;font-size:.82rem; }
.try-btn       { background:var(--ac);color:#000;border:none;border-radius:var(--r2);padding:8px 18px;font-weight:700;font-size:.82rem;cursor:pointer; }
.response-box  { background:var(--bg);border:1px solid var(--bd);border-radius:var(--r2);padding:14px;font-family:monospace;font-size:.78rem;white-space:pre-wrap;color:var(--tx);max-height:300px;overflow-y:auto;margin-top:10px;display:none; }
.key-badge     { display:inline-flex;align-items:center;gap:6px;background:var(--sf2);border:1px solid var(--bd2);border-radius:6px;padding:4px 10px;font-size:.78rem;font-family:monospace;color:var(--ac); }
</style>

<?php
// Load all entities with their fields
$entities = DB::q('SELECT * FROM entities WHERE active=1 ORDER BY position ASC, name ASC');
foreach ($entities as &$ent) {
    $ent['fields']        = DB::q('SELECT * FROM entity_fields WHERE entity_id=? ORDER BY position ASC', [$ent['id']]);
    $ent['api_responses'] = json_decode($ent['api_responses'] ?? '{}', true) ?: [];
}
unset($ent);

// Helper: get response config for an operation, with fallback to defaults
function getResp(array $ent, string $op, int $defaultCode, string $defaultMsg = ''): array {
    $r = $ent['api_responses'][$op] ?? [];
    return [
        'code'    => (int)($r['code']    ?? $defaultCode),
        'message' => $r['message'] ?? $defaultMsg,
        'extra'   => $r['extra']   ?? '',
    ];
}

// Load API keys for the "try it" selector
$apiKeys = DB::q('SELECT id, name, key_preview, active FROM api_keys WHERE active=1 ORDER BY created_at DESC');

// PHP helper for inline color (used in response cards)
function codeColor(int $code): string {
    return match(true) {
        $code >= 200 && $code < 300 => '#86efac',
        $code >= 400 && $code < 500 => '#fcd34d',
        $code >= 500                => '#fca5a5',
        default                     => 'var(--mt2)',
    };
}

// Field type → JSON type mapping
$typeMap = [
    'text'        => 'string',
    'textarea'    => 'string',
    'email'       => 'string',
    'url'         => 'string',
    'phone'       => 'string',
    'select'      => 'string',
    'multiselect' => 'array',
    'checkbox'    => 'boolean',
    'number'      => 'number',
    'currency'    => 'number',
    'date'        => 'string (YYYY-MM-DD)',
    'datetime'    => 'string (YYYY-MM-DD HH:MM:SS)',
    'relation'    => 'integer (record ID)',
    'file'        => 'string (filename)',
];

// Build example payload for an entity
function buildPayload(array $fields, bool $onlyRequired = false): string {
    global $typeMap;
    $lines = [];
    foreach ($fields as $f) {
        if ($onlyRequired && !$f['required']) continue;
        $type = $typeMap[$f['field_type']] ?? 'string';
        $example = match($f['field_type']) {
            'checkbox'    => 'false',
            'number',
            'currency'    => '0',
            'relation'    => '1',
            'multiselect' => '["opcao1", "opcao2"]',
            'date'        => '"2025-01-15"',
            'datetime'    => '"2025-01-15 14:30:00"',
            default       => '"' . $f['name'] . ' exemplo"',
        };
        $req = $f['required'] ? ' // obrigatório' : '';
        $lines[] = '  "' . $f['slug'] . '": ' . $example . $req;
    }
    if (empty($lines)) return "{}";
    return "{\n" . implode(",\n", $lines) . "\n}";
}
?>

<div class="doc-layout">

  <!-- Left nav -->
  <nav class="doc-nav">
    <div class="doc-nav-head">Visão geral</div>
    <a href="#intro"      class="active" onclick="scrollTo('intro',this)">Introdução</a>
    <a href="#auth"       onclick="scrollTo('auth',this)">Autenticação</a>
    <a href="#envelope"   onclick="scrollTo('envelope',this)">Envelope de resposta</a>
    <a href="#codes"      onclick="scrollTo('codes',this)">Códigos HTTP</a>
    <a href="#filters"    onclick="scrollTo('filters',this)">Filtros & Paginação</a>
    <a href="#errors"     onclick="scrollTo('errors',this)">Erros customizados</a>

    <?php if (!empty($entities)): ?>
    <div class="doc-nav-head" style="margin-top:8px">Entidades</div>
    <?php foreach ($entities as $ent): ?>
    <a href="#ent-<?= h($ent['slug']) ?>" onclick="scrollTo('ent-<?= h($ent['slug']) ?>',this)">
      <?= h($ent['icon']) ?> <?= h($ent['name']) ?>
    </a>
    <?php endforeach; ?>
    <?php endif; ?>
  </nav>

  <!-- Main content -->
  <div class="doc-content">

    <!-- INTRO -->
    <div class="doc-section" id="intro">
      <div class="doc-h1">📡 Documentação da API — <?= h($appName) ?></div>
      <p class="doc-p">API REST gerada automaticamente a partir das entidades configuradas no painel. Todos os endpoints seguem o mesmo padrão de envelope, autenticação e códigos de resposta.</p>
      <div class="code-block"><button class="copy-btn" onclick="copyCode(this)">📋</button>Base URL: <?= h($baseUrl) ?>
Versão:   <?= h($version) ?>
Formato:  application/json
Charset:  utf-8</div>
    </div>

    <!-- AUTH -->
    <div class="doc-section" id="auth">
      <div class="doc-h1">🔑 Autenticação</div>
      <p class="doc-p">Todas as requisições devem incluir o header <code>Authorization</code> com uma chave de API válida criada no painel em <a href="<?= url('/api') ?>" style="color:var(--ac)">API & Chaves</a>.</p>

      <div class="doc-h2">Header obrigatório</div>
      <div class="code-block"><button class="copy-btn" onclick="copyCode(this)">📋</button>Authorization: Bearer fc_sua_chave_aqui</div>

      <div class="doc-h2">Chave de teste</div>
      <?php if (!empty($apiKeys)): ?>
      <p class="doc-p">Chaves ativas disponíveis para testar os endpoints abaixo:</p>
      <?php foreach ($apiKeys as $k): ?>
      <div class="key-badge" style="margin-bottom:6px">🔑 <?= h($k['name']) ?> — <span style="color:var(--mt)"><?= h($k['key_preview']) ?>••••</span></div>
      <?php endforeach; ?>
      <?php else: ?>
      <div style="background:rgba(245,158,11,.08);border:1px solid rgba(245,158,11,.2);border-radius:var(--r2);padding:12px 16px;font-size:.83rem;color:#fcd34d">
        ⚠️ Nenhuma chave ativa. <a href="<?= url('/api') ?>" style="color:var(--ac)">Crie uma chave →</a>
      </div>
      <?php endif; ?>
    </div>

    <!-- ENVELOPE -->
    <div class="doc-section" id="envelope">
      <div class="doc-h1">📦 Envelope de resposta</div>
      <p class="doc-p">Todas as respostas seguem o mesmo envelope JSON, independente do endpoint ou status HTTP.</p>

      <div class="doc-h2">Estrutura</div>
      <div class="code-block"><button class="copy-btn" onclick="copyCode(this)">📋</button>{
  "data":   { ... } | [ ... ] | null,
  "meta":   {
    "total":        100,
    "per_page":     25,
    "current_page": 1,
    "last_page":    4
  } | null,
  "errors": [ "mensagem de erro" ] | null
}</div>

      <div class="doc-h2">Exemplos</div>
      <div class="tabs">
        <div class="tab active" onclick="switchTab(this,'env-tabs','t-ok')">✅ Sucesso (200)</div>
        <div class="tab" onclick="switchTab(this,'env-tabs','t-list')">📋 Lista (200)</div>
        <div class="tab" onclick="switchTab(this,'env-tabs','t-err')">❌ Erro (422)</div>
      </div>
      <div id="env-tabs">
        <div class="tab-pane active" id="t-ok">
          <div class="code-block"><button class="copy-btn" onclick="copyCode(this)">📋</button>{
  "data": {
    "id":         42,
    "created_at": "2025-01-15 14:30:00",
    "updated_at": "2025-01-15 14:30:00",
    "nome":       "João Silva",
    "email":      "joao@exemplo.com"
  },
  "meta":   null,
  "errors": null
}</div>
        </div>
        <div class="tab-pane" id="t-list">
          <div class="code-block"><button class="copy-btn" onclick="copyCode(this)">📋</button>{
  "data": [
    { "id": 1, "nome": "João Silva", "email": "joao@exemplo.com" },
    { "id": 2, "nome": "Maria Souza", "email": "maria@exemplo.com" }
  ],
  "meta": {
    "total":        50,
    "per_page":     25,
    "current_page": 1,
    "last_page":    2
  },
  "errors": null
}</div>
        </div>
        <div class="tab-pane" id="t-err">
          <div class="code-block"><button class="copy-btn" onclick="copyCode(this)">📋</button>{
  "data":   null,
  "meta":   null,
  "errors": [
    "Campo \"nome\" é obrigatório.",
    "Campo \"email\" deve ser um e-mail válido."
  ]
}</div>
        </div>
      </div>
    </div>

    <!-- HTTP CODES -->
    <div class="doc-section" id="codes">
      <div class="doc-h1">🔢 Códigos HTTP</div>
      <p class="doc-p">A API usa os códigos HTTP padrão. O campo <code>errors</code> no envelope sempre explica o que deu errado.</p>
      <div class="status-grid">
        <div class="status-card s200">
          <div class="status-code" style="color:#86efac">200</div>
          <div class="status-label">OK</div>
          <div class="status-when">GET bem-sucedido. Dados retornados em <code>data</code>.</div>
        </div>
        <div class="status-card s201">
          <div class="status-code" style="color:var(--ac)">201</div>
          <div class="status-label">Created</div>
          <div class="status-when">POST bem-sucedido. Registro criado retornado em <code>data</code>.</div>
        </div>
        <div class="status-card s204">
          <div class="status-code" style="color:#94a3b8">204</div>
          <div class="status-label">No Content</div>
          <div class="status-when">DELETE bem-sucedido. Sem corpo na resposta.</div>
        </div>
        <div class="status-card s400">
          <div class="status-code" style="color:#fcd34d">400</div>
          <div class="status-label">Bad Request</div>
          <div class="status-when">Requisição malformada ou parâmetros inválidos.</div>
        </div>
        <div class="status-card s401">
          <div class="status-code" style="color:#fca5a5">401</div>
          <div class="status-label">Unauthorized</div>
          <div class="status-when">Chave ausente, inválida ou inativa.</div>
        </div>
        <div class="status-card s403">
          <div class="status-code" style="color:#fca5a5">403</div>
          <div class="status-label">Forbidden</div>
          <div class="status-when">Chave válida mas sem permissão para esta operação.</div>
        </div>
        <div class="status-card s404">
          <div class="status-code" style="color:#fca5a5">404</div>
          <div class="status-label">Not Found</div>
          <div class="status-when">Entidade ou registro não encontrado.</div>
        </div>
        <div class="status-card s422">
          <div class="status-code" style="color:#fcd34d">422</div>
          <div class="status-label">Unprocessable</div>
          <div class="status-when">Validação falhou. Veja <code>errors[]</code> para detalhe campo a campo.</div>
        </div>
        <div class="status-card s429">
          <div class="status-code" style="color:#fca5a5">429</div>
          <div class="status-label">Too Many Requests</div>
          <div class="status-when">Rate limit excedido. Aguarde antes de tentar novamente.</div>
        </div>
      </div>
    </div>

    <!-- FILTERS -->
    <div class="doc-section" id="filters">
      <div class="doc-h1">🔍 Filtros, Busca & Paginação</div>
      <p class="doc-p">Disponíveis em todos os endpoints de listagem (<code>GET /api/v1/{entidade}</code>).</p>

      <div class="doc-h2">Parâmetros</div>
      <table class="param-table">
        <thead><tr><th>Parâmetro</th><th>Tipo</th><th>Descrição</th><th>Exemplo</th></tr></thead>
        <tbody>
          <tr><td class="param-name">q</td><td>string</td><td>Busca em todos os campos de texto</td><td><code>?q=joão</code></td></tr>
          <tr><td class="param-name">filter[campo]</td><td>string</td><td>Filtro exato por slug do campo</td><td><code>?filter[status]=ativo</code></td></tr>
          <tr><td class="param-name">filter[campo][gte]</td><td>number</td><td>Maior ou igual</td><td><code>?filter[valor][gte]=1000</code></td></tr>
          <tr><td class="param-name">filter[campo][lte]</td><td>number</td><td>Menor ou igual</td><td><code>?filter[valor][lte]=5000</code></td></tr>
          <tr><td class="param-name">filter[campo][like]</td><td>string</td><td>Contém (case-insensitive)</td><td><code>?filter[nome][like]=silva</code></td></tr>
          <tr><td class="param-name">sort</td><td>string</td><td>Campo para ordenar. Prefixo <code>-</code> = decrescente</td><td><code>?sort=-created_at</code></td></tr>
          <tr><td class="param-name">page</td><td>integer</td><td>Página atual (começa em 1)</td><td><code>?page=2</code></td></tr>
          <tr><td class="param-name">per_page</td><td>integer</td><td>Itens por página (máx 100, padrão 25)</td><td><code>?per_page=50</code></td></tr>
          <tr><td class="param-name">fields</td><td>string</td><td>Campos a retornar, separados por vírgula</td><td><code>?fields=id,nome,email</code></td></tr>
        </tbody>
      </table>

      <div class="doc-h2">Exemplo combinado</div>
      <div class="code-block"><button class="copy-btn" onclick="copyCode(this)">📋</button>GET <?= h($baseUrl) ?>/clientes?filter[status]=ativo&filter[valor][gte]=1000&sort=-created_at&page=1&per_page=25&fields=id,nome,email</div>
    </div>

    <!-- CUSTOM ERRORS -->
    <div class="doc-section" id="errors">
      <div class="doc-h1">⚙️ Respostas customizadas</div>
      <p class="doc-p">Você pode enviar códigos HTTP, mensagens e dados customizados a partir de automações com a ação <strong>Resposta API</strong>, ou via plugins usando o <code>HookDispatcher</code>.</p>

      <div class="doc-h2">Códigos customizados via Automação</div>
      <p class="doc-p">Ao configurar uma automação com gatilho <code>api.before_response</code>, você pode interceptar qualquer resposta e modificar código, dados ou mensagens de erro:</p>
      <div class="code-block"><button class="copy-btn" onclick="copyCode(this)">📋</button>// Exemplo: plugin ou automação interceptando a resposta
Hooks::filter('api.response', function(array $response, array $entity) {
    // Adicionar campo customizado
    $response['data']['_custom'] = 'valor extra';

    // Ou retornar erro customizado com código específico
    if ($alguma_condicao) {
        http_response_code(409);
        $response['errors'] = ['Conflito: registro duplicado detectado.'];
        $response['data']   = null;
    }
    return $response;
});
</div>

      <div class="doc-h2">Formato de erro customizado</div>
      <div class="code-block"><button class="copy-btn" onclick="copyCode(this)">📋</button>{
  "data":   null,
  "meta":   null,
  "errors": [
    "Mensagem principal do erro",
    "Detalhe adicional opcional"
  ]
}</div>

      <div class="doc-h2">Códigos recomendados para uso customizado</div>
      <div class="status-grid">
        <div class="status-card s400"><div class="status-code" style="color:#fcd34d">409</div><div class="status-label">Conflict</div><div class="status-when">Registro duplicado ou conflito de estado.</div></div>
        <div class="status-card s400"><div class="status-code" style="color:#fcd34d">410</div><div class="status-label">Gone</div><div class="status-when">Recurso existiu mas foi removido permanentemente.</div></div>
        <div class="status-card s400"><div class="status-code" style="color:#fcd34d">423</div><div class="status-label">Locked</div><div class="status-when">Registro bloqueado para edição.</div></div>
        <div class="status-card s400"><div class="status-code" style="color:#fcd34d">451</div><div class="status-label">Unavailable</div><div class="status-when">Indisponível por motivos legais.</div></div>
      </div>
    </div>

    <!-- ENTITY ENDPOINTS -->
    <?php foreach ($entities as $ent):
      $slug   = $ent['slug'];
      $fields = $ent['fields'];
      $name   = $ent['name'];
      $icon   = $ent['icon'];
      $url    = $baseUrl . '/' . $slug;
      $reqFields  = array_filter($fields, fn($f) => $f['required']);
      $fullPayload = buildPayload($fields, false);
      $reqPayload  = buildPayload($fields, true);
    ?>
    <div class="doc-section" id="ent-<?= h($slug) ?>">
      <div class="doc-h1"><?= h($icon) ?> <?= h($name) ?></div>
      <p class="doc-p">
        Endpoint base: <code style="color:var(--ac)"><?= h($url) ?></code>
        <?php if ($ent['description']): ?> — <?= h($ent['description']) ?><?php endif; ?>
      </p>

      <?php if (!empty($fields)): ?>
      <div class="doc-h2">Campos</div>
      <table class="param-table">
        <thead><tr><th>Slug</th><th>Nome</th><th>Tipo</th><th>JSON type</th><th>Obrigatório</th></tr></thead>
        <tbody>
          <?php foreach ($fields as $f): ?>
          <tr>
            <td class="param-name"><?= h($f['slug']) ?></td>
            <td style="color:var(--tx)"><?= h($f['name']) ?></td>
            <td><span class="badge bm" style="font-size:.7rem"><?= h($f['field_type']) ?></span></td>
            <td style="color:var(--mt);font-size:.8rem"><?= h($typeMap[$f['field_type']] ?? 'string') ?></td>
            <td><?= $f['required']
              ? '<span class="param-req">✓ Sim</span>'
              : '<span class="param-opt">Não</span>' ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>

      <!-- Custom responses banner -->
      <?php
      $apiResp = json_decode($ent['api_responses'] ?? '{}', true) ?: [];
      if (!empty($apiResp)):
      ?>
      <div style="background:rgba(0,212,255,.06);border:1px solid rgba(0,212,255,.2);border-radius:var(--r2);padding:12px 16px;margin-bottom:16px;display:flex;gap:10px;align-items:flex-start;font-size:.82rem">
        <span>⚙️</span>
        <div>
          <span style="font-weight:700;color:var(--ac)">Respostas customizadas ativas</span>
          <span style="color:var(--mt);margin-left:8px"><?= count($apiResp) ?> operação(ões) com código ou mensagem personalizada.</span>
          <div style="margin-top:6px;display:flex;flex-wrap:wrap;gap:6px">
            <?php foreach ($apiResp as $opKey => $cfg): ?>
            <span style="background:var(--sf2);border:1px solid var(--bd);border-radius:4px;padding:2px 8px;font-size:.72rem;font-family:monospace">
              <?= h($opKey) ?> → <span style="color:<?= ($cfg['code']>=200&&$cfg['code']<300)?'#86efac':(($cfg['code']>=400)?'#fcd34d':'var(--mt)') ?>;font-weight:700"><?= (int)$cfg['code'] ?></span>
            </span>
            <?php endforeach; ?>
          </div>
          <a href="<?= url('/entities/'.$ent['id'].'/fields?tab=api') ?>" style="color:var(--ac);font-size:.78rem;margin-top:6px;display:inline-block">Editar respostas →</a>
        </div>
      </div>
      <?php endif; ?>

      <!-- GET LIST -->
      <div class="method-block">
        <div class="method-head" onclick="toggleMethod(this)">
          <span class="method-badge GET">GET</span>
          <span class="method-path">/api/v1/<?= h($slug) ?></span>
          <span class="method-desc">Listar registros</span>
          <span style="margin-left:auto;color:var(--mt);font-size:.8rem">▼</span>
        </div>
        <div class="method-body">
          <div class="tabs" id="tabs-get-<?= h($slug) ?>">
            <div class="tab active" onclick="switchTab(this,'tabs-get-<?= h($slug) ?>','get-<?= h($slug) ?>-resp')">Resposta</div>
            <div class="tab" onclick="switchTab(this,'tabs-get-<?= h($slug) ?>','get-<?= h($slug) ?>-try')">▶ Testar</div>
          </div>
          <div id="tabs-get-<?= h($slug) ?>">
            <div class="tab-pane active" id="get-<?= h($slug) ?>-resp">
              <?php
              $rSelectAll = getResp($ent, 'select_all', 200, 'Registros retornados com sucesso.');
              $rSelectOne = getResp($ent, 'select_one', 200, 'Registro encontrado.');
              $rNotFound  = getResp($ent, 'select_not_found', 404, 'Registro não encontrado.');
              ?>
              <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-bottom:14px">
                <div style="background:var(--sf);border:1px solid var(--bd);border-radius:var(--r2);padding:10px 12px">
                  <div style="font-size:.7rem;color:var(--mt);margin-bottom:4px">GET lista</div>
                  <div style="font-family:var(--fd);font-weight:800;font-size:1rem;color:<?= codeColor($rSelectAll['code']) ?>"><?= $rSelectAll['code'] ?></div>
                  <div style="font-size:.72rem;color:var(--mt2);margin-top:2px"><?= h($rSelectAll['message']) ?></div>
                </div>
                <div style="background:var(--sf);border:1px solid var(--bd);border-radius:var(--r2);padding:10px 12px">
                  <div style="font-size:.7rem;color:var(--mt);margin-bottom:4px">GET um</div>
                  <div style="font-family:var(--fd);font-weight:800;font-size:1rem;color:<?= codeColor($rSelectOne['code']) ?>"><?= $rSelectOne['code'] ?></div>
                  <div style="font-size:.72rem;color:var(--mt2);margin-top:2px"><?= h($rSelectOne['message']) ?></div>
                </div>
                <div style="background:var(--sf);border:1px solid var(--bd);border-radius:var(--r2);padding:10px 12px">
                  <div style="font-size:.7rem;color:var(--mt);margin-bottom:4px">Não encontrado</div>
                  <div style="font-family:var(--fd);font-weight:800;font-size:1rem;color:<?= codeColor($rNotFound['code']) ?>"><?= $rNotFound['code'] ?></div>
                  <div style="font-size:.72rem;color:var(--mt2);margin-top:2px"><?= h($rNotFound['message']) ?></div>
                </div>
              </div>
              <p style="font-size:.82rem;color:var(--mt2);margin-bottom:10px">Retorna <code><?= $rSelectAll['code'] ?></code> com array paginado.</p>
              <div class="code-block"><button class="copy-btn" onclick="copyCode(this)">📋</button>{
  "data": [
    {
      "id":         1,
      "created_at": "2025-01-15 14:30:00",
<?php foreach (array_slice($fields,0,3) as $f): ?>      "<?= h($f['slug']) ?>": ...,
<?php endforeach; if(count($fields)>3): ?>      ...
<?php endif; ?>    }
  ],
  "meta": { "total": 50, "per_page": 25, "current_page": 1, "last_page": 2 },
  "errors": null
}</div>
            </div>
            <div class="tab-pane" id="get-<?= h($slug) ?>-try">
              <div class="try-section">
                <div class="try-row">
                  <span class="try-label">Chave API</span>
                  <select class="try-select" id="key-get-<?= h($slug) ?>">
                    <option value="">— selecione —</option>
                    <?php foreach ($apiKeys as $k): ?>
                    <option value="<?= h($k['key_preview']) ?>">🔑 <?= h($k['name']) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="try-row">
                  <span class="try-label">Filtros</span>
                  <input class="try-input" id="qs-get-<?= h($slug) ?>" placeholder="?q=busca&page=1&per_page=10">
                </div>
                <button class="try-btn" onclick="tryRequest('GET','<?= h($url) ?>','key-get-<?= h($slug) ?>','qs-get-<?= h($slug) ?>',null,'resp-get-<?= h($slug) ?>')">▶ Executar</button>
                <div class="response-box" id="resp-get-<?= h($slug) ?>"></div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- GET ONE -->
      <div class="method-block">
        <div class="method-head" onclick="toggleMethod(this)">
          <span class="method-badge GET">GET</span>
          <span class="method-path">/api/v1/<?= h($slug) ?>/{id}</span>
          <span class="method-desc">Buscar um registro</span>
          <span style="margin-left:auto;color:var(--mt);font-size:.8rem">▼</span>
        </div>
        <div class="method-body">
          <p style="font-size:.82rem;color:var(--mt2);margin-bottom:10px">Retorna <code>200 OK</code> com o registro, ou <code>404</code> se não encontrado.</p>
          <div class="code-block"><button class="copy-btn" onclick="copyCode(this)">📋</button>GET <?= h($url) ?>/1
Authorization: Bearer fc_sua_chave</div>
          <div class="try-section">
            <div class="try-row">
              <span class="try-label">Chave API</span>
              <select class="try-select" id="key-one-<?= h($slug) ?>">
                <option value="">— selecione —</option>
                <?php foreach ($apiKeys as $k): ?>
                <option value="<?= h($k['key_preview']) ?>">🔑 <?= h($k['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="try-row">
              <span class="try-label">ID</span>
              <input class="try-input" id="id-one-<?= h($slug) ?>" placeholder="1" style="max-width:100px">
            </div>
            <button class="try-btn" onclick="tryRequestId('GET','<?= h($url) ?>','key-one-<?= h($slug) ?>','id-one-<?= h($slug) ?>','resp-one-<?= h($slug) ?>')">▶ Executar</button>
            <div class="response-box" id="resp-one-<?= h($slug) ?>"></div>
          </div>
        </div>
      </div>

      <!-- POST -->
      <div class="method-block">
        <div class="method-head" onclick="toggleMethod(this)">
          <span class="method-badge POST">POST</span>
          <span class="method-path">/api/v1/<?= h($slug) ?></span>
          <span class="method-desc">Criar registro</span>
          <span style="margin-left:auto;color:var(--mt);font-size:.8rem">▼</span>
        </div>
        <div class="method-body">
          <div class="tabs" id="tabs-post-<?= h($slug) ?>">
            <div class="tab active" onclick="switchTab(this,'tabs-post-<?= h($slug) ?>','post-<?= h($slug) ?>-full')">Payload completo</div>
            <?php if (!empty($reqFields)): ?>
            <div class="tab" onclick="switchTab(this,'tabs-post-<?= h($slug) ?>','post-<?= h($slug) ?>-req')">Apenas obrigatórios</div>
            <?php endif; ?>
            <div class="tab" onclick="switchTab(this,'tabs-post-<?= h($slug) ?>','post-<?= h($slug) ?>-try')">▶ Testar</div>
          </div>
          <div id="tabs-post-<?= h($slug) ?>">
            <div class="tab-pane active" id="post-<?= h($slug) ?>-full">
              <?php
              $rInsert = getResp($ent, 'insert', 201, 'Registro criado com sucesso.');
              $rInsertVal = getResp($ent, 'insert_validation', 422, 'Dados inválidos.');
              ?>
              <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:14px">
                <div style="background:var(--sf);border:1px solid var(--bd);border-radius:var(--r2);padding:10px 12px">
                  <div style="font-size:.7rem;color:var(--mt);margin-bottom:4px">Sucesso</div>
                  <div style="font-family:var(--fd);font-weight:800;font-size:1rem;color:<?= codeColor($rInsert['code']) ?>"><?= $rInsert['code'] ?></div>
                  <div style="font-size:.72rem;color:var(--mt2);margin-top:2px"><?= h($rInsert['message']) ?></div>
                </div>
                <div style="background:var(--sf);border:1px solid var(--bd);border-radius:var(--r2);padding:10px 12px">
                  <div style="font-size:.7rem;color:var(--mt);margin-bottom:4px">Validação falhou</div>
                  <div style="font-family:var(--fd);font-weight:800;font-size:1rem;color:<?= codeColor($rInsertVal['code']) ?>"><?= $rInsertVal['code'] ?></div>
                  <div style="font-size:.72rem;color:var(--mt2);margin-top:2px"><?= h($rInsertVal['message']) ?></div>
                </div>
              </div>
              <p style="font-size:.82rem;color:var(--mt2);margin-bottom:8px">Retorna <code><?= $rInsert['code'] ?></code> com o registro criado.</p>
              <div class="code-block"><button class="copy-btn" onclick="copyCode(this)">📋</button><?= h($fullPayload) ?></div>
            </div>
            <?php if (!empty($reqFields)): ?>
            <div class="tab-pane" id="post-<?= h($slug) ?>-req">
              <p style="font-size:.82rem;color:var(--mt2);margin-bottom:8px">Mínimo necessário para criar um registro:</p>
              <div class="code-block"><button class="copy-btn" onclick="copyCode(this)">📋</button><?= h($reqPayload) ?></div>
            </div>
            <?php endif; ?>
            <div class="tab-pane" id="post-<?= h($slug) ?>-try">
              <div class="try-section">
                <div class="try-row">
                  <span class="try-label">Chave API</span>
                  <select class="try-select" id="key-post-<?= h($slug) ?>">
                    <option value="">— selecione —</option>
                    <?php foreach ($apiKeys as $k): ?>
                    <option value="<?= h($k['key_preview']) ?>">🔑 <?= h($k['name']) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="try-row" style="align-items:flex-start">
                  <span class="try-label" style="padding-top:8px">Body (JSON)</span>
                  <textarea class="try-input" id="body-post-<?= h($slug) ?>" rows="6" style="font-family:monospace;resize:vertical"><?= h($fullPayload) ?></textarea>
                </div>
                <button class="try-btn" onclick="tryPost('<?= h($url) ?>','key-post-<?= h($slug) ?>','body-post-<?= h($slug) ?>','resp-post-<?= h($slug) ?>')">▶ Executar</button>
                <div class="response-box" id="resp-post-<?= h($slug) ?>"></div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- PUT -->
      <div class="method-block">
        <div class="method-head" onclick="toggleMethod(this)">
          <span class="method-badge PUT">PUT</span>
          <span class="method-path">/api/v1/<?= h($slug) ?>/{id}</span>
          <span class="method-desc">Atualizar registro</span>
          <span style="margin-left:auto;color:var(--mt);font-size:.8rem">▼</span>
        </div>
        <div class="method-body">
          <p style="font-size:.82rem;color:var(--mt2);margin-bottom:8px">Retorna <code>200 OK</code> com o registro atualizado. Envie apenas os campos que deseja alterar.</p>
          <div class="code-block"><button class="copy-btn" onclick="copyCode(this)">📋</button>PUT <?= h($url) ?>/1
Authorization: Bearer fc_sua_chave
Content-Type: application/json

<?= h(buildPayload(array_slice($fields,0,2), false)) ?></div>
        </div>
      </div>

      <!-- DELETE -->
      <div class="method-block">
        <div class="method-head" onclick="toggleMethod(this)">
          <span class="method-badge DELETE">DELETE</span>
          <span class="method-path">/api/v1/<?= h($slug) ?>/{id}</span>
          <span class="method-desc">Excluir registro</span>
          <span style="margin-left:auto;color:var(--mt);font-size:.8rem">▼</span>
        </div>
        <div class="method-body">
          <p style="font-size:.82rem;color:var(--mt2);margin-bottom:8px">Retorna <code>204 No Content</code>. Operação irreversível.</p>
          <div class="code-block"><button class="copy-btn" onclick="copyCode(this)">📋</button>DELETE <?= h($url) ?>/1
Authorization: Bearer fc_sua_chave</div>
        </div>
      </div>

    </div><!-- /entity section -->
    <?php endforeach; ?>

    <?php if (empty($entities)): ?>
    <div class="doc-section" id="no-entities">
      <div style="text-align:center;padding:48px">
        <div style="font-size:2.5rem;margin-bottom:14px">📋</div>
        <div style="font-weight:700;margin-bottom:8px">Nenhuma entidade criada ainda</div>
        <div style="color:var(--mt);margin-bottom:20px;font-size:.875rem">Crie entidades e campos no painel para gerar os endpoints automaticamente.</div>
        <a href="<?= url('/entities/new') ?>" class="btn btn-primary" style="display:inline-flex">+ Criar primeira entidade</a>
      </div>
    </div>
    <?php endif; ?>

  </div><!-- /doc-content -->
</div><!-- /doc-layout -->

<script>
// Toggle method body
function toggleMethod(head) {
  const body = head.nextElementSibling;
  const arrow = head.querySelector('span:last-child');
  body.classList.toggle('open');
  arrow.textContent = body.classList.contains('open') ? '▲' : '▼';
}

// Tab switching
function switchTab(el, groupId, targetId) {
  const group = document.getElementById(groupId);
  group.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
  el.closest('.method-body, .doc-section').querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
  document.getElementById(targetId).classList.add('active');
  el.classList.add('active');
}

// Sidebar scroll spy
function scrollTo(id, link) {
  document.getElementById(id)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
  document.querySelectorAll('.doc-nav a').forEach(a => a.classList.remove('active'));
  link.classList.add('active');
}

// Copy code block
function copyCode(btn) {
  const code = btn.parentElement.textContent.replace('📋','').trim();
  navigator.clipboard.writeText(code).then(() => {
    btn.textContent = '✅'; setTimeout(() => btn.textContent = '📋', 1500);
  });
}

// Format JSON response
function formatResp(box, status, data) {
  box.style.display = 'block';
  const color = status >= 200 && status < 300 ? '#86efac' : '#fca5a5';
  box.innerHTML = `<span style="color:${color};font-weight:700">HTTP ${status}</span>\n\n` +
    JSON.stringify(data, null, 2).replace(/("[^"]*"):/g, '<span style="color:var(--ac)">$1</span>:');
}

// GET list
async function tryRequest(method, baseUrl, keyId, qsId, bodyId, respId) {
  const key = document.getElementById(keyId).value;
  const qs  = document.getElementById(qsId)?.value || '';
  const box = document.getElementById(respId);
  if (!key) { box.style.display='block'; box.textContent='⚠️ Selecione uma chave de API.'; return; }
  box.style.display='block'; box.textContent='Executando...';
  try {
    const r = await fetch(baseUrl + (qs ? (qs.startsWith('?')?qs:'?'+qs) : ''), {
      method, headers: { 'Authorization': 'Bearer ' + key, 'Accept': 'application/json' }
    });
    formatResp(box, r.status, await r.json());
  } catch(e) { box.textContent = '❌ Erro: ' + e.message; }
}

// GET one by ID
async function tryRequestId(method, baseUrl, keyId, idElId, respId) {
  const key = document.getElementById(keyId).value;
  const id  = document.getElementById(idElId).value || '1';
  const box = document.getElementById(respId);
  if (!key) { box.style.display='block'; box.textContent='⚠️ Selecione uma chave de API.'; return; }
  box.style.display='block'; box.textContent='Executando...';
  try {
    const r = await fetch(baseUrl + '/' + id, {
      method, headers: { 'Authorization': 'Bearer ' + key, 'Accept': 'application/json' }
    });
    formatResp(box, r.status, await r.json());
  } catch(e) { box.textContent = '❌ Erro: ' + e.message; }
}

// POST
async function tryPost(baseUrl, keyId, bodyId, respId) {
  const key  = document.getElementById(keyId).value;
  const body = document.getElementById(bodyId).value;
  const box  = document.getElementById(respId);
  if (!key) { box.style.display='block'; box.textContent='⚠️ Selecione uma chave de API.'; return; }
  try { JSON.parse(body); } catch(e) { box.style.display='block'; box.textContent='❌ JSON inválido: '+e.message; return; }
  box.style.display='block'; box.textContent='Executando...';
  try {
    const r = await fetch(baseUrl, {
      method: 'POST',
      headers: { 'Authorization': 'Bearer '+key, 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body
    });
    formatResp(box, r.status, await r.json());
  } catch(e) { box.textContent = '❌ Erro: ' + e.message; }
}

// Scroll spy
window.addEventListener('scroll', () => {
  document.querySelectorAll('.doc-section').forEach(sec => {
    const rect = sec.getBoundingClientRect();
    if (rect.top <= 80 && rect.bottom > 80) {
      const id = sec.id;
      document.querySelectorAll('.doc-nav a').forEach(a => {
        a.classList.toggle('active', a.getAttribute('href') === '#' + id);
      });
    }
  });
}, { passive: true });
</script>

<?php partial('layout/footer'); ?>
