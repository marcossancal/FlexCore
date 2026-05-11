<?php

declare(strict_types=1);

namespace FlexCore\Modules\Plugins;

use FlexCore\Core\Hooks\Hooks;

/**
 * PluginInterface — OCP + LSP.
 *
 * Every plugin implements this. The PluginLoader calls boot() on each.
 * Plugins register hooks, routes, and settings here — never modify core.
 *
 * A plugin directory looks like:
 *
 *   plugins/
 *     my-plugin/
 *       plugin.json     ← manifest
 *       Plugin.php      ← implements PluginInterface
 *       views/          ← optional blade/PHP views
 *       assets/         ← optional JS/CSS
 */
interface PluginInterface
{
    /** Plugin metadata. Must match plugin.json. */
    public function manifest(): PluginManifest;

    /**
     * Called once when the plugin is loaded.
     * Register all hooks, routes, and bindings here.
     */
    public function boot(): void;

    /**
     * Called when the plugin is uninstalled.
     * Clean up any tables or settings the plugin created.
     */
    public function uninstall(): void;
}
