<?php

declare(strict_types=1);

namespace FlexCore\App\Controllers;
use Auth;
use DB;
/**
 * SettingsController — SRP: configurações do sistema e CRUD de usuários.
 *
 * Rotas cobertas:
 *   GET  /settings
 *   POST /settings
 *   POST /users/create
 *   POST /users/{id}/update
 *   POST /users/{id}/delete
 */
class SettingsController
{
    // ── Settings ─────────────────────────────────────────────────────

    public function index(): void
    {
        Auth::require(['admin']);
        $users = DB::q('SELECT id, name, email, role, active, last_login FROM users ORDER BY name ASC');
        view('settings/index', compact('users'));
    }

    public function save(): void
    {
        Auth::require(['admin']);
        $tab = post('tab', 'geral');

        if ($tab === 'geral') {
            $this->saveGeral();
        } elseif ($tab === 'tema') {
            $this->saveTema();
        } elseif ($tab === 'lang') {
            $this->saveLang();
        }

        flash('ok', __('settings.saved'));
        admin_redirect("/settings?tab={$tab}");
    }

    // ── Users ─────────────────────────────────────────────────────────

    public function createUser(): void
    {
        Auth::require(['admin']);
        $email = trim(post('email'));
        $name  = trim(post('name'));
        $pwd   = post('password');

        if (!$name || !$email || !$pwd) {
            flash('err', 'Nome, e-mail e senha obrigatórios.');
            admin_redirect('/settings?tab=usuarios');
        }
        if (DB::one('SELECT id FROM users WHERE email = ?', [$email])) {
            flash('err', 'E-mail já cadastrado.');
            admin_redirect('/settings?tab=usuarios');
        }

        DB::exec(
            'INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)',
            [$name, $email, password_hash($pwd, PASSWORD_DEFAULT), post('role', 'editor')]
        );
        flash('ok', "Usuário '{$name}' criado!");
        admin_redirect('/settings?tab=usuarios');
    }

    public function updateUser(int $id): void
    {
        Auth::require(['admin']);
        $pwd = post('password');
        if ($pwd) {
            DB::run('UPDATE users SET password = ? WHERE id = ?', [password_hash($pwd, PASSWORD_DEFAULT), $id]);
        }
        DB::run(
            'UPDATE users SET name = ?, email = ?, role = ? WHERE id = ?',
            [trim(post('name')), trim(post('email')), post('role', 'editor'), $id]
        );
        flash('ok', 'Usuário atualizado!');
        admin_redirect('/settings?tab=usuarios');
    }

    public function destroyUser(int $id): void
    {
        Auth::require(['admin']);
        if ($id === Auth::id()) {
            flash('err', 'Você não pode excluir sua própria conta.');
            admin_redirect('/settings?tab=usuarios');
        }
        DB::run('DELETE FROM users WHERE id = ?', [$id]);
        flash('ok', 'Usuário excluído.');
        admin_redirect('/settings?tab=usuarios');
    }

    // ── Internals ─────────────────────────────────────────────────────

    private function saveGeral(): void
    {
        DB::setSetting('app_name',    post('app_name', 'FlexCore'), 'Nome do sistema',  'geral');
        DB::setSetting('app_url',     post('app_url', ''),           'URL da aplicação', 'geral');
        DB::setSetting('app_logo',    post('app_logo', ''),          'Logo',             'geral');
        DB::setSetting('app_favicon', post('app_favicon', ''),       'Favicon',          'geral');
    }

    private function saveTema(): void
    {
        DB::setSetting('theme_mode',    post('theme_mode', 'dark'),      'Modo do tema',   'tema');
        DB::setSetting('theme_preset',  post('theme_preset', 'default'), 'Preset de tema', 'tema');
        DB::setSetting('color_accent',  post('color_accent', ''),        'Cor de destaque','tema');
        DB::setSetting('color_accent2', post('color_accent2', ''),       'Cor secundária', 'tema');
    }

    private function saveLang(): void
    {
        $lang     = post('app_lang', 'pt_BR');
        $langFile = BASE . '/translates/' . $lang . '.json';
        if (!file_exists($langFile)) {
            flash('err', 'Arquivo de idioma não encontrado.');
            admin_redirect('/settings?tab=lang');
        }
        DB::setSetting('app_lang', $lang, 'Idioma padrão', 'geral');
        loadTranslations($lang);
    }
}