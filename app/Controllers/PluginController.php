<?php

declare(strict_types=1);

namespace FlexCore\App\Controllers;
use Auth;
use DB;

/**
 * PluginController — instalação e gerenciamento de plugins.
 *
 * Rotas cobertas:
 *   GET  /plugins
 *   GET  /plugins/docs
 *   POST /plugins/install              ← upload de ZIP
 *   POST /plugins/install-from-registry ← instala do registry oficial
 *   POST /plugins/{slug}/toggle
 *   POST /plugins/{slug}/settings
 *   POST /plugins/{slug}/uninstall
 */
class PluginController
{
    /** URL do registry oficial */
    const REGISTRY_URL = 'https://raw.githubusercontent.com/marcossancal/FlexCore-plugins/main/registry.json';

    /** Cache local do registry em segundos (1 hora) */
    const REGISTRY_TTL = 3600;

    public function docs(): void
    {
        Auth::require(['admin', 'editor']);
        view('plugins/docs');
    }

    public function index(): void
    {
        Auth::require(['admin']);
        $plugins  = DB::q('SELECT * FROM plugins ORDER BY name ASC');
        $registry = $this->fetchRegistry();
        view('plugins/index', compact('plugins', 'registry'));
    }

    // ── Instalação via ZIP ───────────────────────────────────────────

    public function install(): void
    {
        Auth::require(['admin']);
        $file = $_FILES['plugin_zip'] ?? null;
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            flash('err', 'Erro no upload.');
            redirect('/plugins');
        }

        $result = $this->extractAndRegister($file['tmp_name']);
        if ($result !== true) {
            flash('err', $result);
            redirect('/plugins');
        }

        redirect('/plugins');
    }

    // ── Instalação a partir do registry ─────────────────────────────

    public function installFromRegistry(): void
    {
        Auth::require(['admin']);

        $pluginId   = trim($_POST['plugin_id']   ?? '');
        $downloadUrl= trim($_POST['download_url'] ?? '');

        if (!$pluginId || !$downloadUrl) {
            flash('err', 'Dados inválidos.');
            redirect('/plugins');
        }

        // Valida que a URL vem de github.com (segurança básica)
        $host = parse_url($downloadUrl, PHP_URL_HOST);
        if (!in_array($host, ['github.com', 'objects.githubusercontent.com', 'codeload.github.com'])) {
            flash('err', 'URL de download não permitida. Apenas repositórios GitHub são aceitos.');
            redirect('/plugins');
        }

        // Baixa o zip para um arquivo temporário
        $tmpFile = sys_get_temp_dir() . '/flexcore_registry_' . uniqid() . '.zip';
        $ctx = stream_context_create([
            'http' => [
                'timeout'     => 30,
                'user_agent'  => 'FlexCore-PluginManager/1.0',
                'follow_location' => 1,
            ]
        ]);

        $bytes = file_put_contents($tmpFile, file_get_contents($downloadUrl, false, $ctx));
        if (!$bytes) {
            flash('err', 'Não foi possível baixar o plugin. Verifique sua conexão.');
            redirect('/plugins');
        }

        $result = $this->extractAndRegister($tmpFile);
        unlink($tmpFile);

        if ($result !== true) {
            flash('err', $result);
            redirect('/plugins');
        }

        redirect('/plugins');
    }

    // ── Toggle ───────────────────────────────────────────────────────

    public function toggle(string $slug): void
    {
        Auth::require(['admin']);
        $row = DB::one('SELECT active FROM plugins WHERE plugin_id = ?', [$slug]);
        DB::run('UPDATE plugins SET active = ? WHERE plugin_id = ?', [$row['active'] ? 0 : 1, $slug]);
        flash('ok', 'Status alterado.');
        redirect('/plugins');
    }

    // ── Settings ─────────────────────────────────────────────────────

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

    // ── Uninstall ────────────────────────────────────────────────────

    public function uninstall(string $slug): void
    {
        Auth::require(['admin']);
        $dest = BASE . '/plugins/' . $slug;

        $pluginFile = $dest . '/Plugin.php';
        if (file_exists($pluginFile)) {
            require_once $pluginFile;
            $ns    = str_replace(' ', '', ucwords(str_replace('-', ' ', $slug)));
            $class = $ns . '\\Plugin';
            if (class_exists($class)) (new $class())->uninstall();
        }

        if (is_dir($dest)) {
            $it = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dest, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($it as $f) { $f->isDir() ? rmdir($f->getPathname()) : unlink($f->getPathname()); }
            rmdir($dest);
        }

        DB::run('DELETE FROM plugins WHERE plugin_id = ?', [$slug]);
        flash('ok', 'Plugin removido.');
        redirect('/plugins');
    }

    // ── Helpers privados ─────────────────────────────────────────────

    /**
     * Extrai um ZIP e registra o plugin no banco.
     * Retorna true em caso de sucesso ou string de erro.
     *
     * @return true|string
     */
    private function extractAndRegister(string $zipPath)
    {
        $tmpDir = sys_get_temp_dir() . '/flexcore_' . uniqid();
        mkdir($tmpDir);

        $zip = new \ZipArchive();
        if ($zip->open($zipPath) !== true) {
            return 'ZIP inválido ou corrompido.';
        }
        $zip->extractTo($tmpDir);
        $zip->close();

        // Suporte a zip com subpasta raiz (padrão do GitHub)
        $manifestPath = $tmpDir . '/plugin.json';
        if (!file_exists($manifestPath)) {
            // Tenta encontrar plugin.json um nível abaixo
            foreach (glob($tmpDir . '/*/plugin.json') as $found) {
                $manifestPath = $found;
                $tmpDir       = dirname($found);
                break;
            }
        }

        if (!file_exists($manifestPath)) {
            return 'plugin.json não encontrado no ZIP.';
        }

        $manifest = json_decode(file_get_contents($manifestPath), true);
        if (!$manifest || empty($manifest['id'])) {
            return 'plugin.json inválido ou sem campo "id".';
        }

        $dest = BASE . '/plugins/' . $manifest['id'];
        if (is_dir($dest)) {
            return 'Plugin já instalado. Remova antes de reinstalar.';
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

        flash('ok', "Plugin \"{$manifest['name']}\" instalado com sucesso!");
        return true;
    }

    /**
     * Busca e faz cache do registry.json oficial.
     * Retorna array de plugins ou [] em caso de falha.
     */
    private function fetchRegistry(): array
    {
        $cacheFile = sys_get_temp_dir() . '/flexcore_registry_cache.json';

        // Usa cache se ainda válido
        if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < self::REGISTRY_TTL) {
            $cached = json_decode(file_get_contents($cacheFile), true);
            if (is_array($cached)) return $cached;
        }

        // Busca do GitHub
        $ctx = stream_context_create([
            'http' => [
                'timeout'    => 8,
                'user_agent' => 'FlexCore-PluginManager/1.0',
            ]
        ]);

        $raw = @file_get_contents(self::REGISTRY_URL, false, $ctx);
        if (!$raw) return [];

        $data = json_decode($raw, true);
        if (!is_array($data)) return [];

        // Salva cache
        file_put_contents($cacheFile, $raw);
        return $data;
    }
}