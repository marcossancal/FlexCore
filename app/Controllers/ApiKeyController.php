<?php

declare(strict_types=1);

namespace FlexCore\App\Controllers;
use Auth;
use DB;
/**
 * ApiKeyController — SRP: gerencia API keys.
 *
 * Rotas cobertas:
 *   GET  /api
 *   GET  /api/docs
 *   POST /api/keys/create
 *   POST /api/keys/{id}/update
 *   POST /api/keys/{id}/toggle
 *   POST /api/keys/{id}/delete
 */
class ApiKeyController
{
    public function docs(): void
    {
        Auth::require(['admin', 'editor']);
        view('api/docs');
    }

    public function index(): void
    {
        Auth::require(['admin']);
        $keys     = DB::q('SELECT * FROM api_keys ORDER BY created_at DESC');
        $entities = DB::q('SELECT id, name, slug, icon FROM entities WHERE active = 1 ORDER BY name ASC');
        $newKey   = $_SESSION['_new_api_key'] ?? null;
        unset($_SESSION['_new_api_key']);
        view('api/keys', compact('keys', 'entities', 'newKey'));
    }

    public function store(): void
    {
        Auth::require(['admin']);
        $name = trim(post('name'));
        if (!$name) { flash('err', 'Nome obrigatório.'); redirect('/api'); }

        $scope = post('scope', 'all');
        $perms = $scope === 'all'
            ? ['scope' => 'all']
            : ['scope' => 'custom', 'entities' => $_POST['perm'] ?? []];

        $rawKey  = 'fc_' . bin2hex(random_bytes(32));
        $expires = trim(post('expires_at')) ?: null;

        DB::exec(
            'INSERT INTO api_keys (name, key_hash, key_preview, permissions, rate_limit, created_by, expires_at)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$name, hash('sha256', $rawKey), substr($rawKey, 0, 10),
             json_encode($perms), (int) post('rate_limit', 60), Auth::id(), $expires]
        );

        $_SESSION['_new_api_key'] = $rawKey;
        flash('ok', "Chave \"{$name}\" criada!");
        redirect('/api');
    }

    public function update(int $id): void
    {
        Auth::require(['admin']);
        $scope   = post('scope', 'all');
        $perms   = $scope === 'all' ? ['scope' => 'all'] : ['scope' => 'custom', 'entities' => $_POST['perm'] ?? []];
        $expires = trim(post('expires_at')) ?: null;

        DB::run(
            'UPDATE api_keys SET name = ?, permissions = ?, rate_limit = ?, expires_at = ? WHERE id = ?',
            [trim(post('name')), json_encode($perms), (int) post('rate_limit', 60), $expires, $id]
        );
        flash('ok', 'Chave atualizada!');
        redirect('/api');
    }

    public function toggle(int $id): void
    {
        Auth::require(['admin']);
        $row = DB::one('SELECT active FROM api_keys WHERE id = ?', [$id]);
        DB::run('UPDATE api_keys SET active = ? WHERE id = ?', [$row['active'] ? 0 : 1, $id]);
        flash('ok', 'Status alterado.');
        redirect('/api');
    }

    public function destroy(int $id): void
    {
        Auth::require(['admin']);
        DB::run('DELETE FROM api_keys WHERE id = ?', [$id]);
        flash('ok', 'Chave revogada.');
        redirect('/api');
    }
}
