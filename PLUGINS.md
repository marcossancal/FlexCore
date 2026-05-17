# FlexCore — Como Criar Plugins

## O que é um Plugin

Um plugin do FlexCore é um diretório dentro de `plugins/` contendo dois arquivos obrigatórios: um manifesto JSON e uma classe PHP. Nada mais é necessário para que o sistema reconheça e carregue o plugin.

Plugins **nunca modificam o core**. Toda extensão se dá via hooks (ações e filtros), registro de rotas, e injeção de elementos de UI. O core garante pontos de extensão; o plugin decide o que fazer com eles.

---

## Estrutura de um Plugin

```
plugins/
└── meu-plugin/
    ├── plugin.json        ← manifesto (obrigatório)
    ├── Plugin.php         ← classe principal (obrigatório)
    ├── views/             ← templates PHP (opcional)
    │   └── index.php
    ├── assets/            ← JS e CSS (opcional)
    │   ├── app.js
    │   └── style.css
    └── translates/        ← traduções do plugin (opcional, mas fortemente recomendado...)
        ├── pt_BR.json
        └── en_US.json
```

---

## 1. O Manifesto (`plugin.json`)

```json
{
  "id":          "meu-plugin",
  "name":        "Meu Plugin",
  "version":     "1.0.0",
  "description": "Descrição do que o plugin faz.",
  "author":      "Seu Nome",
  "url":         "https://seusite.com/meu-plugin",
  "requires":    "1.0.0",
  "namespace":   "MeuPlugin",
  "hooks": [
    "record.created",
    "api.response"
  ],
  "settings": [
    {
      "key":     "webhook_url",
      "label":   "URL do Webhook",
      "type":    "text",
      "default": ""
    },
    {
      "key":     "ativo",
      "label":   "Ativar notificações",
      "type":    "checkbox",
      "default": "1"
    }
  ]
}
```

### Campos do Manifesto

| Campo | Obrigatório | Descrição |
|---|---|---|
| `id` | ✅ | Identificador único (slug). Deve ser único no sistema. |
| `name` | ✅ | Nome legível exibido no painel. |
| `version` | ✅ | Versão do plugin (semver). |
| `description` | — | Descrição exibida na listagem de plugins. |
| `author` | — | Autor exibido na listagem. |
| `url` | — | Link para documentação ou repositório. |
| `requires` | ✅ | Versão mínima do FlexCore necessária. |
| `namespace` | ✅* | Namespace PHP da classe `Plugin`. Ex: `"MeuPlugin"` → classe `MeuPlugin\Plugin`. |
| `class` | — | Alternativa ao `namespace`: FQCN completo da classe. |
| `hooks` | — | Lista informativa dos hooks utilizados (documentação). |
| `settings` | — | Campos de configuração exibidos no painel do plugin. |

\* Se `namespace` e `class` forem omitidos, o sistema tenta inferir o namespace a partir do nome do diretório.

### Tipos de Settings

Os campos definidos em `settings` aparecem automaticamente na aba de configuração do plugin no painel. Os valores são salvos com `DB::setSetting()` e lidos com `DB::setting()`.

| Tipo | Renderização |
|---|---|
| `text` | Input de texto simples |
| `checkbox` | Checkbox booleano |
| `select` | Lista com `options` array |
| `textarea` | Área de texto longa |
| `password` | Input mascarado |

Ler um setting salvo pelo plugin:
```php
$url = DB::setting('meu-plugin.webhook_url', '');
```

---

## 2. A Classe Principal (`Plugin.php`)

```php
<?php

namespace MeuPlugin;

use FlexCore\Modules\Plugins\PluginInterface;
use FlexCore\Modules\Plugins\PluginManifest;
use FlexCore\Core\Hooks\Hooks;

class Plugin implements PluginInterface
{
    public function manifest(): PluginManifest
    {
        return PluginManifest::fromJson(__DIR__ . '/plugin.json');
    }

    public function boot(): void
    {
        // Tudo que o plugin faz é registrado aqui.
        // Este método é chamado uma vez, durante o bootstrap da aplicação.

        $this->registerHooks();
        $this->registerRoutes();
        $this->registerTranslations();
    }

    public function uninstall(): void
    {
        // Limpa qualquer coisa que o plugin criou:
        // tabelas, configurações, arquivos, etc.
        DB::run("DROP TABLE IF EXISTS meu_plugin_logs");
        DB::run("DELETE FROM settings WHERE skey LIKE 'meu-plugin.%'");
    }

    private function registerHooks(): void
    {
        // Registra listeners de ação e filtros aqui
    }

    private function registerRoutes(): void
    {
        // Registra rotas adicionais aqui
    }

    private function registerTranslations(): void
    {
        // Mescla traduções do plugin às do core
    }
}
```

### Contrato `PluginInterface`

```php
interface PluginInterface
{
    public function manifest(): PluginManifest;
    public function boot(): void;
    public function uninstall(): void;
}
```

- `manifest()` — retorna os metadados do plugin
- `boot()` — chamado uma vez no bootstrap; registre todos os hooks, rotas e bindings aqui
- `uninstall()` — chamado quando o usuário desinstala o plugin; limpe o que foi criado

---

## 3. Registrando Hooks

### Ações (fire-and-forget)

```php
// Executar algo quando um registro é criado
Hooks::on('record.created', function(int $recordId, int $entityId, array $input): void {
    // $recordId  — ID do registro recém-criado
    // $entityId  — ID da entidade
    // $input     — dados brutos do formulário

    // Exemplo: enviar notificação
    $url = \DB::setting('meu-plugin.webhook_url', '');
    if ($url) {
        // dispara webhook...
    }
});

// Prioridade: menor número executa primeiro (padrão: 10)
Hooks::on('record.created', $fn, priority: 5);
```

### Filtros (transforma valor)

```php
// Adicionar campos extras na resposta da API
Hooks::filter('api.response', function(array $response, array $entity): array {
    $response['plugin_data'] = ['extra' => 'valor'];
    return $response;
});

// Adicionar item de menu no sidebar
Hooks::filter('sidebar.nav_items', function(array $items): array {
    $items[] = [
        'label' => 'Meu Plugin',
        'url'   => url('meu-plugin'),
        'icon'  => '🔌',
    ];
    return $items;
});
```

---

## 4. Registrando Rotas

```php
Hooks::on('router.register', function($router): void {
    $router->get('/meu-plugin', [MeuPlugin\Controllers\MainController::class, 'index']);
    $router->post('/meu-plugin/save', [MeuPlugin\Controllers\MainController::class, 'save']);
});
```

O hook `router.register` é disparado ao final de `config/routes.php`, após o registro de todas as rotas do core. O router passado como argumento é a mesma instância usada pela aplicação.

**Rotas públicas (sem autenticação):**

```php
// Declarar rota como pública (sem login)
Hooks::filter('public_routes.match', function(bool $matched, string $path): bool {
    return $matched || strpos($path, '/meu-plugin/public') === 0;
});

// E registrar a rota normalmente
Hooks::on('router.register', function($router): void {
    $router->get('/meu-plugin/public', [PublicController::class, 'index']);
});
```

---

## 5. Estendendo Tipos de Campo

```php
Hooks::filter('field.types', function(array $types): array {
    $types['star_rating'] = [
        'icon'    => '⭐',
        'storage' => 'val_num',
    ];
    return $types;
});

// Renderização personalizada na listagem de registros
Hooks::filter('field.render_value', function(?string $html, array $field, mixed $val, bool $full): ?string {
    if ($field['field_type'] !== 'star_rating') return $html;
    $n = (int) $val;
    return str_repeat('⭐', $n) . str_repeat('☆', max(0, 5 - $n));
});

// Renderização personalizada no formulário de edição
Hooks::filter('field.render_form', function(?string $html, array $field, string $name, mixed $val, bool $required): ?string {
    if ($field['field_type'] !== 'star_rating') return $html;
    $req = $required ? 'required' : '';
    return "<input type='range' name='{$name}' min='1' max='5' value='" . (int)$val . "' {$req}>";
});

// Configuração extra na tela de edição de campos
Hooks::filter('field.render_config', function(string $html, array $ctx): string {
    if (($ctx['field']['field_type'] ?? '') !== 'star_rating') return $html;
    return $html . "<p>Tipo Star Rating: avaliação de 1 a 5 estrelas.</p>";
});
```

---

## 6. Estendendo a Interface de Listagem de Registros

```php
// Adicionar coluna personalizada na tabela de registros
Hooks::filter('records.list.columns.header', function(string $html, array $ctx): string {
    return $html . '<th>Status</th>';
});

Hooks::filter('records.list.columns.cell', function(string $html, array $ctx): string {
    $record = $ctx['record'];
    $status = computeStatus($record);
    return $html . "<td>{$status}</td>";
});

// Adicionar botão de ação na barra superior da listagem
Hooks::filter('records.list.actions', function(string $html, array $ctx): string {
    $entity = $ctx['entity'];
    return $html . "<a href='/meu-plugin/exportar/{$entity['slug']}' class='btn'>Exportar</a>";
});
```

---

## 7. Estendendo o Layout

```php
// Injetar CSS/JS no <head>
Hooks::filter('layout.head', function(string $html): string {
    return $html . '<link rel="stylesheet" href="' . url('plugins/meu-plugin/assets/style.css') . '">';
});

// Injetar scripts antes do </body>
Hooks::filter('layout.footer_scripts', function(string $html): string {
    return $html . '<script src="' . url('plugins/meu-plugin/assets/app.js') . '"></script>';
});
```

---

## 8. Adicionando Traduções

```php
Hooks::filter('translations.loaded', function(array $trans, string $lang): array {
    $file = __DIR__ . '/translates/' . $lang . '.json';
    if (!file_exists($file)) {
        $file = __DIR__ . '/translates/pt_BR.json';
    }
    $plugin = json_decode(file_get_contents($file), true) ?? [];
    return array_replace_recursive($trans, $plugin);
});
```

Estrutura do arquivo de tradução do plugin (`translates/pt_BR.json`):
```json
{
  "meu_plugin": {
    "titulo": "Meu Plugin",
    "mensagem": "Olá, :nome!"
  }
}
```

Uso na view:
```php
echo __('meu_plugin.titulo');
echo __('meu_plugin.mensagem', ['nome' => 'Roseli']);
```

---

## 9. Registrando Ações de Automação

```php
// No boot() do plugin, registrar um novo tipo de ação de automação
Hooks::on('automation.register_actions', function($engine): void {
    $engine->registerAction('enviar_email', new MeuPlugin\Actions\EnviarEmailAction());
});
```

A action deve implementar `ActionHandlerInterface`:
```php
class EnviarEmailAction implements \FlexCore\Modules\Automations\ActionHandlerInterface
{
    public function execute(array $config, int $recordId, int $entityId, array $input): void
    {
        $para     = $config['para'] ?? '';
        $assunto  = $config['assunto'] ?? 'Notificação';
        // ... enviar email
    }
}
```

---

## 10. Acessando Configurações do Plugin

Configurações definidas em `settings` do `plugin.json` são salvas com prefixo `{id}.{key}`:

```php
// Ler setting
$valor = \DB::setting('meu-plugin.webhook_url', '');

// Gravar setting programaticamente
\DB::setSetting('meu-plugin.webhook_url', 'https://exemplo.com/hook', 'URL do Webhook', 'meu-plugin');
```

---

## 11. Criando Tabelas no Banco

O plugin deve criar suas tabelas de forma idempotente, geralmente no `boot()`:

```php
public function boot(): void
{
    $this->ensureTables();
    // ...
}

private function ensureTables(): void
{
    \DB::run("
        CREATE TABLE IF NOT EXISTS meu_plugin_logs (
            id         INT AUTO_INCREMENT PRIMARY KEY,
            record_id  INT NOT NULL,
            mensagem   TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
}
```

No `uninstall()`, remova o que foi criado:
```php
public function uninstall(): void
{
    \DB::run("DROP TABLE IF EXISTS meu_plugin_logs");
    \DB::run("DELETE FROM settings WHERE skey LIKE 'meu-plugin.%'");
}
```

---

## 12. Instalação e Ativação

### Via painel (interface web)
1. Coloque o diretório do plugin em `plugins/`
2. Acesse **Painel → Plugins**
3. O plugin aparece na lista com botão "Ativar"
4. Após ativar, a coluna "Configurações" aparece se o plugin tiver `settings`

### Via ZIP (upload)
O painel de plugins suporta upload de `.zip` contendo o diretório do plugin.

### Via Registry (URL)
O painel suporta instalação direta por URL de um registry externo.

---

## 13. Boas Práticas

**Prefixe tudo:** tabelas, settings, classes e funções com o ID do plugin para evitar conflitos.
```php
// ✅ Correto
CREATE TABLE meu_plugin_logs ...
DB::setting('meu-plugin.api_key')

// ❌ Errado
CREATE TABLE logs ...
DB::setting('api_key')
```

**Nunca modifique arquivos do core.** Toda extensão via hooks.

**Verifique compatibilidade de versão.** O campo `requires` no manifesto garante que o plugin só carrega em versões compatíveis do FlexCore.

**Falhe silenciosamente.** O PluginLoader envolve `boot()` em try/catch. Exceções são logadas em `error_log` mas não derrubam a aplicação.

**Evite consultas no boot.** O `boot()` é executado em todos os requests. Consultas ao banco devem ser feitas apenas quando necessário (dentro dos callbacks de hooks, não no registro deles).

**Use `DB::setting()` com cache.** A primeira chamada carrega toda a tabela `settings` em memória. Chamadas subsequentes no mesmo request são instantâneas.

---

## Exemplo Completo: Plugin de Webhook Simples

```
plugins/
└── simple-webhook/
    ├── plugin.json
    └── Plugin.php
```

**`plugin.json`**
```json
{
  "id": "simple-webhook",
  "name": "Simple Webhook",
  "version": "1.0.0",
  "description": "Dispara um webhook quando um registro é criado.",
  "author": "FlexCore",
  "requires": "1.0.0",
  "namespace": "SimpleWebhook",
  "settings": [
    {
      "key": "url",
      "label": "URL do Webhook",
      "type": "text",
      "default": ""
    }
  ]
}
```

**`Plugin.php`**
```php
<?php

namespace SimpleWebhook;

use FlexCore\Modules\Plugins\PluginInterface;
use FlexCore\Modules\Plugins\PluginManifest;
use FlexCore\Core\Hooks\Hooks;

class Plugin implements PluginInterface
{
    public function manifest(): PluginManifest
    {
        return PluginManifest::fromJson(__DIR__ . '/plugin.json');
    }

    public function boot(): void
    {
        Hooks::on('record.created', function(int $recordId, int $entityId, array $input): void {
            $url = \DB::setting('simple-webhook.url', '');
            if (!$url) return;

            $payload = json_encode([
                'event'     => 'record.created',
                'record_id' => $recordId,
                'entity_id' => $entityId,
            ]);

            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $payload,
                CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 5,
            ]);
            curl_exec($ch);
            curl_close($ch);
        });
    }

    public function uninstall(): void
    {
        \DB::run("DELETE FROM settings WHERE skey LIKE 'simple-webhook.%'");
    }
}
```