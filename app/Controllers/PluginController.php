<?php

declare(strict_types=1);

namespace FlexCore\App\Controllers;
use Auth;
use DB;
/**
 * PluginController — SRP: instalação e gerenciamento de plugins.
 *
 * Rotas cobertas:
 *   GET  /plugins
 *   GET  /plugins/docs
 *   POST /plugins/install
 *   POST /plugins/{slug}/toggle
 *   POST /plugins/{slug}/settings
 *   POST /plugins/{slug}/uninstall
 */
class PluginController
{
    public function docs(): void
    {
        Auth::require(['admin', 'editor']);
        view('plugins/docs');
    }

    public function index(): void
    {
        Auth::require(['admin']);
        $plugins = DB::q('SELECT * FROM plugins ORDER BY name ASC');
        view('plugins/index', compact('plugins'));
    }

    public function install(): void
    {
        Auth::require(['admin']);
        $file = $_FILES['plugin_zip'] ?? null;
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            flash('err', 'Erro no upload.');
            redirect('/plugins');
        }

        $tmpDir = sys_get_temp_dir() . '/flexcore_' . uniqid();
        mkdir($tmpDir);
        $zip = new \ZipArchive();
        if ($zip->open($file['tmp_name']) !== true) {
            flash('err', 'ZIP inválido.');
            redirect('/plugins');
        }
        $zip->extractTo($tmpDir);
        $zip->close();

        $manifestPath = $tmpDir . '/plugin.json';
        if (!file_exists($manifestPath)) {
            flash('err', 'plugin.json não encontrado.');
            redirect('/plugins');
        }

        $manifest = json_decode(file_get_contents($manifestPath), true);
        if (!$manifest || empty($manifest['id'])) {
            flash('err', 'plugin.json inválido.');
            redirect('/plugins');
        }

        $dest = BASE . '/plugins/' . $manifest['id'];
        if (is_dir($dest)) {
            flash('err', 'Plugin já instalado. Remova antes de reinstalar.');
            redirect('/plugins');
        }
        if (!is_dir(BASE . '/plugins')) mkdir(BASE . '/plugins');
        rename($tmpDir, $dest);

        DB::exec(
            'INSERT INTO plugins (plugin_id, name, version, description, author, manifest, active)
             VALUES (?, ?, ?, ?, ?, ?, 1)
             ON DUPLICATE KEY UPDATE
                 name     = VALUES(name),
                 version  = VALUES(version),
                 manifest = VALUES(manifest)',
            [
                $manifest['id'],
                $manifest['name']        ?? $manifest['id'],
                $manifest['version']     ?? '0.0.0',
                $manifest['description'] ?? '',
                $manifest['author']      ?? '',
                json_encode($manifest),
            ]
        );
        flash('ok', "Plugin \"{$manifest['name']}\" instalado!");
        redirect('/plugins');
    }

    public function toggle(string $slug): void
    {
        Auth::require(['admin']);
        $row = DB::one('SELECT active FROM plugins WHERE plugin_id = ?', [$slug]);
        DB::run('UPDATE plugins SET active = ? WHERE plugin_id = ?', [$row['active'] ? 0 : 1, $slug]);
        flash('ok', 'Status alterado.');
        redirect('/plugins');
    }

    public function saveSettings(string $slug): void
    {
        Auth::require(['admin']);
        DB::run(
            'UPDATE plugins SET settings = ? WHERE plugin_id = ?',
            [json_encode($_POST['settings'] ?? []), $slug]
        );
        flash('ok', 'Configurações salvas!');
        redirect('/plugins');
    }

    public function uninstall(string $slug): void
    {
        Auth::require(['admin']);
        $dest = BASE . '/plugins/' . $slug;

        // Chama Plugin::uninstall() se existir
        $pluginFile = $dest . '/Plugin.php';
        if (file_exists($pluginFile)) {
            require_once $pluginFile;
            $ns    = str_replace(' ', '', ucwords(str_replace('-', ' ', $slug)));
            $class = $ns . '\\Plugin';
            if (class_exists($class)) (new $class())->uninstall();
        }

        // Remove arquivos recursivamente
        if (is_dir($dest)) {
            $it = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dest, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($it as $f) { $f->isDir() ? rmdir($f) : unlink($f); }
            rmdir($dest);
        }

        DB::run('DELETE FROM plugins WHERE plugin_id = ?', [$slug]);
        flash('ok', 'Plugin removido.');
        redirect('/plugins');
    }
}
