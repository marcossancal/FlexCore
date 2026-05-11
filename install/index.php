<?php
define('BASE', __DIR__ . '/..');
define('VERSION', '1.0.0');

// Calcula o prefixo da aplicação (ex: /system)
// SCRIPT_NAME em install/index.php = /system/install/index.php
// dirname x2 = /system
$_appBase = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/');

function installUrl(string $path = ''): string {
    global $_appBase;
    $path = ltrim($path, '/');
    return $path === '' ? $_appBase . '/' : $_appBase . '/' . $path;
}

// Já instalado com .env → vai para o sistema
if (file_exists(BASE . '/.installed') && file_exists(BASE . '/.env')) {
    header('Location: ' . installUrl()); exit;
}
// .installed sem .env → apaga o lock para permitir reinstalar limpo
if (file_exists(BASE . '/.installed') && !file_exists(BASE . '/.env')) {
    @unlink(BASE . '/.installed');
}

// Lê o step: do POST se vier de submit, senão do GET
$step = isset($_POST['_step']) ? (int)$_POST['_step'] : (int)($_GET['step'] ?? 1);
$errors = [];
$accent = '#00d4ff';

function testDb(string $host, string $port, string $db, string $user, string $pass): ?PDO {
    try {
        return new PDO("mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
    } catch (Exception $e) { return null; }
}

/**
 * Divide SQL em statements respeitando strings e comentários.
 */
function parseSqlStatements(string $sql): array
{
    $statements = [];
    $current    = '';
    $inStr      = false;
    $strChar    = '';
    $len        = strlen($sql);

    for ($i = 0; $i < $len; $i++) {
        $ch = $sql[$i];

        // Comentário de linha
        if (!$inStr && $ch === '-' && ($sql[$i+1] ?? '') === '-') {
            while ($i < $len && $sql[$i] !== "\n") $i++;
            continue;
        }
        // Comentário de bloco
        if (!$inStr && $ch === '/' && ($sql[$i+1] ?? '') === '*') {
            $i += 2;
            while ($i < $len - 1 && !($sql[$i] === '*' && $sql[$i+1] === '/')) $i++;
            $i += 2; continue;
        }
        // Abre/fecha string
        if (!$inStr && ($ch === "'" || $ch === '"' || $ch === '`')) {
            $inStr = true; $strChar = $ch;
        } elseif ($inStr && $ch === $strChar && ($sql[$i-1] ?? '') !== '\\') {
            $inStr = false;
        }
        // Fim do statement
        if (!$inStr && $ch === ';') {
            $stmt = trim($current);
            if ($stmt !== '') $statements[] = $stmt . ';';
            $current = ''; continue;
        }
        $current .= $ch;
    }
    $stmt = trim($current);
    if ($stmt !== '') $statements[] = $stmt . ';';
    return array_filter($statements, fn($s) => trim($s, " \t\n\r;") !== '');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // — Coleta todos os campos —
    $dbHost     = trim($_POST['db_host'] ?? 'localhost');
    $dbPort     = trim($_POST['db_port'] ?? '3306');
    $dbName     = trim($_POST['db_name'] ?? '');
    $dbUser     = trim($_POST['db_user'] ?? '');
    $dbPass     = $_POST['db_pass'] ?? '';
    $adminName  = trim($_POST['admin_name'] ?? 'Administrador');
    $adminEmail = trim($_POST['admin_email'] ?? '');
    $adminPwd   = $_POST['admin_password'] ?? '';
    $appName    = trim($_POST['app_name'] ?? 'FlexCore');
    $appUrl     = rtrim(trim($_POST['app_url'] ?? ''), '/');

    // — Valida tudo antes de tocar no banco —
    if (empty($dbName))  $errors[] = 'Nome do banco obrigatório.';
    if (!$adminEmail || !filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) $errors[] = 'E-mail do admin inválido.';
    if (strlen($adminPwd) < 6) $errors[] = 'Senha mínima: 6 caracteres.';

    // — Testa conexão —
    $pdo = empty($errors) ? testDb($dbHost, $dbPort, $dbName, $dbUser, $dbPass) : null;
    if (!$pdo && empty($errors)) $errors[] = 'Não foi possível conectar ao banco. Verifique host, usuário e senha.';

    if (empty($errors)) {
        // Roda o schema SQL com parser seguro
        $sql        = file_get_contents(BASE . '/install/schema.sql');
        $statements = parseSqlStatements($sql);
        foreach ($statements as $q) {
            try { $pdo->exec($q); }
            catch (\PDOException $e) {
                if (!str_contains($e->getMessage(), 'already exists')) throw $e;
            }
        }

        // Cria o admin (INSERT IGNORE para não duplicar se já existir)
        $pdo->prepare('INSERT IGNORE INTO users (name,email,password,role) VALUES (?,?,?,?)')
            ->execute([$adminName, $adminEmail, password_hash($adminPwd, PASSWORD_DEFAULT), 'admin']);

        // Salva settings no banco (upsert)
        $pdo->prepare("INSERT INTO settings (skey,sval,label,grp) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE sval=VALUES(sval)")
            ->execute(['app_name', $appName, 'Nome do sistema', 'geral']);
        $pdo->prepare("INSERT INTO settings (skey,sval,label,grp) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE sval=VALUES(sval)")
            ->execute(['app_url', $appUrl, 'URL da aplicação', 'geral']);

        // Grava .env
        $env = implode("\n", [
            "DB_HOST={$dbHost}",
            "DB_PORT={$dbPort}",
            "DB_NAME={$dbName}",
            "DB_USER={$dbUser}",
            "DB_PASS={$dbPass}",
            "APP_URL={$appUrl}",
            "DEBUG=false",
            "",
        ]);
        // Colunas adicionadas em versões posteriores — adiciona se não existirem
        $entCols = $pdo->query("SHOW COLUMNS FROM `entities`")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('api_responses', $entCols)) {
            $pdo->exec("ALTER TABLE `entities` ADD COLUMN `api_responses` TEXT DEFAULT NULL AFTER `active`");
        }

        $userCols = $pdo->query("SHOW COLUMNS FROM `users`")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('lang', $userCols)) {
            $pdo->exec("ALTER TABLE `users` ADD COLUMN `lang` VARCHAR(10) DEFAULT NULL AFTER `last_login`");
        }

        // Novas settings de tema e idioma
        $newSettings = [
            ['app_logo',      '',          'Logo',             'geral'],
            ['app_favicon',   '',          'Favicon',          'geral'],
            ['theme_mode',    'dark',      'Modo do tema',     'tema'],
            ['theme_preset',  'default',   'Preset de tema',   'tema'],
            ['app_lang',      'pt_BR',     'Idioma padrão',    'geral'],
        ];
        $stSetting = $pdo->prepare("INSERT IGNORE INTO settings (skey,sval,label,grp) VALUES (?,?,?,?)");
        foreach ($newSettings as $s) { $stSetting->execute($s); }

        file_put_contents(BASE . '/.env', $env);
        file_put_contents(BASE . '/.installed', date('Y-m-d H:i:s'));

        // Gera o .htaccess na raiz se ainda não existir
        $htaccess = BASE . '/.htaccess';
        if (!file_exists($htaccess)) {
            file_put_contents($htaccess, <<<'HTACCESS'
RewriteEngine On
Options -Indexes

# Arquivos físicos são servidos diretamente (inclui install/index.php, assets, etc)
RewriteCond %{REQUEST_FILENAME} -f
RewriteRule ^ - [L]

# Tudo mais vai para index.php
RewriteRule ^ index.php [QSA,L]
HTACCESS);
        }

        header('Location: ' . installUrl('install') . '?step=3'); exit;
    }

    $step = 2; // volta para o form com os erros
}
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Instalação — Flex System</title>
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,700&display=swap" rel="stylesheet">
  <style>
    :root { --ac: <?= $accent ?>; }
    *,*::before,*::after { box-sizing:border-box; margin:0; padding:0; }
    body { min-height:100vh; background:#07090e; font-family:'DM Sans',sans-serif; color:#eef0f8;
           display:flex; align-items:center; justify-content:center; padding:20px;
           background-image: radial-gradient(ellipse 60% 50% at 50% -10%, color-mix(in srgb, var(--ac) 10%, transparent), transparent); }
    .wrap { width:100%; max-width:520px; }
    .logo { text-align:center; margin-bottom:32px; }
    .logo-mark { font-family:'Syne',sans-serif; font-size:1.5rem; font-weight:800; color:var(--ac); display:inline-flex; align-items:center; gap:10px; }
    .logo-dot { width:10px; height:10px; border-radius:50%; background:var(--ac); }
    .steps { display:flex; align-items:center; justify-content:center; gap:0; margin-bottom:28px; }
    .step-item { display:flex; align-items:center; gap:0; }
    .step-circle { width:30px; height:30px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:.78rem; font-weight:700; border:2px solid; }
    .step-done  { border-color:var(--ac); background:var(--ac); color:#000; }
    .step-active{ border-color:var(--ac); background:transparent; color:var(--ac); }
    .step-wait  { border-color:#2a3050; background:transparent; color:#4a5568; }
    .step-line  { width:50px; height:2px; background:#1e2640; }
    .step-line.done { background:var(--ac); }
    .card { background:#111622; border:1px solid rgba(255,255,255,.06); border-radius:16px; padding:32px; }
    .card-title { font-family:'Syne',sans-serif; font-size:1.15rem; font-weight:800; margin-bottom:8px; }
    .card-sub { color:#68718f; font-size:.85rem; margin-bottom:28px; }
    .field { margin-bottom:16px; }
    .field label { display:block; font-size:.78rem; font-weight:700; color:#a0a8c0; margin-bottom:6px; }
    .field input { width:100%; background:#161c2e; border:1px solid rgba(255,255,255,.1); border-radius:8px; color:#eef0f8; padding:10px 14px; font-family:'DM Sans',sans-serif; font-size:.9rem; outline:none; transition:border-color .18s; }
    .field input:focus { border-color:var(--ac); }
    .field .hint { font-size:.72rem; color:#68718f; margin-top:5px; }
    .row2 { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
    .sep { border:none; border-top:1px solid rgba(255,255,255,.06); margin:22px 0; }
    .section-label { font-size:.72rem; font-weight:700; letter-spacing:.1em; text-transform:uppercase; color:#68718f; margin-bottom:14px; }
    .btn { width:100%; background:var(--ac); color:#000; border:none; border-radius:8px; padding:12px; font-family:'DM Sans',sans-serif; font-size:.95rem; font-weight:700; cursor:pointer; margin-top:8px; transition:filter .18s; }
    .btn:hover { filter:brightness(1.1); }
    .errors { background:rgba(239,68,68,.1); border:1px solid rgba(239,68,68,.2); color:#fca5a5; border-radius:8px; padding:12px 16px; margin-bottom:20px; font-size:.84rem; }
    .errors li { margin-left:16px; }
    .success-icon { font-size:3rem; text-align:center; margin-bottom:16px; }
    .success-title { font-family:'Syne',sans-serif; font-size:1.3rem; font-weight:800; text-align:center; margin-bottom:8px; }
    .success-sub { color:#68718f; text-align:center; font-size:.88rem; margin-bottom:28px; }
    .btn-outline { background:transparent; border:1px solid var(--ac); color:var(--ac); }
  </style>
</head>
<body>
<div class="wrap">
  <div class="logo">
    <div class="logo-mark"><div class="logo-dot"></div> Flex System</div>
    <div style="color:#68718f;font-size:.78rem;margin-top:6px">Instalação v<?= VERSION ?></div>
  </div>

  <div class="steps">
    <div class="step-item">
      <div class="step-circle <?= $step>=1?($step>1?'step-done':'step-active'):'step-wait' ?>">1</div>
    </div>
    <div class="step-line <?= $step>1?'done':'' ?>"></div>
    <div class="step-item">
      <div class="step-circle <?= $step>=2?($step>2?'step-done':'step-active'):'step-wait' ?>">2</div>
    </div>
    <div class="step-line <?= $step>2?'done':'' ?>"></div>
    <div class="step-item">
      <div class="step-circle <?= $step>=3?'step-done':'step-wait' ?>">✓</div>
    </div>
  </div>

  <?php if ($step === 1): ?>
  <div class="card">
    <div class="card-title">Bem-vindo ao Flex System</div>
    <div class="card-sub">Sistema de CRM com entidades e campos personalizados. Antes de começar, certifique-se de ter:</div>
    <ul style="color:#a0a8c0;font-size:.88rem;line-height:2;margin-left:20px;margin-bottom:24px">
      <li>PHP 8.1 ou superior</li>
      <li>Extensões: PDO, pdo_mysql, mbstring</li>
      <li>Banco de dados MySQL / MariaDB criado e vazio</li>
      <li>Permissão de escrita na raiz do projeto (para gerar .env)</li>
    </ul>
    <a href="<?= installUrl('install?step=2') ?>"><button type="button" class="btn">Começar instalação →</button></a>
  </div>

  <?php elseif ($step === 2): ?>
  <div class="card">
    <div class="card-title">Configuração</div>
    <div class="card-sub">Preencha os dados do banco e do administrador.</div>

    <?php if (!empty($errors)): ?>
    <div class="errors"><ul><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>

    <form method="POST" action="<?= installUrl('install/') ?>">
      <input type="hidden" name="_step" value="2">
      <div class="section-label">🗄️ Banco de Dados</div>
      <div class="row2">
        <div class="field"><label>Host</label><input type="text" name="db_host" value="<?= htmlspecialchars($_POST['db_host']??'localhost') ?>"></div>
        <div class="field"><label>Porta</label><input type="text" name="db_port" value="<?= htmlspecialchars($_POST['db_port']??'3306') ?>"></div>
      </div>
      <div class="field"><label>Nome do Banco *</label><input type="text" name="db_name" value="<?= htmlspecialchars($_POST['db_name']??'') ?>" required placeholder="flexcore"></div>
      <div class="row2">
        <div class="field"><label>Usuário *</label><input type="text" name="db_user" value="<?= htmlspecialchars($_POST['db_user']??'root') ?>" required></div>
        <div class="field"><label>Senha</label><input type="password" name="db_pass" value=""></div>
      </div>

      <hr class="sep">
      <div class="section-label">🏢 Sistema</div>
      <div class="row2">
        <div class="field"><label>Nome do sistema</label><input type="text" name="app_name" value="<?= htmlspecialchars($_POST['app_name']??'FlexCore') ?>"></div>
        <div class="field"><label>URL</label><input type="url" name="app_url" value="<?= htmlspecialchars($_POST['app_url']??'') ?>" placeholder="https://"></div>
      </div>

      <hr class="sep">
      <div class="section-label">👤 Administrador</div>
      <div class="field"><label>Nome *</label><input type="text" name="admin_name" value="<?= htmlspecialchars($_POST['admin_name']??'') ?>" required></div>
      <div class="field"><label>E-mail *</label><input type="email" name="admin_email" value="<?= htmlspecialchars($_POST['admin_email']??'') ?>" required></div>
      <div class="field"><label>Senha * <span style="color:#68718f;font-weight:400">(mín. 6 caracteres)</span></label><input type="password" name="admin_password" required minlength="6"></div>

      <button type="submit" class="btn">Instalar →</button>
    </form>
  </div>

  <?php elseif ($step === 3): ?>
  <div class="card">
    <div class="success-icon">🎉</div>
    <div class="success-title">Instalado com sucesso!</div>
    <div class="success-sub">O FlexCore está pronto. Faça login com o administrador cadastrado.</div>
    <div style="display:flex;flex-direction:column;gap:8px;margin:20px 0;text-align:left">
      <?php foreach([
        ['⚙️','Entidades','Crie tabelas com campos personalizados'],
        ['🔑','API & Chaves','Consuma sua API REST em /api/v1/'],
        ['⚡','Automações','Regras de negócio automáticas'],
        ['🧩','Plugins','Estenda via ZIP ou código próprio'],
      ] as [$ico,$tit,$sub]): ?>
      <div style="background:#0b0e18;border:1px solid rgba(255,255,255,.06);border-radius:8px;padding:12px 16px;display:flex;gap:12px;align-items:center">
        <span><?= $ico ?></span>
        <div><div style="font-size:.85rem;font-weight:700;margin-bottom:2px"><?= $tit ?></div>
        <div style="font-size:.78rem;color:#68718f"><?= $sub ?></div></div>
      </div>
      <?php endforeach; ?>
    </div>
    <a href="<?= installUrl() ?>"><button type="button" class="btn">Entrar no sistema →</button></a>
  </div>
  <?php endif; ?>
</div>
</body>
</html>
