<?php

declare(strict_types=1);

/**
 * routes.php — Mapa central de rotas do FlexCore.
 *
 * Como adicionar uma rota:
 *   $router->get('/caminho',           [Controller::class, 'metodo']);
 *   $router->post('/caminho',          [Controller::class, 'metodo']);
 *   $router->any('/caminho/{param}',   [Controller::class, 'metodo']);
 *
 * Parâmetros de rota usam a sintaxe {nome}.
 * O valor chega como argumento posicional no método do controller.
 *
 * Exemplo:
 *   $router->get('/e/{slug}', [RecordController::class, 'index']);
 *   // RecordController::index(string $slug): void
 */

use FlexCore\App\Controllers\AuthController;
use FlexCore\App\Controllers\DashboardController;
use FlexCore\App\Controllers\EntityController;
use FlexCore\App\Controllers\RecordController;
use FlexCore\App\Controllers\SettingsController;
use FlexCore\App\Controllers\ApiKeyController;
use FlexCore\App\Controllers\AutomationController;
use FlexCore\App\Controllers\PluginController;

// ── Auth (rotas públicas — antes do guard de login) ───────────────────
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
$router->post('/entities/{id}/api-responses',           [EntityController::class, 'saveApiResponses']);

// ── Fields ────────────────────────────────────────────────────────────
$router->get( '/entities/{id}/fields',                  [EntityController::class, 'fields']);
$router->post('/entities/{id}/fields/create',           [EntityController::class, 'storeField']);
$router->post('/entities/{id}/fields/{fid}/update',     [EntityController::class, 'updateField']);
$router->post('/entities/{id}/fields/{fid}/delete',     [EntityController::class, 'destroyField']);

// ── Records (entidades dinâmicas) /e/{slug} ───────────────────────────
$router->get( '/e/{slug}',            [RecordController::class, 'index']);
$router->get( '/e/{slug}/new',        [RecordController::class, 'create']);
$router->post('/e/{slug}/create',     [RecordController::class, 'store']);
$router->get( '/e/{slug}/{id}',       [RecordController::class, 'show']);
$router->get( '/e/{slug}/{id}/edit',  [RecordController::class, 'edit']);
$router->post('/e/{slug}/{id}/update',[RecordController::class, 'update']);
$router->post('/e/{slug}/{id}/delete',[RecordController::class, 'destroy']);

// ── Settings + Users ──────────────────────────────────────────────────
$router->get( '/settings',               [SettingsController::class, 'index']);
$router->post('/settings',               [SettingsController::class, 'save']);
$router->post('/users/create',           [SettingsController::class, 'createUser']);
$router->post('/users/{id}/update',      [SettingsController::class, 'updateUser']);
$router->post('/users/{id}/delete',      [SettingsController::class, 'destroyUser']);

// ── API Keys ──────────────────────────────────────────────────────────
$router->get( '/api',                    [ApiKeyController::class, 'index']);
// $router->get( '/api/docs',               [ApiKeyController::class, 'docs']);
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
$router->get( '/plugins',                        [PluginController::class, 'index']);
$router->get( '/plugins/docs',                   [PluginController::class, 'docs']);
$router->post('/plugins/install',                [PluginController::class, 'install']);
$router->post('/plugins/{slug}/toggle',          [PluginController::class, 'toggle']);
$router->post('/plugins/{slug}/settings',        [PluginController::class, 'saveSettings']);
$router->post('/plugins/{slug}/uninstall',       [PluginController::class, 'uninstall']);

// ── FlexCore Data Importer ────────────────────────────────────────────
// Só registra as rotas se o plugin estiver instalado e ativo
if (
    file_exists(BASE . '/plugins/flexcore-data-importer/ImporterController.php') &&
    \DB::one("SELECT id FROM plugins WHERE plugin_id = 'flexcore-data-importer' AND active = 1")
) {
    require_once BASE . '/plugins/flexcore-data-importer/ImporterController.php';
    $importer = new \FlexCoreDataImporter\ImporterController();

    $router->get( '/importer',                [$importer, 'index']);
    $router->post('/importer/upload',         [$importer, 'upload']);
    $router->get( '/importer/map/{token}',    [$importer, 'map']);
    $router->post('/importer/map/{token}',    [$importer, 'saveMap']);
    $router->get( '/importer/run/{token}',    [$importer, 'run']);
    $router->post('/importer/run/{token}',    [$importer, 'run']);
    $router->get( '/importer/result/{token}', [$importer, 'result']);
}
