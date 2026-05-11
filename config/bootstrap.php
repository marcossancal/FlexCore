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

// ── Libs (sem namespace — carregadas primeiro) ────────────────────────
require_once BASE_BS . '/lib/DB.php';
require_once BASE_BS . '/lib/Auth.php';
require_once BASE_BS . '/lib/helpers.php';

// ── Core: Router ─────────────────────────────────────────────────────
require_once BASE_BS . '/core/Router/MiddlewareInterface.php';
require_once BASE_BS . '/core/Router/Request.php';
require_once BASE_BS . '/core/Router/Route.php';
require_once BASE_BS . '/core/Router/Router.php';

// ── Core: Hooks ──────────────────────────────────────────────────────
require_once BASE_BS . '/core/Hooks/HookDispatcher.php';

// ── Core: Container (DI) ─────────────────────────────────────────────
require_once BASE_BS . '/core/Container/Container.php';

// ── App: Repositories ────────────────────────────────────────────────
require_once BASE_BS . '/app/Repositories/RepositoryInterface.php';
require_once BASE_BS . '/app/Repositories/EntityRepositoryInterface.php';
require_once BASE_BS . '/app/Repositories/BaseRepository.php';
require_once BASE_BS . '/app/Repositories/EntityRepository.php';
require_once BASE_BS . '/app/Repositories/RecordRepository.php';
require_once BASE_BS . '/app/Repositories/FieldRepository.php';
require_once BASE_BS . '/app/Repositories/AutomationRepository.php';

// ── App: Services ────────────────────────────────────────────────────
require_once BASE_BS . '/app/Services/AuditService.php';
require_once BASE_BS . '/app/Services/RecordService.php';

// ── App: Controllers ─────────────────────────────────────────────────
require_once BASE_BS . '/app/Controllers/AuthController.php';
require_once BASE_BS . '/app/Controllers/DashboardController.php';
require_once BASE_BS . '/app/Controllers/EntityController.php';
require_once BASE_BS . '/app/Controllers/RecordController.php';
require_once BASE_BS . '/app/Controllers/SettingsController.php';
require_once BASE_BS . '/app/Controllers/ApiKeyController.php';
require_once BASE_BS . '/app/Controllers/AutomationController.php';
require_once BASE_BS . '/app/Controllers/PluginController.php';

// ── Modules: Plugins ─────────────────────────────────────────────────
require_once BASE_BS . '/modules/Plugins/PluginInterface.php';
require_once BASE_BS . '/modules/Plugins/PluginManifest.php';
require_once BASE_BS . '/modules/Plugins/PluginLoader.php';

// ── Modules: Automations ─────────────────────────────────────────────
require_once BASE_BS . '/modules/Automations/ActionHandlerInterface.php';
require_once BASE_BS . '/modules/Automations/Actions/WebhookAction.php';
require_once BASE_BS . '/modules/Automations/AutomationEngine.php';

// ── API ──────────────────────────────────────────────────────────────
require_once BASE_BS . '/api/Formatters/ApiResponse.php';
require_once BASE_BS . '/api/Middleware/ApiAuthMiddleware.php';
require_once BASE_BS . '/api/Controllers/ApiRecordController.php';

// ── BASE_PATH (usado por url(), redirect(), Router) ──────────────────
define('BASE_PATH', rtrim(dirname($_SERVER['SCRIPT_NAME']), '/'));

// ── Container de DI ──────────────────────────────────────────────────
// Registra todos os bindings. O $container fica disponível globalmente
// via Container::getInstance() em qualquer parte do código.
$container = require_once BASE_BS . '/config/container.php';

// ── Boot: PluginLoader ───────────────────────────────────────────────
// Só inicializa plugins se o banco já estiver disponível (.installed existe).
// Isso evita erros durante a instalação, quando as tabelas ainda não existem.
if (file_exists(BASE_BS . '/.installed')) {
    try {
        $activePluginIds = array_column(
            \DB::q('SELECT plugin_id FROM plugins WHERE active = 1'),
            'plugin_id'
        );

        // APP_VERSION é definido no index.php — usar fallback seguro
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
    // Registra os listeners de hooks. As automações disparam automaticamente
    // em record.created / record.updated / record.deleted.
    try {
        /** @var \FlexCore\Modules\Automations\AutomationEngine $automationEngine */
        $automationEngine = $container->make(\FlexCore\Modules\Automations\AutomationEngine::class);
        $automationEngine->boot();

    } catch (\Throwable $e) {
        error_log('FlexCore: AutomationEngine boot falhou: ' . $e->getMessage());
    }
}