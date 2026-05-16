<?php

define('BASE_BS', __DIR__ . '/..');

if (!defined('BASE')) {
    define('BASE', realpath(__DIR__ . '/..'));
}

// ── .env ─────────────────────────────────────────────────────────────
if (file_exists(BASE_BS . '/.env')) {
    foreach (file(BASE_BS . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            [$k, $v] = explode('=', $line, 2);
            $_ENV[trim($k)] = trim($v);
            putenv(trim($k) . '=' . trim($v));
        }
    }
}

// ── DEBUG — controlled by DEBUG=true at .env ────────────────────────
if (($_ENV['DEBUG'] ?? 'false') === 'true') {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    error_reporting(0);
}

session_start();

// ── PSR-4 Autoloader ──────────────────────────────────────────────────
// Resolves FlexCore\* namespaces to the project's root directory.
// Eliminates the need for manual require_once calls for each file.
// Registered prefixes:
//   FlexCore\Lib\        → lib/
//   FlexCore\Core\       → core/
//   FlexCore\App\        → app/
//   FlexCore\Api\        → api/
//   FlexCore\Modules\    → modules/

spl_autoload_register(function (string $class): void {
    $map = [
        'FlexCore\\Lib\\'     => BASE_BS . '/lib/',
        'FlexCore\\Core\\'    => BASE_BS . '/core/',
        'FlexCore\\App\\'     => BASE_BS . '/app/',
        'FlexCore\\Api\\'     => BASE_BS . '/api/',
        'FlexCore\\Modules\\' => BASE_BS . '/modules/',
    ];

    foreach ($map as $prefix => $base) {
        if (strpos($class, $prefix) !== 0) continue;
        $relative = substr($class, strlen($prefix));
        $file     = $base . str_replace('\\', '/', $relative) . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// ── Global aliases ────────────────────────────────────────────────────
// DB and Auth are namespaced classes (FlexCore\Lib\DB / Auth).
// The aliases below maintain compatibility with legacy code, plugins,
// and views that reference \DB and \Auth without namespaces.
// There is no need to register them in the autoloader — class_alias is instantaneous.

class_alias(\FlexCore\Lib\DB::class,   'DB');
class_alias(\FlexCore\Lib\Auth::class, 'Auth');

// ── Helpers (globals functions) ─────────────────────────────────────────
require_once BASE_BS . '/lib/helpers.php';

// ── BASE_PATH (used por url(), redirect(), Router) ───────────────────
define('BASE_PATH', rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/'));

// ── ADMIN_PATH ────────────────────────────────────────────────────────
// Single path segment that prefixes every admin route (login, entities, etc.).
// Configured by the user at install time via the installer form and stored in .env.
// Default: 'painel'  →  /FlexCore/painel/login
//
// Plugins and front-end code are NOT affected — they own the app root freely.
// Read ADMIN_PATH from .env (already loaded above). Falls back to 'painel'.
define('ADMIN_PATH', '/' . trim($_ENV['ADMIN_PATH'] ?? 'painel', '/'));

// ── DI Container ───────────────────────────────────────────────────
$container = require_once BASE_BS . '/config/container.php';

// ── Boot: PluginLoader ───────────────────────────────────────────────
if (file_exists(BASE_BS . '/.installed')) {
    try {
        $activePluginIds = array_column(
            \DB::q('SELECT plugin_id FROM plugins WHERE active = 1'),
            'plugin_id'
        );

        $appVersion   = defined('APP_VERSION') ? APP_VERSION : '1.0.0';
        $pluginLoader = new \FlexCore\Modules\Plugins\PluginLoader(
            BASE . '/plugins',
            $appVersion
        );
        $pluginLoader->loadAll($activePluginIds ?: ['__none__']);


    } catch (\Throwable $e) {
        error_log('FlexCore: PluginLoader boot falhou: ' . $e->getMessage());
    }

    // ── Boot: AutomationEngine ───────────────────────────────────────
    try {
        /** @var \FlexCore\Modules\Automations\AutomationEngine $automationEngine */
        $automationEngine = $container->make(\FlexCore\Modules\Automations\AutomationEngine::class);
        $automationEngine->boot();

    } catch (\Throwable $e) {
        error_log('FlexCore: AutomationEngine boot falhou: ' . $e->getMessage());
    }
}
