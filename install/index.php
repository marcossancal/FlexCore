<?php
define('BASE', __DIR__ . '/..');
define('VERSION', '1.0.0');

// Calculates the application base path (e.g. /flexcore)
// SCRIPT_NAME in install/index.php = /flexcore/install/index.php → dirname x2 = /flexcore
$_appBase = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/');

function installUrl(string $path = ''): string {
    global $_appBase;
    $path = ltrim($path, '/');
    return $path === '' ? $_appBase . '/' : $_appBase . '/' . $path;
}

// Already installed → redirect to app root
if (file_exists(BASE . '/.installed') && file_exists(BASE . '/.env')) {
    header('Location: ' . installUrl()); exit;
}

// Stale lock (no .env) → remove so user can reinstall cleanly
if (file_exists(BASE . '/.installed') && !file_exists(BASE . '/.env')) {
    @unlink(BASE . '/.installed');
}

$step   = isset($_POST['_step']) ? (int)$_POST['_step'] : (int)($_GET['step'] ?? 1);
$errors = [];

function testDb(string $host, string $port, string $db, string $user, string $pass): ?PDO {
    try {
        return new PDO("mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Sanitises the admin path segment entered by the user.
 * Only [a-z0-9-_] allowed. Falls back to 'painel' if empty.
 */
function sanitiseAdminPath(string $raw): string {
    $clean = preg_replace('/[^a-z0-9\-_]/', '', strtolower(trim($raw)));
    return $clean !== '' ? $clean : 'painel';
}

/**
 * Splits SQL into individual statements, respecting strings and comments.
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

        if (!$inStr && $ch === '-' && ($sql[$i+1] ?? '') === '-') {
            while ($i < $len && $sql[$i] !== "\n") $i++;
            continue;
        }
        if (!$inStr && $ch === '/' && ($sql[$i+1] ?? '') === '*') {
            $i += 2;
            while ($i < $len - 1 && !($sql[$i] === '*' && $sql[$i+1] === '/')) $i++;
            $i += 2;
            continue;
        }
        if (!$inStr && ($ch === "'" || $ch === '"' || $ch === '`')) {
            $inStr = true; $strChar = $ch;
        } elseif ($inStr && $ch === $strChar && ($sql[$i-1] ?? '') !== '\\') {
            $inStr = false;
        }
        if (!$inStr && $ch === ';') {
            $stmt = trim($current);
            if ($stmt !== '') $statements[] = $stmt . ';';
            $current = '';
            continue;
        }
        $current .= $ch;
    }
    $stmt = trim($current);
    if ($stmt !== '') $statements[] = $stmt . ';';
    return array_filter($statements, fn($s) => trim($s, " \t\n\r;") !== '');
}

// ── POST handler ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $dbHost    = trim($_POST['db_host'] ?? 'localhost');
    $dbPort    = trim($_POST['db_port'] ?? '3306');
    $dbName    = trim($_POST['db_name'] ?? '');
    $dbUser    = trim($_POST['db_user'] ?? '');
    $dbPass    = $_POST['db_pass'] ?? '';

    $adminName  = trim($_POST['admin_name']  ?? 'Administrator');
    $adminEmail = trim($_POST['admin_email'] ?? '');
    $adminPwd   = $_POST['admin_password']   ?? '';

    $appName   = trim($_POST['app_name']  ?? 'FlexCore');
    $appUrl    = rtrim(trim($_POST['app_url'] ?? ''), '/');
    $adminPath = sanitiseAdminPath($_POST['admin_path'] ?? 'painel');

    // Validation
    if (empty($dbName))
        $errors[] = 'Database name is required.';
    if (!$adminEmail || !filter_var($adminEmail, FILTER_VALIDATE_EMAIL))
        $errors[] = 'Invalid admin e-mail.';
    if (strlen($adminPwd) < 6)
        $errors[] = 'Minimum password length: 6 characters.';

    $reserved = ['api', 'install', 'assets', 'index.php'];
    if (in_array($adminPath, $reserved, true))
        $errors[] = "Admin path '{$adminPath}' is reserved. Choose another (e.g. painel, admin, backoffice).";

    $pdo = empty($errors) ? testDb($dbHost, $dbPort, $dbName, $dbUser, $dbPass) : null;
    if (!$pdo && empty($errors))
        $errors[] = 'Could not connect to the database. Please verify host, username, and password.';

    if (empty($errors)) {

        // Schema
        $sql        = file_get_contents(BASE . '/install/schema.sql');
        $statements = parseSqlStatements($sql);
        foreach ($statements as $q) {
            try { $pdo->exec($q); }
            catch (\PDOException $e) {
                if (!str_contains($e->getMessage(), 'already exists')) throw $e;
            }
        }

        // Admin user
        $pdo->prepare('INSERT IGNORE INTO users (name,email,password,role) VALUES (?,?,?,?)')
            ->execute([$adminName, $adminEmail, password_hash($adminPwd, PASSWORD_DEFAULT), 'admin']);

        // Settings (upsert)
        $upsert = $pdo->prepare("
            INSERT INTO settings (skey,sval,label,grp) VALUES (?,?,?,?)
            ON DUPLICATE KEY UPDATE sval=VALUES(sval)
        ");
        $upsert->execute(['app_name', $appName, 'System name',     'general']);
        $upsert->execute(['app_url',  $appUrl,  'Application URL', 'general']);

        // Optional columns added in later versions
        $entCols = $pdo->query("SHOW COLUMNS FROM `entities`")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('api_responses', $entCols))
            $pdo->exec("ALTER TABLE `entities` ADD COLUMN `api_responses` TEXT DEFAULT NULL AFTER `active`");

        $userCols = $pdo->query("SHOW COLUMNS FROM `users`")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('lang', $userCols))
            $pdo->exec("ALTER TABLE `users` ADD COLUMN `lang` VARCHAR(10) DEFAULT NULL AFTER `last_login`");

        $seed = $pdo->prepare("INSERT IGNORE INTO settings (skey,sval,label,grp) VALUES (?,?,?,?)");
        foreach ([
            ['app_logo',     '',        'Logo',             'general'],
            ['app_favicon',  '',        'Favicon',          'general'],
            ['theme_mode',   'dark',    'Theme mode',       'theme'],
            ['theme_preset', 'default', 'Theme preset',     'theme'],
            ['app_lang',     'pt_BR',   'Default language', 'general'],
        ] as $s) { $seed->execute($s); }

        // ── .env ─────────────────────────────────────────────────────
        // ADMIN_PATH  = single path segment (no slashes).
        // The core reads it at bootstrap and prefixes ALL admin routes.
        // The front-end (plugins) uses the app root freely.
        file_put_contents(BASE . '/.env', implode("\n", [
            "DB_HOST={$dbHost}",
            "DB_PORT={$dbPort}",
            "DB_NAME={$dbName}",
            "DB_USER={$dbUser}",
            "DB_PASS={$dbPass}",
            "APP_URL={$appUrl}",
            "ADMIN_PATH={$adminPath}",
            "DEBUG=false",
            "",
        ]));

        file_put_contents(BASE . '/.installed', date('Y-m-d H:i:s'));

        // ── .htaccess ─────────────────────────────────────────────────
        $htaccess    = BASE . '/.htaccess';
        $rewriteBase = $_appBase !== '' ? "\nRewriteBase {$_appBase}" : '';
        if (!file_exists($htaccess)) {
            file_put_contents($htaccess,
                "RewriteEngine On\n" .
                "Options -Indexes\n" .
                $rewriteBase . "\n\n" .
                "# Allow OPTIONS preflight through to PHP\n" .
                "RewriteCond %{REQUEST_METHOD} OPTIONS\n" .
                "RewriteRule ^ index.php [QSA,L]\n\n" .
                "# Serve real files directly\n" .
                "RewriteCond %{REQUEST_FILENAME} -f\n" .
                "RewriteRule ^ - [L]\n\n" .
                "# Route everything else through the front controller\n" .
                "RewriteRule ^ index.php [QSA,L]\n"
            );
        }

        header('Location: /login');
    }

    $step = 2; // re-render form with errors
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>FlexCore — Installation</title>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: system-ui, -apple-system, sans-serif;
      background: #07090e; color: #eef0f8;
      min-height: 100vh; display: flex; align-items: center;
      justify-content: center; padding: 2rem;
    }
    .card {
      width: 100%; max-width: 520px;
      background: #111622; border: 1px solid rgba(255,255,255,.08);
      border-radius: 16px; padding: 2.5rem;
    }
    .logo { text-align: center; margin-bottom: 2rem; }
    .logo h1 { font-size: 1.6rem; font-weight: 700; color: #00d4ff; }
    .logo p  { color: #68718f; font-size: .875rem; margin-top: .25rem; }
    h2 {
      font-size: 1rem; font-weight: 600; margin: 1.5rem 0 1rem;
      color: #eef0f8; border-bottom: 1px solid rgba(255,255,255,.06);
      padding-bottom: .5rem;
    }
    h2:first-of-type { margin-top: 0; }
    label { display: block; font-size: .8rem; color: #8892a4;
            margin-bottom: .35rem; font-weight: 500; }
    input[type=text], input[type=email], input[type=password],
    input[type=number], input[type=url] {
      width: 100%; padding: .55rem .75rem;
      background: #1e2640; border: 1px solid rgba(255,255,255,.1);
      border-radius: 8px; color: #eef0f8; font-size: .875rem;
      outline: none; transition: border-color .2s;
    }
    input:focus { border-color: #00d4ff; }
    .row  { display: grid; grid-template-columns: 1fr 1fr; gap: .75rem; }
    .field { margin-bottom: .9rem; }
    .hint { font-size: .73rem; color: #68718f; margin-top: .35rem; line-height: 1.5; }
    .hint code {
      background: #1e2640; padding: 1px 5px; border-radius: 4px;
      font-size: .7rem; color: #00d4ff;
    }
    .btn {
      display: block; width: 100%; padding: .7rem 1rem; margin-top: 1.5rem;
      background: #00d4ff; color: #07090e; border: none; border-radius: 8px;
      font-size: .9rem; font-weight: 700; cursor: pointer; transition: opacity .2s;
    }
    .btn:hover { opacity: .85; }
    .errors {
      background: rgba(239,68,68,.1); border: 1px solid rgba(239,68,68,.3);
      border-radius: 8px; padding: .75rem 1rem; margin-bottom: 1.25rem;
    }
    .errors p { color: #fca5a5; font-size: .83rem; margin: .2rem 0; }
    .success {
      background: rgba(34,197,94,.08); border: 1px solid rgba(34,197,94,.25);
      border-radius: 12px; padding: 2rem; text-align: center;
    }
    .success h2 { border: none; color: #4ade80; font-size: 1.2rem; }
    .success p  { color: #86efac; font-size: .875rem; margin-top: .75rem; }
    .success a  { color: #00d4ff; word-break: break-all; }
    .sep { height: 1px; background: rgba(255,255,255,.06); margin: 1.25rem 0; }
    .badge {
      display: inline-block; font-size: .68rem;
      background: rgba(0,212,255,.1); color: #00d4ff;
      border: 1px solid rgba(0,212,255,.2); border-radius: 4px;
      padding: 1px 6px; vertical-align: middle; margin-left: .35rem;
    }
  </style>
</head>
<body>
<div class="card">

  <div class="logo">
    <h1>⚡ FlexCore</h1>
    <p>Installation Wizard &mdash; v<?= VERSION ?></p>
  </div>

  <?php if ($step === 3): ?>

    <?php
      // Read the admin path that was just written to .env
      $_envLines        = file_exists(BASE . '/.env') ? file(BASE . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];
      $_installedAdmin  = 'painel';
      foreach ($_envLines as $_line) {
          if (strpos($_line, 'ADMIN_PATH=') === 0)
              $_installedAdmin = trim(substr($_line, strlen('ADMIN_PATH=')));
      }
      $_loginUrl = installUrl($_installedAdmin . '/login');
    ?>
    <div class="success">
      <h2>✅ Installation complete!</h2>
      <p>Your admin panel is ready at:</p>
      <p style="margin-top:.5rem"><a href="<?= htmlspecialchars($_loginUrl) ?>"><?= htmlspecialchars($_loginUrl) ?></a></p>
      <p style="margin-top:1.25rem;font-size:.78rem;color:#68718f">
        The front-end lives at the app root.<br>
        The installer is now locked &mdash; delete <code>.installed</code> to reinstall.
      </p>
    </div>

  <?php else: ?>

    <?php if (!empty($errors)): ?>
    <div class="errors">
      <?php foreach ($errors as $e): ?><p>⚠ <?= htmlspecialchars($e) ?></p><?php endforeach; ?>
    </div>
    <?php endif; ?>

    <form method="POST" action="<?= installUrl('install/') ?>">
      <input type="hidden" name="_step" value="2">

      <h2>🗄 Database</h2>

      <div class="row">
        <div class="field">
          <label>Host</label>
          <input type="text" name="db_host"
                 value="<?= htmlspecialchars($_POST['db_host'] ?? 'localhost') ?>" required>
        </div>
        <div class="field">
          <label>Port</label>
          <input type="number" name="db_port"
                 value="<?= htmlspecialchars($_POST['db_port'] ?? '3306') ?>" required>
        </div>
      </div>

      <div class="field">
        <label>Database name</label>
        <input type="text" name="db_name"
               value="<?= htmlspecialchars($_POST['db_name'] ?? '') ?>" required>
      </div>

      <div class="row">
        <div class="field">
          <label>User</label>
          <input type="text" name="db_user"
                 value="<?= htmlspecialchars($_POST['db_user'] ?? '') ?>" required>
        </div>
        <div class="field">
          <label>Password</label>
          <input type="password" name="db_pass"
                 value="<?= htmlspecialchars($_POST['db_pass'] ?? '') ?>">
        </div>
      </div>

      <div class="sep"></div>
      <h2>👤 Admin account</h2>

      <div class="field">
        <label>Name</label>
        <input type="text" name="admin_name"
               value="<?= htmlspecialchars($_POST['admin_name'] ?? 'Administrator') ?>" required>
      </div>
      <div class="field">
        <label>E-mail</label>
        <input type="email" name="admin_email"
               value="<?= htmlspecialchars($_POST['admin_email'] ?? '') ?>" required>
      </div>
      <div class="field">
        <label>Password <span style="color:#68718f">(min. 6 characters)</span></label>
        <input type="password" name="admin_password" required>
      </div>

      <div class="sep"></div>
      <h2>⚙️ Application</h2>

      <div class="field">
        <label>Application name</label>
        <input type="text" name="app_name"
               value="<?= htmlspecialchars($_POST['app_name'] ?? 'FlexCore') ?>" required>
      </div>

      <div class="field">
        <label>Application URL <span style="color:#68718f">(no trailing slash)</span></label>
        <?php
          $_defaultUrl = 'http' . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 's' : '')
                       . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $_appBase;
        ?>
        <input type="url" name="app_url"
               value="<?= htmlspecialchars($_POST['app_url'] ?? $_defaultUrl) ?>" required>
        <p class="hint">Example: <code>https://mysite.com</code> or <code>http://localhost/FlexCore</code></p>
      </div>

      <div class="field">
        <label>Admin path <span class="badge">new</span></label>
        <input type="text" name="admin_path"
               value="<?= htmlspecialchars($_POST['admin_path'] ?? 'painel') ?>"
               pattern="[a-z0-9\-_]+" required>
        <p class="hint">
          Single path segment that protects your admin panel.<br>
          Example: <code>painel</code> &rarr; <code><?= htmlspecialchars(($_POST['app_url'] ?? $_defaultUrl) . '/painel/login') ?></code><br>
          Your front-end plugins will own the app root (<code>/</code>) freely.<br>
          Reserved (cannot use): <code>api</code> &nbsp; <code>install</code> &nbsp; <code>assets</code>
        </p>
      </div>

      <button type="submit" class="btn">Install FlexCore &rarr;</button>
    </form>

  <?php endif; ?>
</div>
</body>
</html>
