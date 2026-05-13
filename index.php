<?php

/**
 * FlexCore — Entry point.
 *
 * File responsabilities (ONLY this):
 *   1. Define global contants (BASE, APP_VERSION)
 *   2. Load bootstrap (env, session, helpers, DB, Auth)
 *   3. Installation Guard 
 *   4. DB connection test
 *   5. Language loader
 *   6. Auth Guard (except public routes)
 *   7. Instance Router, load routes and despatch
 *
 * To add ou edit routes: config/routes.php
 * To add business logic: app/Controllers
 */

define('BASE',        __DIR__);
define('APP_VERSION', '1.0.0');  // ← Change here every release



require_once __DIR__ . '/config/bootstrap.php';

// ── CORS ─────────────────────────────────────────────────────────────
// Before any code. header_remove() cleans anything that apache could be add before
$_corsOrigin = '*'; // production: change by 'https://yourdomain.com'
header_remove('Access-Control-Allow-Origin');
header('Access-Control-Allow-Origin: '  . $_corsOrigin);
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization');
header('Access-Control-Allow-Credentials: false');
header('Access-Control-Max-Age: 86400');

// Preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit();
}
// ─────────────────────────────────────────────────────────────────────


// ── 1. Installation Guard ───────────────────────────────────────────
$_base      = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
$_reqUri    = strtok(rawurldecode($_SERVER['REQUEST_URI'] ?? '/'), '?');
$_rel       = $_base !== '' && strpos($_reqUri, $_base) === 0
    ? substr($_reqUri, strlen($_base))
    : $_reqUri;
$_rel       = '/' . ltrim($_rel, '/');
$_onInstall = strpos($_rel, '/install') === 0;

if ((!file_exists(__DIR__ . '/.env') || !file_exists(__DIR__ . '/.installed')) && !$_onInstall) {
    header('Location: ' . $_base . '/install/');
    exit;
}

// ── 2. DB Connection test ───────────────────────────────────────────
if (file_exists(__DIR__ . '/.env') && !$_onInstall) {
    try {
        DB::get();
    } catch (Throwable $e) {
        http_response_code(503);
        die(renderDbError($e->getMessage(), $_base));
    }
}

// ── 3. Language Loader (ptBR as callback) ────────────────────────────────────────────────
if (!$_onInstall) {
    if (Auth::check()) {
        $u     = Auth::user();
        $_lang = $u['lang'] ?? DB::setting('app_lang', 'pt_BR') ?: 'pt_BR';
    } else {
        $_lang = DB::setting('app_lang', 'pt_BR') ?: 'pt_BR';
    }
    loadTranslations($_lang);
}

// ── 4. Login Guard  ────────────────────────────────────────────────
// Public routes (no auth)
$_publicRoutes = ['/login', '/logout'];
$_currentPath  = '/' . ltrim($_rel, '/');

// API Rest routes uses API Key, there is no session needing
$_isApiRoute = strpos($_currentPath, '/api/v1/') === 0;

if (!$_onInstall && !$_isApiRoute && !in_array($_currentPath, $_publicRoutes, true) && !Auth::check()) {
    header('Location: ' . BASE_PATH . '/login');
    exit;
}

// ── 5. Router ────────────────────────────────────────────────────────
$router = new \FlexCore\Core\Router\Router(BASE_PATH);

require_once __DIR__ . '/config/routes.php';

$router->dispatch();

// ────────────────────────────────────────────────────────────────────
// Local Helpers  (don't belong to any controller)
// ────────────────────────────────────────────────────────────────────

/**
 * Render DB connection error view
 * Flow in anothe rfile to keep index.php clean
 */
function renderDbError(string $message, string $base): string
{
    $msg  = htmlspecialchars($message);
    $link = $base . '/install/';
    return <<<HTML
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
      <meta charset="UTF-8">
      <title>Erro DB — FlexCore</title>
      <style>
        *, *::before, *::after { box-sizing: border-box }
        body {
          margin: 0; min-height: 100vh;
          display: flex; align-items: center; justify-content: center;
          background: #07090e; font-family: sans-serif; color: #eef0f8;
        }
        .card {
          background: #111622; border: 1px solid rgba(255,255,255,.08);
          border-radius: 14px; padding: 36px; max-width: 480px; text-align: center;
        }
        h2   { color: #fca5a5; margin: 0 0 12px }
        p    { color: #68718f; line-height: 1.6; margin: 0 0 10px }
        code { background: #1e2640; padding: 2px 8px; border-radius: 4px; font-size: .82rem }
        a    { color: #00d4ff }
      </style>
    </head>
    <body>
      <div class="card">
        <h2>⚠️ DB connection failed</h2>
        <p>It wasn't possible to connect. Check <code>.env</code>.</p>
        <p style="font-size:.78rem;color:#4a5568">{$msg}</p>
        <p style="margin-top:18px"><a href="{$link}">↺ Reconfigure</a></p>
      </div>
    </body>
    </html>
    HTML;
}
