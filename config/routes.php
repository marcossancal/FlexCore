<?php

declare(strict_types=1);

/**
 * routes.php — Mapa central de rotas do FlexCore.
 *
 * Rotas de plugins opcionais NÃO ficam mais aqui com DB::one inline.
 * Cada plugin registra suas próprias rotas via hook 'router.register'
 * no método boot() do Plugin.php — veja docs/plugins.md.
 */

use FlexCore\App\Controllers\AuthController;
use FlexCore\App\Controllers\DashboardController;
use FlexCore\App\Controllers\EntityController;
use FlexCore\App\Controllers\RecordController;
use FlexCore\App\Controllers\SettingsController;
use FlexCore\App\Controllers\ApiKeyController;
use FlexCore\App\Controllers\AutomationController;
use FlexCore\App\Controllers\PluginController;
use FlexCore\Api\Controllers\ApiRecordController;
use FlexCore\Api\Middleware\ApiAuthMiddleware;

// ── Auth ──────────────────────────────────────────────────────────────
$router->get( '/login',  [AuthController::class, 'showLogin']);
$router->post('/login',  [AuthController::class, 'login']);
$router->get( '/logout', [AuthController::class, 'logout']);

// ── Dashboard ─────────────────────────────────────────────────────────
$router->get('/',          [DashboardController::class, 'index']);
$router->get('/dashboard', [DashboardController::class, 'index']);

// ── Entities ──────────────────────────────────────────────────────────
$router->get( '/entities',                              [EntityController::class, 'index']);
$router->get( '/entities/new',                          [EntityController::class, 'create']);
$router->post('/entities/create',                       [EntityController::class, 'store']);
$router->get( '/entities/{id}/edit',                    [EntityController::class, 'edit']);
$router->post('/entities/{id}/update',                  [EntityController::class, 'update']);
$router->post('/entities/{id}/delete',                  [EntityController::class, 'destroy']);
$router->post('/entities/bulk-delete',                  [EntityController::class, 'bulkDestroy']);
$router->post('/entities/{id}/api-responses',           [EntityController::class, 'saveApiResponses']);

// ── Fields ────────────────────────────────────────────────────────────
$router->get( '/entities/{id}/fields',                  [EntityController::class, 'fields']);
$router->post('/entities/{id}/fields/create',           [EntityController::class, 'storeField']);
$router->post('/entities/{id}/fields/{fid}/update',     [EntityController::class, 'updateField']);
$router->post('/entities/{id}/fields/{fid}/delete',     [EntityController::class, 'destroyField']);

// ── Records (entidades dinâmicas) /e/{slug} ───────────────────────────
$router->get( '/e/{slug}',             [RecordController::class, 'index']);
$router->get( '/e/{slug}/new',         [RecordController::class, 'create']);
$router->post('/e/{slug}/create',      [RecordController::class, 'store']);
$router->post('/e/{slug}/set-view',    [RecordController::class, 'setView']);
$router->get( '/e/{slug}/{id}',        [RecordController::class, 'show']);
$router->get( '/e/{slug}/{id}/edit',   [RecordController::class, 'edit']);
$router->post('/e/{slug}/{id}/update', [RecordController::class, 'update']);
$router->post('/e/{slug}/{id}/delete', [RecordController::class, 'destroy']);

// ── Settings + Users ──────────────────────────────────────────────────
$router->get( '/settings',               [SettingsController::class, 'index']);
$router->post('/settings',               [SettingsController::class, 'save']);
$router->post('/users/create',           [SettingsController::class, 'createUser']);
$router->post('/users/{id}/update',      [SettingsController::class, 'updateUser']);
$router->post('/users/{id}/delete',      [SettingsController::class, 'destroyUser']);

// ── API Keys (interface web) ──────────────────────────────────────────
$router->get( '/api',                    [ApiKeyController::class, 'index']);
$router->post('/api/keys/create',        [ApiKeyController::class, 'store']);
$router->post('/api/keys/{id}/update',   [ApiKeyController::class, 'update']);
$router->post('/api/keys/{id}/toggle',   [ApiKeyController::class, 'toggle']);
$router->post('/api/keys/{id}/delete',   [ApiKeyController::class, 'destroy']);

// ── Automations ───────────────────────────────────────────────────────
$router->get( '/automations',                [AutomationController::class, 'index']);
$router->post('/automations/create',         [AutomationController::class, 'store']);
$router->post('/automations/{id}/update',    [AutomationController::class, 'update']);
$router->post('/automations/{id}/toggle',    [AutomationController::class, 'toggle']);
$router->post('/automations/{id}/delete',    [AutomationController::class, 'destroy']);
$router->get( '/automations/{id}/logs',      [AutomationController::class, 'logs']);

// ── Plugins ───────────────────────────────────────────────────────────
$router->get( '/plugins',                    [PluginController::class, 'index']);
$router->get( '/plugins/docs',               [PluginController::class, 'docs']);
$router->post('/plugins/install',            [PluginController::class, 'install']);
$router->post('/plugins/{slug}/toggle',      [PluginController::class, 'toggle']);
$router->post('/plugins/{slug}/settings',    [PluginController::class, 'saveSettings']);
$router->post('/plugins/{slug}/uninstall',   [PluginController::class, 'uninstall']);

// ── API REST v1 ───────────────────────────────────────────────────────
$router->get(   '/api/v1/entities',          [ApiRecordController::class, 'entities'])
       ->middleware(new ApiAuthMiddleware());

$router->get(   '/api/v1/e/{slug}',          [ApiRecordController::class, 'index'])
       ->middleware(new ApiAuthMiddleware());

$router->get(   '/api/v1/e/{slug}/{id}',     [ApiRecordController::class, 'show'])
       ->middleware(new ApiAuthMiddleware());

$router->post(  '/api/v1/e/{slug}',          [ApiRecordController::class, 'store'])
       ->middleware(new ApiAuthMiddleware());

$router->put(   '/api/v1/e/{slug}/{id}',     [ApiRecordController::class, 'update'])
       ->middleware(new ApiAuthMiddleware());

$router->delete('/api/v1/e/{slug}/{id}',     [ApiRecordController::class, 'destroy'])
       ->middleware(new ApiAuthMiddleware());

// ── Rotas de plugins ativos ───────────────────────────────────────────
// Plugins registram suas rotas via hook 'router.register' no boot().
// Exemplo no Plugin.php do plugin:
//
//   public function boot(): void
//   {
//       \FlexCore\Core\Hooks\Hooks::on('router.register', function ($router) {
//           $router->get('/importer', [ImporterController::class, 'index']);
//           // ...
//       });
//   }
//
// O hook é disparado abaixo — sem DB::one inline neste arquivo.
\FlexCore\Core\Hooks\Hooks::fire('router.register', [$router]);
