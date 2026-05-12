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

// ── DEBUG — controlado por DEBUG=true no .env ────────────────────────
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

// ── Autoloader PSR-4 ─────────────────────────────────────────────────
// Resolve namespaces FlexCore\* para o diretório raiz do projeto.
// Elimina a necessidade de require_once manual para cada arquivo.
// Prefixos registrados:
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

// ── Aliases globais ───────────────────────────────────────────────────
// DB e Auth são classes namespaced (FlexCore\Lib\DB / Auth).
// Os aliases abaixo mantêm compatibilidade com código legado, plugins
// e views que referenciam \DB e \Auth sem namespace.
// Não é necessário registrá-los no autoloader — class_alias é instantâneo.
class_alias(\FlexCore\Lib\DB::class,   'DB');
class_alias(\FlexCore\Lib\Auth::class, 'Auth');

// ── Helpers (funções globais) ─────────────────────────────────────────
require_once BASE_BS . '/lib/helpers.php';

// ── BASE_PATH (usado por url(), redirect(), Router) ───────────────────
define('BASE_PATH', rtrim(dirname($_SERVER['SCRIPT_NAME']), '/'));

// ── Container de DI ───────────────────────────────────────────────────
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
        $pluginLoader->loadAll($activePluginIds);

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
