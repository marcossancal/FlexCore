<?php
$page_title  = 'Como criar Plugins';
$active_page = 'plugins';
$breadcrumbs = [
    ['label' => 'Plugins', 'url' => admin_url('/plugins')],
    ['label' => 'Como criar'],
];
partial('layout/header');
?>

<style>
.doc-wrap    { max-width:860px; }
.doc-h1      { font-family:var(--fd);font-size:1.5rem;font-weight:800;margin-bottom:8px; }
.doc-h2      { font-family:var(--fd);font-size:1.05rem;font-weight:700;margin:36px 0 12px;padding-top:24px;border-top:1px solid var(--bd); }
.doc-h3      { font-family:var(--fd);font-size:.9rem;font-weight:700;margin:22px 0 10px;color:var(--ac); }
.doc-p       { color:var(--mt2);font-size:.875rem;line-height:1.8;margin-bottom:12px; }
.doc-ul      { color:var(--mt2);font-size:.875rem;line-height:1.9;margin:0 0 12px 20px; }
pre          { background:var(--bg2);border:1px solid var(--bd);border-radius:var(--r2);padding:16px;font-size:.78rem;line-height:1.7;overflow-x:auto;margin:10px 0;position:relative; }
pre .copy    { position:absolute;top:8px;right:8px;background:var(--sf2);border:1px solid var(--bd);color:var(--mt);border-radius:4px;padding:3px 8px;font-size:.7rem;cursor:pointer;transition:all .15s;font-family:var(--fb); }
pre .copy:hover { color:var(--tx); }
code         { background:var(--sf2);padding:1px 6px;border-radius:4px;font-size:.82rem;color:var(--ac); }
.hook-table  { width:100%;border-collapse:collapse;font-size:.8rem;margin:12px 0; }
.hook-table th { text-align:left;color:var(--mt);font-size:.68rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;padding:0 10px 8px;border-bottom:1px solid var(--bd); }
.hook-table td { padding:9px 10px;border-bottom:1px solid var(--bd);vertical-align:top; }
.hook-table tr:last-child td { border:none; }
.hook-name   { font-family:monospace;color:var(--ac);font-size:.8rem; }
.hook-params { font-family:monospace;font-size:.75rem;color:var(--mt2); }
.badge-action { background:rgba(0,212,255,.12);color:var(--ac);font-size:.68rem;font-weight:700;padding:2px 7px;border-radius:4px; }
.badge-filter { background:rgba(108,92,231,.15);color:#a78bfa;font-size:.68rem;font-weight:700;padding:2px 7px;border-radius:4px; }
.tip-box     { background:rgba(0,212,255,.06);border:1px solid rgba(0,212,255,.2);border-radius:var(--r2);padding:14px 16px;font-size:.83rem;color:var(--mt2);line-height:1.7;margin:14px 0; }
.warn-box    { background:rgba(245,158,11,.06);border:1px solid rgba(245,158,11,.2);border-radius:var(--r2);padding:14px 16px;font-size:.83rem;color:var(--mt2);line-height:1.7;margin:14px 0; }
.checklist   { list-style:none;margin:0;padding:0; }
.checklist li { display:flex;gap:10px;align-items:flex-start;padding:6px 0;font-size:.83rem;color:var(--mt2);border-bottom:1px solid var(--bd); }
.checklist li:last-child { border:none; }
.checklist li::before { content:'☐';color:var(--mt);flex-shrink:0;margin-top:1px; }
.nav-tabs    { display:flex;gap:4px;flex-wrap:wrap;margin-bottom:24px; }
.nav-tab     { padding:6px 14px;border-radius:20px;font-size:.78rem;font-weight:600;color:var(--mt);background:var(--sf2);border:1px solid var(--bd);cursor:pointer;text-decoration:none;transition:all .15s; }
.nav-tab:hover { color:var(--tx);border-color:var(--bd2); }
</style>

<div class="doc-wrap">

<div class="sec-head" style="margin-bottom:24px">
  <div>
    <div class="doc-h1">🧩 Como criar Plugins</div>
    <div class="sec-sub">Guia completo para estender o FlexCore sem modificar o core</div>
  </div>
  <div class="sec-actions">
    <a href="<?= admin_url('/plugins') ?>" class="btn btn-ghost btn-sm">← Plugins</a>
  </div>
</div>

<div class="nav-tabs">
  <a class="nav-tab" href="#estrutura">Estrutura</a>
  <a class="nav-tab" href="#manifesto">plugin.json</a>
  <a class="nav-tab" href="#codigo">Plugin.php</a>
  <a class="nav-tab" href="#hooks">Hooks</a>
  <a class="nav-tab" href="#banco">Banco de dados</a>
  <a class="nav-tab" href="#settings">Configurações</a>
  <a class="nav-tab" href="#exemplos">Exemplos</a>
  <a class="nav-tab" href="#checklist">Checklist</a>
</div>

<!-- ESTRUTURA -->
<div id="estrutura" class="card" style="margin-bottom:16px">
  <div class="doc-h2" style="margin-top:0;padding-top:0;border:none">📁 Estrutura de arquivos</div>
  <p class="doc-p">Um plugin é uma pasta dentro de <code>plugins/</code> com pelo menos dois arquivos obrigatórios:</p>
<pre><button class="copy" onclick="cp(this)">📋</button>plugins/
  meu-plugin/
    plugin.json     ← obrigatório: metadados e configurações
    Plugin.php      ← obrigatório: código PHP
    views/          ← opcional: templates
    assets/         ← opcional: JS e CSS
    README.md       ← recomendado</pre>
  <div class="tip-box">💡 O nome da pasta vira o <code>plugin_id</code>. Use apenas letras minúsculas, números e hífens: <code>meu-plugin</code>, <code>slack-notifier</code>, <code>export-csv</code>.</div>
</div>

<!-- MANIFESTO -->
<div id="manifesto" class="card" style="margin-bottom:16px">
  <div class="doc-h2" style="margin-top:0;padding-top:0;border:none">📄 plugin.json</div>
<pre><button class="copy" onclick="cp(this)">📋</button>{
  "id":          "meu-plugin",
  "name":        "Meu Plugin",
  "version":     "1.0.0",
  "description": "Descrição curta do que o plugin faz.",
  "author":      "Seu Nome",
  "url":         "https://github.com/...",
  "requires":    "0.1.0",
  "hooks": ["record.created", "record.updated"],
  "settings": [
    { "key": "webhook_url", "type": "url",      "label": "URL",         "required": true  },
    { "key": "secret",      "type": "text",     "label": "Secret",      "required": false },
    { "key": "modo",        "type": "select",   "label": "Modo",
      "options": ["producao", "sandbox"],                                "required": false },
    { "key": "notas",       "type": "textarea", "label": "Notas",       "required": false }
  ]
}</pre>

  <div class="doc-h3">Tipos de campo em settings[]</div>
  <table class="hook-table">
    <thead><tr><th>type</th><th>Renderiza</th></tr></thead>
    <tbody>
      <tr><td><code>text</code></td><td>Input de texto livre</td></tr>
      <tr><td><code>url</code></td><td>Input validado como URL</td></tr>
      <tr><td><code>email</code></td><td>Input validado como e-mail</td></tr>
      <tr><td><code>password</code></td><td>Input com texto oculto</td></tr>
      <tr><td><code>number</code></td><td>Input numérico</td></tr>
      <tr><td><code>textarea</code></td><td>Área de texto multilinha</td></tr>
      <tr><td><code>select</code></td><td>Dropdown com as <code>options</code> declaradas</td></tr>
    </tbody>
  </table>
</div>

<!-- CÓDIGO -->
<div id="codigo" class="card" style="margin-bottom:16px">
  <div class="doc-h2" style="margin-top:0;padding-top:0;border:none">⚙️ Plugin.php</div>
<pre><button class="copy" onclick="cp(this)">📋</button>&lt;?php

namespace MeuPlugin; // PascalCase do nome da pasta

class Plugin
{
    public function manifest(): array
    {
        return json_decode(
            file_get_contents(__DIR__ . '/plugin.json'), true
        );
    }

    public function boot(): void
    {
        // Called once at initialization.
        // Register your hooks here.

        Hooks::on('record.created', function (int $recordId, int $entityId, array $input) {
            // Your logic
        });
    }

    public function uninstall(): void
    {
        // Called at "Remove" button at panel.
        // Clean tables and data created by plugin.
        // DB::execute('DROP TABLE IF EXISTS meu_plugin_logs');
    }

    private function settings(): array
    {
        $row = DB::one("SELECT settings FROM plugins WHERE plugin_id = 'meu-plugin'");
        return json_decode($row['settings'] ?? '{}', true) ?: [];
    }
}</pre>

  <div class="doc-h3">Conversão de nome de pasta → namespace</div>
  <table class="hook-table">
    <thead><tr><th>Pasta (plugin_id)</th><th>Namespace (Plugin.php)</th></tr></thead>
    <tbody>
      <tr><td><code>meu-plugin</code></td><td><code>MeuPlugin</code></td></tr>
      <tr><td><code>slack-notifier</code></td><td><code>SlackNotifier</code></td></tr>
      <tr><td><code>export-csv</code></td><td><code>ExportCsv</code></td></tr>
      <tr><td><code>webhook-sender</code></td><td><code>WebhookSender</code></td></tr>
    </tbody>
  </table>
</div>

<!-- HOOKS -->
<div id="hooks" class="card" style="margin-bottom:16px">
  <div class="doc-h2" style="margin-top:0;padding-top:0;border:none">🪝 Sistema de Hooks</div>

  <p class="doc-p">O FlexCore usa dois tipos de hooks: <strong>Actions</strong> (fire and forget) e <strong>Filters</strong> (transformam um valor).</p>

<pre><button class="copy" onclick="cp(this)">📋</button>// ACTION — registrar listener
Hooks::on('record.created', function (int $recordId, int $entityId, array $input) {
    // chamado toda vez que um registro for criado
});

// ACTION — com prioridade (menor = executa primeiro, padrão = 10)
Hooks::on('record.created', function (...) { ... }, priority: 5);

// FILTER — deve sempre retornar o valor (possivelmente modificado)
Hooks::filter('api.response', function (array $response, array $entity): array {
    $response['data']['_version'] = '1.0';
    return $response; // obrigatório
});</pre>

  <div class="doc-h3">Actions de registros</div>
  <table class="hook-table">
    <thead><tr><th>Hook</th><th>Tipo</th><th>Quando</th><th>Parâmetros</th></tr></thead>
    <tbody>
      <tr><td class="hook-name">record.before_create</td><td><span class="badge-action">action</span></td><td>Antes de criar</td><td class="hook-params">(int $entityId, array $input)</td></tr>
      <tr><td class="hook-name">record.created</td><td><span class="badge-action">action</span></td><td>Após criar</td><td class="hook-params">(int $recordId, int $entityId, array $input)</td></tr>
      <tr><td class="hook-name">record.before_update</td><td><span class="badge-action">action</span></td><td>Antes de atualizar</td><td class="hook-params">(int $recordId, int $entityId, array $input)</td></tr>
      <tr><td class="hook-name">record.updated</td><td><span class="badge-action">action</span></td><td>Após atualizar</td><td class="hook-params">(int $recordId, int $entityId, array $input)</td></tr>
      <tr><td class="hook-name">record.before_delete</td><td><span class="badge-action">action</span></td><td>Antes de excluir</td><td class="hook-params">(int $recordId, int $entityId)</td></tr>
      <tr><td class="hook-name">record.deleted</td><td><span class="badge-action">action</span></td><td>Após excluir</td><td class="hook-params">(int $recordId, int $entityId)</td></tr>
    </tbody>
  </table>

  <div class="doc-h3">Actions de entidades</div>
  <table class="hook-table">
    <thead><tr><th>Hook</th><th>Tipo</th><th>Quando</th><th>Parâmetros</th></tr></thead>
    <tbody>
      <tr><td class="hook-name">entity.created</td><td><span class="badge-action">action</span></td><td>Após criar entidade</td><td class="hook-params">(int $entityId)</td></tr>
      <tr><td class="hook-name">entity.updated</td><td><span class="badge-action">action</span></td><td>Após atualizar entidade</td><td class="hook-params">(int $entityId)</td></tr>
      <tr><td class="hook-name">entity.deleted</td><td><span class="badge-action">action</span></td><td>Após excluir entidade</td><td class="hook-params">(int $entityId)</td></tr>
      <tr><td class="hook-name">plugin.loaded</td><td><span class="badge-action">action</span></td><td>Após plugin inicializar</td><td class="hook-params">(array $manifest)</td></tr>
    </tbody>
  </table>

  <div class="doc-h3">Filters da API</div>
  <table class="hook-table">
    <thead><tr><th>Hook</th><th>Tipo</th><th>Quando</th><th>Parâmetros</th><th>Retorna</th></tr></thead>
    <tbody>
      <tr><td class="hook-name">api.response</td><td><span class="badge-filter">filter</span></td><td>Antes de enviar resposta</td><td class="hook-params">(array $response, array $entity)</td><td class="hook-params">array</td></tr>
      <tr><td class="hook-name">api.record</td><td><span class="badge-filter">filter</span></td><td>Antes de retornar 1 registro</td><td class="hook-params">(array $record, array $entity)</td><td class="hook-params">array</td></tr>
      <tr><td class="hook-name">api.list</td><td><span class="badge-filter">filter</span></td><td>Antes de retornar lista</td><td class="hook-params">(array $records, array $entity)</td><td class="hook-params">array</td></tr>
      <tr><td class="hook-name">field.render</td><td><span class="badge-filter">filter</span></td><td>Antes de renderizar campo na UI</td><td class="hook-params">(string $html, array $field, mixed $val)</td><td class="hook-params">string</td></tr>
    </tbody>
  </table>
</div>

<!-- BANCO -->
<div id="banco" class="card" style="margin-bottom:16px">
  <div class="doc-h2" style="margin-top:0;padding-top:0;border:none">🗄️ Banco de dados</div>
  <p class="doc-p">Use a classe <code>DB</code> diretamente — já disponível globalmente:</p>
<pre><button class="copy" onclick="cp(this)">📋</button>// Buscar um registro
$row = DB::one('SELECT * FROM entity_records WHERE id = ?', [$recordId]);

// Buscar vários
$rows = DB::query('SELECT * FROM entity_records WHERE entity_id = ?', [$entityId]);

// Inserir (retorna o novo ID)
$id = DB::insert('INSERT INTO minha_tabela (campo) VALUES (?)', ['valor']);

// Atualizar / deletar (retorna rows affected)
$n = DB::execute('UPDATE minha_tabela SET campo = ? WHERE id = ?', ['novo', $id]);

// Transação (rollback automático em exceção)
DB::transaction(function () use ($data) {
    DB::execute('UPDATE ...');
    DB::insert('INSERT ...');
});

// Criar tabela própria no boot()
DB::execute("
    CREATE TABLE IF NOT EXISTS meu_plugin_logs (
        id         INT AUTO_INCREMENT PRIMARY KEY,
        record_id  INT NOT NULL,
        evento     VARCHAR(50),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");</pre>
</div>

<!-- SETTINGS -->
<div id="settings" class="card" style="margin-bottom:16px">
  <div class="doc-h2" style="margin-top:0;padding-top:0;border:none">🔧 Lendo configurações</div>
  <p class="doc-p">As configurações salvas pelo usuário ficam na coluna <code>settings</code> da tabela <code>plugins</code> como JSON:</p>
<pre><button class="copy" onclick="cp(this)">📋</button">private function settings(): array
{
    $row = DB::one("SELECT settings FROM plugins WHERE plugin_id = 'meu-plugin'");
    return json_decode($row['settings'] ?? '{}', true) ?: [];
}

public function boot(): void
{
    Hooks::on('record.created', function (int $recordId, int $entityId, array $input) {
        $cfg = $this->settings();
        $url = $cfg['webhook_url'] ?? '';
        if (!$url) return; // não configurado ainda

        $payload = json_encode(['record_id' => $recordId, 'data' => $input]);
        $ctx = stream_context_create([
            'http' => [
                'method'  => 'POST',
                'header'  => "Content-Type: application/json\r\n",
                'content' => $payload,
                'timeout' => 10,
            ],
        ]);
        @file_get_contents($url, false, $ctx);
    });
}</pre>
</div>

<!-- EXEMPLOS -->
<div id="exemplos" class="card" style="margin-bottom:16px">
  <div class="doc-h2" style="margin-top:0;padding-top:0;border:none">💡 Exemplos prontos</div>

  <div class="doc-h3">1. Webhook com HMAC signature</div>
<pre><button class="copy" onclick="cp(this)">📋</button>Hooks::on('record.created', function (int $recordId, int $entityId, array $input) {
    $cfg     = $this->settings();
    $url     = $cfg['url']    ?? '';
    $secret  = $cfg['secret'] ?? '';
    if (!$url) return;

    $payload   = json_encode(['record_id' => $recordId, 'entity_id' => $entityId, 'data' => $input, 'fired_at' => date('c')]);
    $signature = hash_hmac('sha256', $payload, $secret);

    $ctx = stream_context_create(['http' => [
        'method'  => 'POST',
        'header'  => "Content-Type: application/json\r\nX-Signature: {$signature}\r\n",
        'content' => $payload,
        'timeout' => 10,
    ]]);
    @file_get_contents($url, false, $ctx);
});</pre>

  <div class="doc-h3">2. Campo calculado na API (filter)</div>
<pre><button class="copy" onclick="cp(this)">📋</button>Hooks::filter('api.record', function (array $record, array $entity): array {
    if ($entity['slug'] !== 'pedidos') return $record;

    $record['total_com_imposto'] = round(
        ((float)($record['valor'] ?? 0) + (float)($record['frete'] ?? 0)) * 1.12, 2
    );
    return $record;
});</pre>

  <div class="doc-h3">3. Logger de auditoria com tabela própria</div>
<pre><button class="copy" onclick="cp(this)">📋</button">public function boot(): void
{
    DB::execute("CREATE TABLE IF NOT EXISTS meu_plugin_audit (
        id INT AUTO_INCREMENT PRIMARY KEY,
        evento VARCHAR(50), record_id INT, entity_id INT,
        user_id INT, ip VARCHAR(45),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $log = fn($ev, $rid, $eid) => DB::insert(
        'INSERT INTO meu_plugin_audit (evento,record_id,entity_id,user_id,ip) VALUES (?,?,?,?,?)',
        [$ev, $rid, $eid, $_SESSION['user_id'] ?? null, $_SERVER['REMOTE_ADDR'] ?? null]
    );

    Hooks::on('record.created', fn($rid, $eid, $i) => $log('created', $rid, $eid));
    Hooks::on('record.updated', fn($rid, $eid, $i) => $log('updated', $rid, $eid));
    Hooks::on('record.deleted', fn($rid, $eid)     => $log('deleted', $rid, $eid));
}

public function uninstall(): void
{
    DB::execute('DROP TABLE IF EXISTS meu_plugin_audit');
}</pre>
</div>

<!-- CHECKLIST -->
<div id="checklist" class="card" style="margin-bottom:16px">
  <div class="doc-h2" style="margin-top:0;padding-top:0;border:none">✅ Checklist antes de publicar</div>
  <ul class="checklist">
    <li><code>plugin.json</code> tem todos os campos obrigatórios (id, name, version, requires)</li>
    <li>O <code>id</code> no <code>plugin.json</code> é igual ao nome da pasta</li>
    <li>O namespace em <code>Plugin.php</code> é o PascalCase do id</li>
    <li><code>boot()</code> verifica se as configurações existem antes de agir (evita erro em plugin não configurado)</li>
    <li><code>uninstall()</code> limpa todas as tabelas e dados criados pelo plugin</li>
    <li>Testado com o plugin ativo e inativo</li>
    <li>Testado install e uninstall sem deixar resíduos no banco</li>
    <li>O ZIP tem os arquivos na raiz (não dentro de uma subpasta)</li>
  </ul>

  <div class="doc-h3">Empacotando o ZIP corretamente</div>
<pre><button class="copy" onclick="cp(this)">📋</button"># Na pasta do plugin
zip -r meu-plugin-1.0.0.zip . --exclude "*.git*" --exclude ".DS_Store"

# Estrutura correta dentro do ZIP:
# plugin.json   ← na raiz
# Plugin.php    ← na raiz
# README.md

# ERRADO — não coloque dentro de subpasta:
# meu-plugin/plugin.json
# meu-plugin/Plugin.php</pre>
</div>

</div><!-- /doc-wrap -->

<script>
function cp(btn) {
  const pre  = btn.parentElement;
  const text = pre.textContent.replace('📋','').trim();
  navigator.clipboard.writeText(text).then(() => {
    btn.textContent = '✅'; setTimeout(() => btn.textContent = '📋', 1500);
  });
}
// Smooth scroll para anchors
document.querySelectorAll('.nav-tab').forEach(a => {
  a.addEventListener('click', e => {
    e.preventDefault();
    const target = document.querySelector(a.getAttribute('href'));
    if (target) target.scrollIntoView({ behavior: 'smooth', block: 'start' });
  });
});
</script>

<?php partial('layout/footer'); ?>
