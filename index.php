<?php

/**
 * FlexCore — Entry point.
 *
 * Responsabilidades deste arquivo (e APENAS estas):
 *   1. Definir constantes globais (BASE, APP_VERSION)
 *   2. Carregar bootstrap (env, sessão, helpers, DB, Auth)
 *   3. Guard de instalação
 *   4. Teste de conexão DB
 *   5. Carregar idioma
 *   6. Guard de autenticação (exceto rotas públicas)
 *   7. Instanciar o Router, carregar rotas e despachar
 *
 * Para adicionar ou editar rotas: config/routes.php
 * Para adicionar lógica de negócio: app/Controllers/
 */

define('BASE',        __DIR__);
define('APP_VERSION', '1.0.0');  // ← altere aqui a cada release

require_once __DIR__ . '/config/bootstrap.php';

// ── 1. Guard de instalação ───────────────────────────────────────────
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

// ── 2. Teste de conexão DB ───────────────────────────────────────────
if (file_exists(__DIR__ . '/.env') && !$_onInstall) {
    try {
        DB::get();
    } catch (Throwable $e) {
        http_response_code(503);
        die(renderDbError($e->getMessage(), $_base));
    }
}

// ── 3. Carrega idioma ────────────────────────────────────────────────
if (!$_onInstall) {
    if (Auth::check()) {
        $u     = Auth::user();
        $_lang = $u['lang'] ?? DB::setting('app_lang', 'pt_BR') ?: 'pt_BR';
    } else {
        $_lang = DB::setting('app_lang', 'pt_BR') ?: 'pt_BR';
    }
    loadTranslations($_lang);
}

// ── 4. Guard de login ────────────────────────────────────────────────
// Rotas públicas (sem autenticação) — adicione aqui se precisar de mais
$_publicRoutes = ['/login', '/logout'];
$_currentPath  = '/' . ltrim($_rel, '/');

// Rotas da API REST usam API key — não dependem de sessão
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
// Helpers locais (não pertencem a nenhum controller)
// ────────────────────────────────────────────────────────────────────

/**
 * Renderiza a tela de erro de conexão com o banco.
 * Separado do fluxo principal para manter o index.php limpo.
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
        <h2>⚠️ Falha na conexão com o banco</h2>
        <p>Não foi possível conectar. Verifique o arquivo <code>.env</code>.</p>
        <p style="font-size:.78rem;color:#4a5568">{$msg}</p>
        <p style="margin-top:18px"><a href="{$link}">↺ Reconfigurar</a></p>
      </div>
    </body>
    </html>
    HTML;
}