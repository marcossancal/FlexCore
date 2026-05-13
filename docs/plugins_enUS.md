# Creating Plugins for FlexCore

This page covers everything you need to create, configure, and distribute a plugin for FlexCore.

---

## Table of Contents

* [What is a plugin](#what-is-a-plugin)
* [File structure](#file-structure)
* [plugin.json — the manifest](#pluginjson--the-manifest)
* [Plugin.php — the code](#pluginphp--the-code)
* [Hook System](#hook-system)

  * [Actions (fire and forget)](#actions-fire-and-forget)
  * [Filters (transform-values)](#filters-transform-values)
  * [Complete hook reference](#complete-hook-reference)
* [Accessing the database](#accessing-the-database)
* [Reading plugin settings](#reading-plugin-settings)
* [Adding settings to the admin panel](#adding-settings-to-the-admin-panel)
* [Installation and uninstallation](#installation-and-uninstallation)
* [Distributing your plugin](#distributing-your-plugin)
* [Example plugins](#example-plugins)

---

## What is a plugin

A plugin is a folder inside `plugins/` that contains at least two files:

* `plugin.json` — manifest with metadata and settings
* `Plugin.php` — PHP code implementing the `PluginInterface`

FlexCore loads all active plugins during startup and calls `boot()` on each one. Inside `boot()` is where you register your hooks — without modifying any core files.

---

## File structure

```txt
plugins/
  my-plugin/
    plugin.json         ← required
    Plugin.php          ← required
    views/              ← optional: PHP templates
      settings.php
    assets/             ← optional: extra JS and CSS
      my-plugin.js
      my-plugin.css
    README.md           ← recommended
```

> **Important:** the folder name becomes the `plugin_id`. Use only lowercase letters, numbers, and hyphens: `my-plugin`, `slack-notifier`, `export-csv`.

---

## plugin.json — the manifest

```json
{
  "id":          "my-plugin",
  "name":        "My Plugin",
  "version":     "1.0.0",
  "description": "Short description of what the plugin does.",
  "author":      "Your Name",
  "url":         "https://github.com/your-user/my-plugin",
  "requires":    "0.1.0",
  "hooks": [
    "record.created",
    "record.updated"
  ],
  "settings": [
    {
      "key":      "webhook_url",
      "type":     "url",
      "label":    "Webhook URL",
      "required": true,
      "hint":     "Where to send the data"
    },
    {
      "key":      "secret",
      "type":     "text",
      "label":    "Secret Key",
      "required": false,
      "hint":     "Optional — sent in the X-Secret header"
    },
    {
      "key":      "active",
      "type":     "select",
      "label":    "Mode",
      "options":  ["production", "test"],
      "required": false
    },
    {
      "key":      "notes",
      "type":     "textarea",
      "label":    "Internal Notes",
      "required": false
    }
  ]
}
```

### Manifest fields

| Field         | Type   | Description                                                   |
| ------------- | ------ | ------------------------------------------------------------- |
| `id`          | string | Unique identifier. Must match the folder name.                |
| `name`        | string | Human-readable name displayed in the panel.                   |
| `version`     | string | Semantic version: `1.0.0`.                                    |
| `description` | string | Text displayed on the plugin card in the panel.               |
| `author`      | string | Your name or organization.                                    |
| `url`         | string | Link to the repository or documentation.                      |
| `requires`    | string | Minimum required FlexCore version.                            |
| `hooks`       | array  | Declarative list of hooks used (informational, not required). |
| `settings`    | array  | Configuration fields automatically rendered in the panel.     |

### Field types in `settings`

| Type       | Renders                            |
| ---------- | ---------------------------------- |
| `text`     | `<input type="text">`              |
| `url`      | `<input type="url">`               |
| `email`    | `<input type="email">`             |
| `password` | `<input type="password">`          |
| `number`   | `<input type="number">`            |
| `textarea` | `<textarea>`                       |
| `select`   | `<select>` with declared `options` |

---

## Plugin.php — the code

```php
<?php

namespace MyPlugin; // PascalCase version of the folder name (my-plugin → MyPlugin)

class Plugin
{
    public function manifest(): array
    {
        return json_decode(
            file_get_contents(__DIR__ . '/plugin.json'),
            true
        );
    }

    public function boot(): void
    {
        // Register your hooks here.
        // This method is called once during FlexCore startup.

        Hooks::on('record.created', function (int $recordId, int $entityId, array $input) {
            // your logic here
        });
    }

    public function uninstall(): void
    {
        // Called when the user clicks "Remove" in the panel.
        // Clean up tables, settings, or files created by the plugin.

        // Example: remove plugin-specific table
        // DB::run('DROP TABLE IF EXISTS my_plugin_logs');
    }
}
```

### Namespace rules

The namespace is derived from the folder name by converting `kebab-case` to `PascalCase`:

| Folder           | Namespace       |
| ---------------- | --------------- |
| `my-plugin`      | `MyPlugin`      |
| `slack-notifier` | `SlackNotifier` |
| `export-csv`     | `ExportCsv`     |
| `webhook-sender` | `WebhookSender` |

---

## Hook System

FlexCore uses an event system divided into two types: **Actions** and **Filters**.

### Actions (fire and forget)

Actions trigger an event and do not expect a return value. Multiple plugins can listen to the same event — all of them will be called.

```php
// Register a listener
Hooks::on('record.created', function (int $recordId, int $entityId, array $input) {
    // called every time a record is created
});

// With priority (lower number = executes first, default = 10)
Hooks::on('record.created', function (...) { ... }, priority: 5);
```

### Filters (transform values)

Filters receive a value, may modify it, and **must return it**. The filter chain progressively transforms the value.

```php
// Register a filter
Hooks::filter('api.response', function (array $response, array $entity): array {
    // add extra field to all API responses
    $response['data']['_version'] = '1.0';
    return $response; // required
});
```

---

## Complete hook reference

### Record actions

| Hook                   | Triggered when                         | Parameters                                     |
| ---------------------- | -------------------------------------- | ---------------------------------------------- |
| `record.before_create` | Before creating a record               | `(int $entityId, array $input)`                |
| `record.created`       | After a record is successfully created | `(int $recordId, int $entityId, array $input)` |
| `record.before_update` | Before updating a record               | `(int $recordId, int $entityId, array $input)` |
| `record.updated`       | After a record is updated              | `(int $recordId, int $entityId, array $input)` |
| `record.before_delete` | Before deleting a record               | `(int $recordId, int $entityId)`               |
| `record.deleted`       | After a record is deleted              | `(int $recordId, int $entityId)`               |

```php
// Example: log every record creation
Hooks::on('record.created', function (int $recordId, int $entityId, array $input) {
    $entity = DB::one('SELECT name FROM entities WHERE id = ?', [$entityId]);
    error_log("[MyPlugin] Record #{$recordId} created in {$entity['name']}");
});

// Example: block deletion of records from a specific entity
Hooks::on('record.before_delete', function (int $recordId, int $entityId) {
    $entity = DB::one('SELECT slug FROM entities WHERE id = ?', [$entityId]);
    if ($entity['slug'] === 'contracts') {
        throw new \RuntimeException('Contracts cannot be deleted via API.');
    }
});
```

---

### Entity actions

| Hook             | Triggered when             | Parameters        |
| ---------------- | -------------------------- | ----------------- |
| `entity.created` | After an entity is created | `(int $entityId)` |
| `entity.updated` | After an entity is updated | `(int $entityId)` |
| `entity.deleted` | After an entity is deleted | `(int $entityId)` |

---

### Plugin actions

| Hook            | Triggered when                           | Parameters          |
| --------------- | ---------------------------------------- | ------------------- |
| `plugin.loaded` | After the plugin has its `boot()` called | `(array $manifest)` |

---

### API filters

| Hook           | Triggered when                     | Parameters                         | Must return       |
| -------------- | ---------------------------------- | ---------------------------------- | ----------------- |
| `api.response` | Before sending any API response    | `(array $response, array $entity)` | `array $response` |
| `api.record`   | Before returning a single record   | `(array $record, array $entity)`   | `array $record`   |
| `api.list`     | Before returning a list of records | `(array $records, array $entity)`  | `array $records`  |

```php
// Example: add calculated field to all API records
Hooks::filter('api.record', function (array $record, array $entity): array {
    if ($entity['slug'] === 'orders') {
        $record['value_with_shipping'] = ($record['value'] ?? 0) + 15.90;
    }
    return $record;
});

// Example: inject custom metadata into the response
Hooks::filter('api.response', function (array $response, array $entity): array {
    $response['_plugin_meta'] = [
        'processed_by' => 'my-plugin',
        'timestamp'    => time(),
    ];
    return $response;
});

// Example: return custom HTTP status code via filter
Hooks::filter('api.response', function (array $response, array $entity): array {
    if ($entity['slug'] === 'products' && empty($response['data'])) {
        http_response_code(204); // No Content instead of 200 with empty array
    }
    return $response;
});
```

---

### Field filters

| Hook           | Triggered when                           | Parameters                                   | Must return    |
| -------------- | ---------------------------------------- | -------------------------------------------- | -------------- |
| `field.render` | Before rendering a field value in the UI | `(string $html, array $field, mixed $value)` | `string $html` |

```php
// Example: automatically format CPF for display
Hooks::filter('field.render', function (string $html, array $field, mixed $value): string {
    if ($field['slug'] === 'cpf' && $value) {
        $cpf = preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $value);
        return '<span>' . htmlspecialchars($cpf) . '</span>';
    }
    return $html;
});
```

---

## Accessing the database

Use the `DB` class directly — it is already globally available.

> **Attention: actual method names.** The simplified API (`DB::query`, `DB::insert`, `DB::execute`, `DB::transaction`) is planned for the future. The current methods are:
>
> * `DB::q($sql, $params)` — fetch multiple records
> * `DB::one($sql, $params)` — fetch a single record (returns `null` if not found)
> * `DB::exec($sql, $params)` — INSERT → returns `lastInsertId`
> * `DB::run($sql, $params)` — UPDATE/DELETE → returns `rowCount`

Use the `DB` class directly — it is already globally available:

```php
// Fetch one record
$record = DB::one('SELECT * FROM entity_records WHERE id = ?', [$recordId]);

// Fetch multiple records
$records = DB::q('SELECT * FROM entity_records WHERE entity_id = ?', [$entityId]);

// Insert (returns the ID)
$newId = DB::exec(
    'INSERT INTO my_plugin_table (field, value) VALUES (?, ?)',
    ['key', 'value']
); // returns lastInsertId

// Update / delete (returns affected rows)
$affected = DB::run(
    'UPDATE my_plugin_table SET value = ? WHERE field = ?',
    ['new_value', 'key']
); // returns rowCount

// Transaction (manual — there is no DB::transaction() method)
DB::get()->beginTransaction();
try {
    DB::run('UPDATE ...');
    DB::exec('INSERT ...');
    DB::get()->commit();
} catch (\Throwable $e) {
    DB::get()->rollBack();
    throw $e;
}
```

### Creating your own table in `boot()`

```php
public function boot(): void
{
    // Create plugin table if it does not exist
    DB::run("
        CREATE TABLE IF NOT EXISTS my_plugin_logs (
            id         INT AUTO_INCREMENT PRIMARY KEY,
            record_id  INT         NOT NULL,
            entity_id  INT         NOT NULL,
            event      VARCHAR(50) NOT NULL,
            payload    TEXT,
            created_at DATETIME    DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_record (record_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    // Hooks...
}
```

---

## Reading plugin settings

Settings saved by the user in the panel are stored in the `settings` column of the `plugins` table as JSON.

```php
// Recommended helper — copy into your Plugin.php
private function settings(): array
{
    $row = DB::one(
        "SELECT settings FROM plugins WHERE plugin_id = 'my-plugin'",
    );
    return json_decode($row['settings'] ?? '{}', true) ?: [];
}

// Using it in boot()
public function boot(): void
{
    Hooks::on('record.created', function (int $recordId, int $entityId, array $input) {
        $cfg = $this->settings();
        $url = $cfg['webhook_url'] ?? '';

        if (!$url) return; // plugin not configured yet

        $payload = json_encode([
            'record_id' => $recordId,
            'entity_id' => $entityId,
            'data'      => $input,
            'fired_at'  => date('c'),
        ]);

        $ctx = stream_context_create([
            'http' => [
                'method'  => 'POST',
                'header'  => "Content-Type: application/json\r\nX-Secret: " . ($cfg['secret'] ?? ''),
                'content' => $payload,
                'timeout' => 10,
            ],
        ]);

        @file_get_contents($url, false, $ctx);
    });
}
```

---

## Adding settings to the admin panel

Declare fields in `settings[]` inside `plugin.json`. The panel automatically renders a configuration modal with those fields when the user clicks on `"⚙️ Config"`.

```json
{
  "settings": [
    {
      "key":      "api_key",
      "type":     "text",
      "label":    "API Key",
      "required": true,
      "hint":     "Find it at: dashboard.service.com/api-keys"
    },
    {
      "key":      "environment",
      "type":     "select",
      "label":    "Environment",
      "options":  ["production", "sandbox"],
      "required": true
    },
    {
      "key":      "debug",
      "type":     "select",
      "label":    "Debug logging",
      "options":  ["no", "yes"],
      "required": false
    }
  ]
}
```

Values are saved via `POST /plugins/{id}/settings` and become available through `DB::one("SELECT settings FROM plugins WHERE plugin_id = ?")`.

---

## Installation and uninstallation

### Installing

1. Create the plugin folder with `plugin.json` and `Plugin.php`
2. Compress it into a `.zip` (the root of the zip must contain the files directly, not an extra folder)
3. In the panel: **Plugins → ⬆️ Install Plugin → select the .zip**

```txt
# Correct ZIP structure:
my-plugin.zip
├── plugin.json       ← at the root of the zip
├── Plugin.php        ← at the root of the zip
└── README.md

# WRONG structure (extra folder inside):
my-plugin.zip
└── my-plugin/
    ├── plugin.json
    └── Plugin.php
```

### Uninstalling

The panel calls `Plugin::uninstall()` before removing the files. Use it for cleanup:

```php
public function uninstall(): void
{
    // Remove custom table
    DB::run('DROP TABLE IF EXISTS my_plugin_logs');

    // Remove database settings (already done automatically by the core,
    // but if you have data in other tables, clean it here)
}
```

---

## Distributing your plugin

### Recommended repository structure

```txt
my-plugin/
├── plugin.json
├── Plugin.php
├── README.md
├── CHANGELOG.md
└── .gitignore
```

### Packaging for distribution

```bash
# Inside the plugin folder
zip -r my-plugin-1.0.0.zip . --exclude "*.git*" --exclude ".DS_Store"
```

### Checklist before publishing

* [ ] `plugin.json` contains all required fields (`id`, `name`, `version`, `requires`)
* [ ] The `id` in `plugin.json` matches the folder name
* [ ] The namespace in `Plugin.php` is the PascalCase version of the `id`
* [ ] `boot()` checks whether required settings exist before acting
* [ ] `uninstall()` cleans up everything created by the plugin
* [ ] Tested with the plugin enabled and disabled
* [ ] Tested install and uninstall without leaving leftovers

---

## Example plugins

### 1. Simple Webhook

Sends a POST request to a configurable URL every time a record is created in any entity.

**`plugin.json`**

```json
{
  "id":          "simple-webhook",
  "name":        "Simple Webhook",
  "version":     "1.0.0",
  "description": "Sends a webhook when records are created.",
  "author":      "Your Name",
  "url":         "",
  "requires":    "0.1.0",
  "hooks":       ["record.created"],
  "settings": [
    { "key": "url",    "type": "url",  "label": "Webhook URL", "required": true },
    { "key": "secret", "type": "text", "label": "Secret",      "required": false }
  ]
}
```

**`Plugin.php`**

```php
<?php

namespace SimpleWebhook;

class Plugin
{
    public function manifest(): array
    {
        return json_decode(file_get_contents(__DIR__ . '/plugin.json'), true);
    }

    public function boot(): void
    {
        Hooks::on('record.created', function (int $recordId, int $entityId, array $input) {
            $cfg = $this->settings();
            $url = $cfg['url'] ?? '';
            if (!$url) return;

            $payload = json_encode([
                'event'     => 'record.created',
                'record_id' => $recordId,
                'entity_id' => $entityId,
                'data'      => $input,
                'fired_at'  => date('c'),
            ]);

            $headers = "Content-Type: application/json\r\n";
            if (!empty($cfg['secret'])) {
                $headers .= "X-Signature: " . hash_hmac('sha256', $payload, $cfg['secret']) . "\r\n";
            }

            $ctx = stream_context_create([
                'http' => ['method' => 'POST', 'header' => $headers, 'content' => $payload, 'timeout' => 10],
            ]);
            @file_get_contents($url, false, $ctx);
        });
    }

    public function uninstall(): void {}

    private function settings(): array
    {
        $row = DB::one("SELECT settings FROM plugins WHERE plugin_id = 'simple-webhook'");
        return json_decode($row['settings'] ?? '{}', true) ?: [];
    }
}
```

---

### 2. Calculated Field (Filter)

Adds a `total_with_tax` field automatically to all records from the `orders` entity returned by the API.

**`Plugin.php`**

```php
<?php

namespace CalculatedField;

class Plugin
{
    public function manifest(): array
    {
        return json_decode(file_get_contents(__DIR__ . '/plugin.json'), true);
    }

    public function boot(): void
    {
        Hooks::filter('api.record', function (array $record, array $entity): array {
            if ($entity['slug'] !== 'orders') return $record;

            $value   = (float) ($record['value'] ?? 0);
            $shipping = (float) ($record['shipping'] ?? 0);
            $tax = 0.12; // 12%

            $record['total_with_tax'] = round(($value + $shipping) * (1 + $tax), 2);
            return $record;
        });
    }

    public function uninstall(): void {}
}
```

---

### 3. Extra Audit Logger

Stores a detailed log of all operations in a plugin-specific table.

**`Plugin.php`**

```php
<?php

namespace ExtraAudit;

class Plugin
{
    public function manifest(): array
    {
        return json_decode(file_get_contents(__DIR__ . '/plugin.json'), true);
    }

    public function boot(): void
    {
        // Ensure the table exists
        DB::run("
            CREATE TABLE IF NOT EXISTS extra_audit_logs (
                id         INT AUTO_INCREMENT PRIMARY KEY,
                event      VARCHAR(50) NOT NULL,
                record_id  INT,
                entity_id  INT,
                user_id    INT,
                ip         VARCHAR(45),
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_record (record_id),
                INDEX idx_entity (entity_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $log = fn(string $ev, int $rid, int $eid) => DB::exec(
            'INSERT INTO extra_audit_logs (event, record_id, entity_id, user_id, ip) VALUES (?,?,?,?,?)',
            [$ev, $rid, $eid, $_SESSION['user_id'] ?? null, $_SERVER['REMOTE_ADDR'] ?? null]
        );

        Hooks::on('record.created', fn($rid, $eid, $i) => $log('created', $rid, $eid));
        Hooks::on('record.updated', fn($rid, $eid, $i) => $log('updated', $rid, $eid));
        Hooks::on('record.deleted', fn($rid, $eid)     => $log('deleted', $rid, $eid));
    }

    public function uninstall(): void
    {
        DB::run('DROP TABLE IF EXISTS extra_audit_logs');
    }
}
```

---

## Questions and contributions

* Open an issue on [GitHub](https://github.com/sancal/flexcore/issues?utm_source=chatgpt.com) to report bugs or request new hooks
* PRs with new example plugins are welcome in `plugins/`
* For suggestions of new hooks, open an issue with the `hooks` label

---

*FlexCore — [github.com/sancal/flexcore](https://github.com/sancal/flexcore?utm_source=chatgpt.com)*
