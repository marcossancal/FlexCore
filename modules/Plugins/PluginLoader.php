<?php

namespace FlexCore\Modules\Plugins;

/**
 * PluginLoader — descobre e inicializa plugins.
 * Compatible: PHP 7.4+
 */
class PluginLoader
{
    /** @var PluginInterface[] */
    private $loaded = [];

    /** @var string */
    private $pluginsDir;

    /** @var string */
    private $flexCoreVersion;

    public function __construct(string $pluginsDir, string $flexCoreVersion)
    {
        $this->pluginsDir      = $pluginsDir;
        $this->flexCoreVersion = $flexCoreVersion;
    }

    public function loadAll(array $activePluginIds = []): void
    {
        foreach ($this->discover() as $plugin) {
            $manifest = $plugin->manifest();
            $mid = is_array($manifest) ? ($manifest['id'] ?? '') : $manifest->id;
            $req = is_array($manifest) ? ($manifest['requires'] ?? '0.1.0') : $manifest->requires;

            if (!empty($activePluginIds) && !in_array($mid, $activePluginIds)) {
                continue;
            }
            if (!$this->isCompatible($req)) {
                error_log("FlexCore: plugin [{$mid}] requires v{$req}, skipping.");
                continue;
            }
            try {
                $plugin->boot();
                $this->loaded[] = $plugin;
            } catch (\Throwable $e) {
                error_log("FlexCore: plugin [{$mid}] boot failed: {$e->getMessage()}");
            }
        }
    }

    /** @return PluginInterface[] */
    public function loaded(): array
    {
        return $this->loaded;
    }

    /** @return PluginInterface[] */
    private function discover(): array
    {
        $plugins = [];
        if (!is_dir($this->pluginsDir)) return $plugins;

        foreach (new \DirectoryIterator($this->pluginsDir) as $dir) {
            if (!$dir->isDir() || $dir->isDot()) continue;

            $manifestPath = $dir->getPathname() . '/plugin.json';
            $pluginPath   = $dir->getPathname() . '/Plugin.php';

            if (!file_exists($manifestPath) || !file_exists($pluginPath)) continue;

            $manifest = json_decode(file_get_contents($manifestPath), true);

            if (!empty($manifest['class'])) {
                $class = $manifest['class'];
            } elseif (!empty($manifest['namespace'])) {
                $class = rtrim($manifest['namespace'], '\\') . '\\Plugin';
            } else {
                $namespace = $this->dirToNamespace($dir->getFilename());
                $class     = $namespace . '\\Plugin';
                error_log("FlexCore: plugin [{$dir->getFilename()}] sem 'namespace' em plugin.json. Usando: [{$class}].");
            }

            require_once $pluginPath;

            if (!class_exists($class)) {
                error_log("FlexCore: plugin class [{$class}] não encontrada em {$pluginPath}");
                continue;
            }

            $instance = new $class();

            if (!($instance instanceof PluginInterface)) {
                error_log("FlexCore: [{$class}] não implementa PluginInterface");
                continue;
            }

            $plugins[] = $instance;
        }

        return $plugins;
    }

    private function dirToNamespace(string $dirName): string
    {
        return str_replace(' ', '', ucwords(str_replace('-', ' ', $dirName)));
    }

    private function isCompatible(string $requires): bool
    {
        return version_compare($this->flexCoreVersion, $requires, '>=');
    }
}