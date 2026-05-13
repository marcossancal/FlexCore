<?php
define('BASE', __DIR__ . '/..');
define('VERSION', '1.0.0');

// Calculates the application base path (e.g. /system)
// SCRIPT_NAME in install/index.php = /system/install/index.php
// dirname x2 = /system
$_appBase = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/');

function installUrl(string $path = ''): string {
    global $_appBase;
    $path = ltrim($path, '/');
    return $path === '' ? $_appBase . '/' : $_appBase . '/' . $path;
}

// Already installed with .env → redirect to the system
if (file_exists(BASE . '/.installed') && file_exists(BASE . '/.env')) {
    header('Location: ' . installUrl()); exit;
}

// .installed exists without .env → remove the lock to allow a clean reinstall
if (file_exists(BASE . '/.installed') && !file_exists(BASE . '/.env')) {
    @unlink(BASE . '/.installed');
}

// Reads the step: from POST if coming from form submit, otherwise from GET
$step = isset($_POST['_step']) ? (int)$_POST['_step'] : (int)($_GET['step'] ?? 1);

$errors = [];
$accent = '#00d4ff';

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
 * Splits SQL into statements while respecting strings and comments.
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

        // Line comment
        if (!$inStr && $ch === '-' && ($sql[$i+1] ?? '') === '-') {
            while ($i < $len && $sql[$i] !== "\n") $i++;
            continue;
        }

        // Block comment
        if (!$inStr && $ch === '/' && ($sql[$i+1] ?? '') === '*') {
            $i += 2;

            while ($i < $len - 1 && !($sql[$i] === '*' && $sql[$i+1] === '/')) {
                $i++;
            }

            $i += 2;
            continue;
        }

        // Open/close string
        if (!$inStr && ($ch === "'" || $ch === '"' || $ch === '`')) {
            $inStr = true;
            $strChar = $ch;
        } elseif ($inStr && $ch === $strChar && ($sql[$i-1] ?? '') !== '\\') {
            $inStr = false;
        }

        // End of statement
        if (!$inStr && $ch === ';') {
            $stmt = trim($current);

            if ($stmt !== '') {
                $statements[] = $stmt . ';';
            }

            $current = '';
            continue;
        }

        $current .= $ch;
    }

    $stmt = trim($current);

    if ($stmt !== '') {
        $statements[] = $stmt . ';';
    }

    return array_filter($statements, fn($s) => trim($s, " \t\n\r;") !== '');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // — Collect all fields —
    $dbHost     = trim($_POST['db_host'] ?? 'localhost');
    $dbPort     = trim($_POST['db_port'] ?? '3306');
    $dbName     = trim($_POST['db_name'] ?? '');
    $dbUser     = trim($_POST['db_user'] ?? '');
    $dbPass     = $_POST['db_pass'] ?? '';

    $adminName  = trim($_POST['admin_name'] ?? 'Administrator');
    $adminEmail = trim($_POST['admin_email'] ?? '');
    $adminPwd   = $_POST['admin_password'] ?? '';

    $appName    = trim($_POST['app_name'] ?? 'FlexCore');
    $appUrl     = rtrim(trim($_POST['app_url'] ?? ''), '/');

    // — Validate everything before touching the database —
    if (empty($dbName)) {
        $errors[] = 'Database name is required.';
    }

    if (!$adminEmail || !filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid admin e-mail.';
    }

    if (strlen($adminPwd) < 6) {
        $errors[] = 'Minimum password length: 6 characters.';
    }

    // — Test connection —
    $pdo = empty($errors)
        ? testDb($dbHost, $dbPort, $dbName, $dbUser, $dbPass)
        : null;

    if (!$pdo && empty($errors)) {
        $errors[] = 'Could not connect to the database. Please verify host, username, and password.';
    }

    if (empty($errors)) {

        // Runs the SQL schema using the safe parser
        $sql        = file_get_contents(BASE . '/install/schema.sql');
        $statements = parseSqlStatements($sql);

        foreach ($statements as $q) {
            try {
                $pdo->exec($q);
            } catch (\PDOException $e) {
                if (!str_contains($e->getMessage(), 'already exists')) {
                    throw $e;
                }
            }
        }

        // Creates the admin user (INSERT IGNORE avoids duplicates)
        $pdo->prepare('INSERT IGNORE INTO users (name,email,password,role) VALUES (?,?,?,?)')
            ->execute([
                $adminName,
                $adminEmail,
                password_hash($adminPwd, PASSWORD_DEFAULT),
                'admin'
            ]);

        // Saves settings in the database (upsert)
        $pdo->prepare("
            INSERT INTO settings (skey,sval,label,grp)
            VALUES (?,?,?,?)
            ON DUPLICATE KEY UPDATE sval=VALUES(sval)
        ")->execute([
            'app_name',
            $appName,
            'System name',
            'general'
        ]);

        $pdo->prepare("
            INSERT INTO settings (skey,sval,label,grp)
            VALUES (?,?,?,?)
            ON DUPLICATE KEY UPDATE sval=VALUES(sval)
        ")->execute([
            'app_url',
            $appUrl,
            'Application URL',
            'general'
        ]);

        // Writes .env
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

        // Columns added in later versions — add if missing
        $entCols = $pdo->query("SHOW COLUMNS FROM `entities`")->fetchAll(PDO::FETCH_COLUMN);

        if (!in_array('api_responses', $entCols)) {
            $pdo->exec("
                ALTER TABLE `entities`
                ADD COLUMN `api_responses` TEXT DEFAULT NULL AFTER `active`
            ");
        }

        $userCols = $pdo->query("SHOW COLUMNS FROM `users`")->fetchAll(PDO::FETCH_COLUMN);

        if (!in_array('lang', $userCols)) {
            $pdo->exec("
                ALTER TABLE `users`
                ADD COLUMN `lang` VARCHAR(10) DEFAULT NULL AFTER `last_login`
            ");
        }

        // New theme and language settings
        $newSettings = [
            ['app_logo',      '',        'Logo',             'general'],
            ['app_favicon',   '',        'Favicon',          'general'],
            ['theme_mode',    'dark',    'Theme mode',       'theme'],
            ['theme_preset',  'default', 'Theme preset',     'theme'],
            ['app_lang',      'pt_BR',   'Default language', 'general'],
        ];

        $stSetting = $pdo->prepare("
            INSERT IGNORE INTO settings (skey,sval,label,grp)
            VALUES (?,?,?,?)
        ");

        foreach ($newSettings as $s) {
            $stSetting->execute($s);
        }

        file_put_contents(BASE . '/.env', $env);
        file_put_contents(BASE . '/.installed', date('Y-m-d H:i:s'));

        // Generates the root .htaccess file if it doesn't exist yet
        // $_appBase already calculated above: dirname(dirname(SCRIPT_NAME))
        //
        // Example:
        // installing in /flexcore/install/ → $_appBase = /flexcore
        // installing at root /install/     → $_appBase = ''

        $htaccess    = BASE . '/.htaccess';
        $rewriteBase = $_appBase !== '' ? "\nRewriteBase {$_appBase}" : '';

        if (!file_exists($htaccess)) {
            file_put_contents(
                $htaccess,
                "RewriteEngine On\n" .
                "Options -Indexes\n" .
                $rewriteBase . "\n\n" .

                "# Allow OPTIONS requests to pass through to PHP\n" .
                "RewriteCond %{REQUEST_METHOD} OPTIONS\n" .
                "RewriteRule ^ index.php [QSA,L]\n" .

                "# Serve physical files directly\n" .
                "RewriteCond %{REQUEST_FILENAME} -f\n" .
                "RewriteRule ^ - [L]\n\n" .

                "# Route everything else to index.php\n" .
                "RewriteRule ^ index.php [QSA,L]\n"
            );
        }

        header('Location: ' . installUrl('install') . '?step=3');
        exit;
    }

    // Return to the form with validation errors
    $step = 2;
}
?>