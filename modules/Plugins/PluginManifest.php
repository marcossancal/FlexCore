<?php

namespace FlexCore\Modules\Plugins;

/**
 * PluginManifest — value object imutable.
 * Compatible: PHP 7.4+
 */
final class PluginManifest
{
    /** @var string */
    public $id;
    /** @var string */
    public $name;
    /** @var string */
    public $version;
    /** @var string */
    public $description;
    /** @var string */
    public $author;
    /** @var string */
    public $url;
    /** @var string */
    public $requires;
    /** @var array */
    public $hooks;
    /** @var array */
    public $settings;

    public function __construct(
        string $id,
        string $name,
        string $version,
        string $description,
        string $author,
        string $url,
        string $requires,
        array  $hooks,
        array  $settings
    ) {
        $this->id          = $id;
        $this->name        = $name;
        $this->version     = $version;
        $this->description = $description;
        $this->author      = $author;
        $this->url         = $url;
        $this->requires    = $requires;
        $this->hooks       = $hooks;
        $this->settings    = $settings;
    }

    public static function fromJson(string $path): self
    {
        if (!file_exists($path)) {
            throw new \RuntimeException("Plugin manifest not found: {$path}");
        }
        $data = json_decode(file_get_contents($path), true);
        if (!is_array($data)) {
            throw new \RuntimeException("Invalid plugin.json at: {$path}");
        }
        return new self(
            $data['id']          ?? '',
            $data['name']        ?? '',
            $data['version']     ?? '0.0.0',
            $data['description'] ?? '',
            $data['author']      ?? '',
            $data['url']         ?? '',
            $data['requires']    ?? '0.1.0',
            $data['hooks']       ?? [],
            $data['settings']    ?? []
        );
    }

    public function toArray(): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'version'     => $this->version,
            'description' => $this->description,
            'author'      => $this->author,
            'url'         => $this->url,
            'requires'    => $this->requires,
            'hooks'       => $this->hooks,
            'settings'    => $this->settings,
        ];
    }
}