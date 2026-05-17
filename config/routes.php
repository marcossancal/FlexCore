<?php

declare(strict_types=1);

/**
 * routes.php — Central FlexCore route map.
 *
 * Each plugin registers its own routes via the 'router.register' hook
 * inside the boot() method of Plugin.php — see docs/plugins.md.
 */

use FlexCore\App\Controllers\AuthController;
use FlexCore\App\Controllers\DashboardController;
use FlexCore\App\Controllers\EntityController;
use FlexCore\App\Controllers\RecordController;
use FlexCore\App\Controllers\SettingsController;
use FlexCore\App\Controllers\ApiKeyController;
use FlexCore\App\Controllers\AutomationController;
use FlexCore\App\Controllers\PluginController;
use FlexCore\App\Controllers\AuditController;
use FlexCore\Api\Controllers\ApiRecordController;
use FlexCore\Api\Middleware\ApiAuthMiddleware;

// ── Admin routes — all prefixed with ADMIN_PATH ──────────────────────
//
// ADMIN_PATH is a constant defined in bootstrap.php (read from .env).
// It is a path segment like '/painel', configurable at install time.
// The front-end root (/) is completely free for plugins to use.
//
// Using a local variable avoids repeating ADMIN_PATH in every line
// and makes the file readable regardless of what the value is.
$_ap = ADMIN_PATH; // e.g. '/painel'

// ── Auth ──────────────────────────────────────────────────────────────
$router->get( "$_ap/login",  [AuthController::class, 'showLogin']);
$router->post("$_ap/login",  [AuthController::class, 'login']);
$router->get( "$_ap/logout", [AuthController::class, 'logout']);

// ── Dashboard ─────────────────────────────────────────────────────────
$router->get("$_ap",            [DashboardController::class, 'index']);
$router->get("$_ap/",           [DashboardController::class, 'index']);
$router->get("$_ap/dashboard",  [DashboardController::class, 'index']);

// ── Entities ──────────────────────────────────────────────────────────
$router->get( "$_ap/entities",                              [EntityController::class, 'index']);
$router->get( "$_ap/entities/new",                          [EntityController::class, 'create']);
$router->post("$_ap/entities/create",                       [EntityController::class, 'store']);
$router->get( "$_ap/entities/{id}/edit",                    [EntityController::class, 'edit']);
$router->post("$_ap/entities/{id}/update",                  [EntityController::class, 'update']);
$router->post("$_ap/entities/{id}/delete",                  [EntityController::class, 'destroy']);
$router->post("$_ap/entities/bulk-delete",                  [EntityController::class, 'bulkDestroy']);
$router->post("$_ap/entities/{id}/api-responses",           [EntityController::class, 'saveApiResponses']);

// ── Fields ────────────────────────────────────────────────────────────
$router->get( "$_ap/entities/{id}/fields",                  [EntityController::class, 'fields']);
$router->post("$_ap/entities/{id}/fields/create",           [EntityController::class, 'storeField']);
$router->post("$_ap/entities/{id}/fields/{fid}/update",     [EntityController::class, 'updateField']);
$router->post("$_ap/entities/{id}/fields/{fid}/delete",     [EntityController::class, 'destroyField']);

// ── Records /e/{slug} ─────────────────────────────────────────────────
$router->get( "$_ap/e/{slug}",             [RecordController::class, 'index']);
$router->get( "$_ap/e/{slug}/new",         [RecordController::class, 'create']);
$router->post("$_ap/e/{slug}/create",      [RecordController::class, 'store']);
$router->post("$_ap/e/{slug}/set-view",    [RecordController::class, 'setView']);
$router->get( "$_ap/e/{slug}/{id}",        [RecordController::class, 'show']);
$router->get( "$_ap/e/{slug}/{id}/edit",   [RecordController::class, 'edit']);
$router->post("$_ap/e/{slug}/{id}/update", [RecordController::class, 'update']);
$router->post("$_ap/e/{slug}/{id}/delete", [RecordController::class, 'destroy']);

// ── Settings + Users ──────────────────────────────────────────────────
$router->get( "$_ap/settings",               [SettingsController::class, 'index']);
$router->post("$_ap/settings",               [SettingsController::class, 'save']);
$router->post("$_ap/users/create",           [SettingsController::class, 'createUser']);
$router->post("$_ap/users/{id}/update",      [SettingsController::class, 'updateUser']);
$router->post("$_ap/users/{id}/delete",      [SettingsController::class, 'destroyUser']);

// ── API Keys (web interface) ──────────────────────────────────────────
$router->get( "$_ap/api",                    [ApiKeyController::class, 'index']);
$router->post("$_ap/api/keys/create",        [ApiKeyController::class, 'store']);
$router->post("$_ap/api/keys/{id}/update",   [ApiKeyController::class, 'update']);
$router->post("$_ap/api/keys/{id}/toggle",   [ApiKeyController::class, 'toggle']);
$router->post("$_ap/api/keys/{id}/delete",   [ApiKeyController::class, 'destroy']);

// ── Automations ───────────────────────────────────────────────────────
$router->get( "$_ap/automations",                [AutomationController::class, 'index']);
$router->post("$_ap/automations/create",         [AutomationController::class, 'store']);
$router->post("$_ap/automations/{id}/update",    [AutomationController::class, 'update']);
$router->post("$_ap/automations/{id}/toggle",    [AutomationController::class, 'toggle']);
$router->post("$_ap/automations/{id}/delete",    [AutomationController::class, 'destroy']);
$router->get( "$_ap/automations/{id}/logs",      [AutomationController::class, 'logs']);

// ── Plugins ───────────────────────────────────────────────────────────
$router->get( "$_ap/plugins",                       [PluginController::class, 'index']);
$router->get( "$_ap/plugins/docs",                  [PluginController::class, 'docs']);
$router->post("$_ap/plugins/install",               [PluginController::class, 'install']);
$router->post("$_ap/plugins/install-from-registry", [PluginController::class, 'installFromRegistry']);
$router->post("$_ap/plugins/{slug}/toggle",         [PluginController::class, 'toggle']);
$router->post("$_ap/plugins/{slug}/settings",       [PluginController::class, 'saveSettings']);
$router->post("$_ap/plugins/{slug}/uninstall",      [PluginController::class, 'uninstall']);

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

// ── Audit ──────────────────────────────────────────────────────────────
$router->get( "$_ap/audit",              [AuditController::class, 'index']);
$router->get( "$_ap/audit/{id}",        [AuditController::class, 'show']);
$router->post("$_ap/audit/{id}/revert", [AuditController::class, 'revert']);

// ── Active plugin routes ──────────────────────────────────────────────
// Plugins register their routes through the 'router.register' hook in boot().
// Example inside the plugin's Plugin.php:
//
//   public function boot(): void
//   {
//       \FlexCore\Core\Hooks\Hooks::on('router.register', function ($router) {
//           $router->get('/importer', [ImporterController::class, 'index']);
//           // ...
//       });
//   }
//
// The hook is triggered below — without inline DB::one calls in this file.
\FlexCore\Core\Hooks\Hooks::fire('router.register', [$router]);