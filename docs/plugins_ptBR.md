# Criando Plugins para o FlexCore

Esta página cobre tudo que você precisa para criar, configurar e distribuir um plugin para o FlexCore.

---

## Índice

- [O que é um plugin](#o-que-é-um-plugin)
- [Estrutura de arquivos](#estrutura-de-arquivos)
- [plugin.json — o manifesto](#pluginjson--o-manifesto)
- [Plugin.php — o código](#pluginphp--o-código)
- [Sistema de Hooks](#sistema-de-hooks)
  - [Actions (fire and forget)](#actions-fire-and-forget)
  - [Filters (transformar valores)](#filters-transformar-valores)
  - [Referência completa de hooks](#referência-completa-de-hooks)
- [Acessando o banco de dados](#acessando-o-banco-de-dados)
- [Lendo configurações do plugin](#lendo-configurações-do-plugin)
- [Adicionando configurações ao painel](#adicionando-configurações-ao-painel)
- [Instalação e desinstalação](#instalação-e-desinstalação)
- [Distribuindo seu plugin](#distribuindo-seu-plugin)
- [Plugins de exemplo](#plugins-de-exemplo)

---

## O que é um plugin

Um plugin é uma pasta dentro de `plugins/` que contém pelo menos dois arquivos:

- `plugin.json` — manifesto com metadados e configurações
- `Plugin.php` — código PHP que implementa a interface `PluginInterface`

O FlexCore carrega todos os plugins ativos na inicialização e chama `boot()` em cada um. É dentro do `boot()` que você registra seus hooks — sem modificar nenhum arquivo do core.

---

## Estrutura de arquivos

```
plugins/
  meu-plugin/
    plugin.json         ← obrigatório
    Plugin.php          ← obrigatório
    views/              ← opcional: templates PHP
      settings.php
    assets/             ← opcional: JS e CSS extras
      meu-plugin.js
      meu-plugin.css
    README.md           ← recomendado
```

> **Importante:** o nome da pasta vira o `plugin_id`. Use apenas letras minúsculas, números e hífens: `meu-plugin`, `slack-notifier`, `export-csv`.

---

## plugin.json — o manifesto

```json
{
  "id":          "meu-plugin",
  "name":        "Meu Plugin",
  "version":     "1.0.0",
  "description": "Descrição curta do que o plugin faz.",
  "author":      "Seu Nome",
  "url":         "https://github.com/seu-usuario/meu-plugin",
  "requires":    "0.1.0",
  "hooks": [
    "record.created",
    "record.updated"
  ],
  "settings": [
    {
      "key":      "webhook_url",
      "type":     "url",
      "label":    "URL do Webhook",
      "required": true,
      "hint":     "Para onde enviar os dados"
    },
    {
      "key":      "secret",
      "type":     "text",
      "label":    "Chave secreta",
      "required": false,
      "hint":     "Opcional — enviado no header X-Secret"
    },
    {
      "key":      "ativo",
      "type":     "select",
      "label":    "Modo",
      "options":  ["producao", "teste"],
      "required": false
    },
    {
      "key":      "notas",
      "type":     "textarea",
      "label":    "Notas internas",
      "required": false
    }
  ]
}
```

### Campos do manifesto

| Campo | Tipo | Descrição |
|---|---|---|
| `id` | string | Identificador único. Deve ser igual ao nome da pasta. |
| `name` | string | Nome legível exibido no painel. |
| `version` | string | Versão semântica: `1.0.0`. |
| `description` | string | Texto exibido no card do plugin no painel. |
| `author` | string | Seu nome ou organização. |
| `url` | string | Link para o repositório ou documentação. |
| `requires` | string | Versão mínima do FlexCore necessária. |
| `hooks` | array | Lista declarativa dos hooks usados (informativo, não obrigatório). |
| `settings` | array | Campos de configuração renderizados automaticamente no painel. |

### Tipos de campo em `settings`

| Tipo | Renderiza |
|---|---|
| `text` | `<input type="text">` |
| `url` | `<input type="url">` |
| `email` | `<input type="email">` |
| `password` | `<input type="password">` |
| `number` | `<input type="number">` |
| `textarea` | `<textarea>` |
| `select` | `<select>` com as `options` declaradas |

---

## Plugin.php — o código

```php
<?php

namespace MeuPlugin; // PascalCase do nome da pasta (meu-plugin → MeuPlugin)

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
        // Registre seus hooks aqui.
        // Este método é chamado uma vez na inicialização do FlexCore.

        Hooks::on('record.created', function (int $recordId, int $entityId, array $input) {
            // sua lógica aqui
        });
    }

    public function uninstall(): void
    {
        // Chamado quando o usuário clica em "Remover" no painel.
        // Limpe tabelas, settings ou arquivos criados pelo plugin.

        // Exemplo: remover tabela própria do plugin
        // DB::run('DROP TABLE IF EXISTS meu_plugin_logs');
    }
}
```

### Regras do namespace

O namespace é derivado do nome da pasta convertendo `kebab-case` para `PascalCase`:

| Pasta | Namespace |
|---|---|
| `meu-plugin` | `MeuPlugin` |
| `slack-notifier` | `SlackNotifier` |
| `export-csv` | `ExportCsv` |
| `webhook-sender` | `WebhookSender` |

---

## Sistema de Hooks

O FlexCore usa um sistema de eventos dividido em dois tipos: **Actions** e **Filters**.

### Actions (fire and forget)

Actions disparam um evento e não esperam retorno. Vários plugins podem ouvir o mesmo evento — todos são chamados.

```php
// Registrar um listener
Hooks::on('record.created', function (int $recordId, int $entityId, array $input) {
    // chamado toda vez que um registro for criado
});

// Com prioridade (menor número = executa primeiro, padrão = 10)
Hooks::on('record.created', function (...) { ... }, priority: 5);
```

### Filters (transformar valores)

Filters recebem um valor, podem modificá-lo, e **devem retorná-lo**. A cadeia de filters transforma o valor progressivamente.

```php
// Registrar um filter
Hooks::filter('api.response', function (array $response, array $entity): array {
    // adiciona campo extra em todas as respostas da API
    $response['data']['_version'] = '1.0';
    return $response; // obrigatório retornar
});
```

---

## Referência completa de hooks

### Actions de registros

| Hook | Quando dispara | Parâmetros |
|---|---|---|
| `record.before_create` | Antes de criar um registro | `(int $entityId, array $input)` |
| `record.created` | Após registro criado com sucesso | `(int $recordId, int $entityId, array $input)` |
| `record.before_update` | Antes de atualizar um registro | `(int $recordId, int $entityId, array $input)` |
| `record.updated` | Após registro atualizado | `(int $recordId, int $entityId, array $input)` |
| `record.before_delete` | Antes de excluir um registro | `(int $recordId, int $entityId)` |
| `record.deleted` | Após registro excluído | `(int $recordId, int $entityId)` |

```php
// Exemplo: logar toda criação de registro
Hooks::on('record.created', function (int $recordId, int $entityId, array $input) {
    $entity = DB::one('SELECT name FROM entities WHERE id = ?', [$entityId]);
    error_log("[MeuPlugin] Registro #{$recordId} criado em {$entity['name']}");
});

// Exemplo: bloquear exclusão de registros de uma entidade específica
Hooks::on('record.before_delete', function (int $recordId, int $entityId) {
    $entity = DB::one('SELECT slug FROM entities WHERE id = ?', [$entityId]);
    if ($entity['slug'] === 'contratos') {
        throw new \RuntimeException('Contratos não podem ser excluídos via API.');
    }
});
```

---

### Actions de entidades

| Hook | Quando dispara | Parâmetros |
|---|---|---|
| `entity.created` | Após entidade criada | `(int $entityId)` |
| `entity.updated` | Após entidade atualizada | `(int $entityId)` |
| `entity.deleted` | Após entidade excluída | `(int $entityId)` |

---

### Actions de plugins

| Hook | Quando dispara | Parâmetros |
|---|---|---|
| `plugin.loaded` | Após plugin ter seu `boot()` chamado | `(array $manifest)` |

---

### Filters da API

| Hook | Quando dispara | Parâmetros | Deve retornar |
|---|---|---|---|
| `api.response` | Antes de enviar qualquer resposta da API | `(array $response, array $entity)` | `array $response` |
| `api.record` | Antes de retornar um registro individual | `(array $record, array $entity)` | `array $record` |
| `api.list` | Antes de retornar lista de registros | `(array $records, array $entity)` | `array $records` |

```php
// Exemplo: adicionar campo calculado em todos os registros da API
Hooks::filter('api.record', function (array $record, array $entity): array {
    if ($entity['slug'] === 'pedidos') {
        $record['valor_com_frete'] = ($record['valor'] ?? 0) + 15.90;
    }
    return $record;
});

// Exemplo: injetar metadados customizados na resposta
Hooks::filter('api.response', function (array $response, array $entity): array {
    $response['_plugin_meta'] = [
        'processed_by' => 'meu-plugin',
        'timestamp'    => time(),
    ];
    return $response;
});

// Exemplo: retornar código HTTP customizado via filter
Hooks::filter('api.response', function (array $response, array $entity): array {
    if ($entity['slug'] === 'produtos' && empty($response['data'])) {
        http_response_code(204); // No Content em vez de 200 com array vazio
    }
    return $response;
});
```

---

### Filters de campos

| Hook | Quando dispara | Parâmetros | Deve retornar |
|---|---|---|---|
| `field.render` | Antes de renderizar o valor de um campo na UI | `(string $html, array $field, mixed $value)` | `string $html` |

```php
// Exemplo: formatar CPF automaticamente na exibição
Hooks::filter('field.render', function (string $html, array $field, mixed $value): string {
    if ($field['slug'] === 'cpf' && $value) {
        $cpf = preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $value);
        return '<span>' . htmlspecialchars($cpf) . '</span>';
    }
    return $html;
});
```

---

## Acessando o banco de dados

Use a classe `DB` diretamente — ela já está disponível globalmente.

> **Atenção: nomes reais dos métodos.** A API simplificada (`DB::query`, `DB::insert`, `DB::execute`, `DB::transaction`) é planejada para o futuro. Os métodos atuais são:
> - `DB::q($sql, $params)` — busca múltiplos registros
> - `DB::one($sql, $params)` — busca um registro (retorna `null` se não encontrado)
> - `DB::exec($sql, $params)` — INSERT → retorna o `lastInsertId`
> - `DB::run($sql, $params)` — UPDATE/DELETE → retorna `rowCount`

Use a classe `DB` diretamente — ela já está disponível globalmente:

```php
// Buscar um registro
$registro = DB::one('SELECT * FROM entity_records WHERE id = ?', [$recordId]);

// Buscar vários
$registros = DB::q('SELECT * FROM entity_records WHERE entity_id = ?', [$entityId]);

// Inserir (retorna o ID)
$novoId = DB::exec(
    'INSERT INTO minha_tabela_plugin (campo, valor) VALUES (?, ?)',
    ['chave', 'valor']
); // retorna lastInsertId

// Atualizar / deletar (retorna rows affected)
$afetados = DB::run(
    'UPDATE minha_tabela_plugin SET valor = ? WHERE campo = ?',
    ['novo_valor', 'chave']
); // retorna rowCount

// Transação (manual — não há método DB::transaction())
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

### Criando tabela própria no `boot()`

```php
public function boot(): void
{
    // Cria tabela do plugin se não existir
    DB::run("
        CREATE TABLE IF NOT EXISTS meu_plugin_logs (
            id         INT AUTO_INCREMENT PRIMARY KEY,
            record_id  INT         NOT NULL,
            entity_id  INT         NOT NULL,
            evento     VARCHAR(50) NOT NULL,
            payload    TEXT,
            created_at DATETIME    DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_record (record_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    // Hooks...
}
```

---

## Lendo configurações do plugin

As configurações salvas pelo usuário no painel ficam na coluna `settings` da tabela `plugins` como JSON.

```php
// Helper recomendado — copie para o seu Plugin.php
private function settings(): array
{
    $row = DB::one(
        "SELECT settings FROM plugins WHERE plugin_id = 'meu-plugin'",
    );
    return json_decode($row['settings'] ?? '{}', true) ?: [];
}

// Usando no boot()
public function boot(): void
{
    Hooks::on('record.created', function (int $recordId, int $entityId, array $input) {
        $cfg = $this->settings();
        $url = $cfg['webhook_url'] ?? '';

        if (!$url) return; // plugin não configurado ainda

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

## Adicionando configurações ao painel

Declare os campos em `settings[]` no `plugin.json`. O painel renderiza automaticamente um modal de configuração com esses campos quando o usuário clica em "⚙️ Config".

```json
{
  "settings": [
    {
      "key":      "api_key",
      "type":     "text",
      "label":    "API Key",
      "required": true,
      "hint":     "Encontre em: painel.servico.com/api-keys"
    },
    {
      "key":      "ambiente",
      "type":     "select",
      "label":    "Ambiente",
      "options":  ["producao", "sandbox"],
      "required": true
    },
    {
      "key":      "debug",
      "type":     "select",
      "label":    "Log de debug",
      "options":  ["nao", "sim"],
      "required": false
    }
  ]
}
```

Os valores são salvos via `POST /plugins/{id}/settings` e ficam disponíveis via `DB::one("SELECT settings FROM plugins WHERE plugin_id = ?")`.

---

## Instalação e desinstalação

### Instalando

1. Crie a pasta do plugin com `plugin.json` e `Plugin.php`
2. Compacte em um `.zip` (a raiz do zip deve conter os arquivos, não uma pasta extra)
3. No painel: **Plugins → ⬆️ Instalar Plugin → selecione o .zip**

```
# Estrutura correta do ZIP:
meu-plugin.zip
├── plugin.json       ← na raiz do zip
├── Plugin.php        ← na raiz do zip
└── README.md

# Estrutura ERRADA (pasta extra dentro):
meu-plugin.zip
└── meu-plugin/
    ├── plugin.json
    └── Plugin.php
```

### Desinstalando

O painel chama `Plugin::uninstall()` antes de remover os arquivos. Use para fazer limpeza:

```php
public function uninstall(): void
{
    // Remove tabela própria
    DB::run('DROP TABLE IF EXISTS meu_plugin_logs');

    // Remove settings do banco (já feito automaticamente pelo core,
    // mas se tiver dados em outras tabelas, limpe aqui)
}
```

---

## Distribuindo seu plugin

### Estrutura recomendada do repositório

```
meu-plugin/
├── plugin.json
├── Plugin.php
├── README.md
├── CHANGELOG.md
└── .gitignore
```

### Empacotando para distribuição

```bash
# Na pasta do plugin
zip -r meu-plugin-1.0.0.zip . --exclude "*.git*" --exclude ".DS_Store"
```

### Checklist antes de publicar

- [ ] `plugin.json` tem todos os campos obrigatórios (`id`, `name`, `version`, `requires`)
- [ ] O `id` no `plugin.json` é igual ao nome da pasta
- [ ] O namespace em `Plugin.php` é o PascalCase do `id`
- [ ] `boot()` verifica se as configurações necessárias existem antes de agir
- [ ] `uninstall()` limpa tudo que o plugin criou
- [ ] Testado com o plugin ativo e inativo
- [ ] Testado install e uninstall sem deixar resíduos

---

## Plugins de exemplo

### 1. Webhook Simples

Envia um POST para uma URL configurável toda vez que um registro for criado em qualquer entidade.

**`plugin.json`**
```json
{
  "id":          "webhook-simples",
  "name":        "Webhook Simples",
  "version":     "1.0.0",
  "description": "Envia um webhook quando registros são criados.",
  "author":      "Seu Nome",
  "url":         "",
  "requires":    "0.1.0",
  "hooks":       ["record.created"],
  "settings": [
    { "key": "url",    "type": "url",  "label": "URL do Webhook", "required": true },
    { "key": "secret", "type": "text", "label": "Secret",          "required": false }
  ]
}
```

**`Plugin.php`**
```php
<?php

namespace WebhookSimples;

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
        $row = DB::one("SELECT settings FROM plugins WHERE plugin_id = 'webhook-simples'");
        return json_decode($row['settings'] ?? '{}', true) ?: [];
    }
}
```

---

### 2. Campo Calculado (Filter)

Adiciona um campo `total_com_imposto` calculado automaticamente em todos os registros da entidade `pedidos` retornados pela API.

**`Plugin.php`**
```php
<?php

namespace CampoCalculado;

class Plugin
{
    public function manifest(): array
    {
        return json_decode(file_get_contents(__DIR__ . '/plugin.json'), true);
    }

    public function boot(): void
    {
        Hooks::filter('api.record', function (array $record, array $entity): array {
            if ($entity['slug'] !== 'pedidos') return $record;

            $valor  = (float) ($record['valor']   ?? 0);
            $frete  = (float) ($record['frete']   ?? 0);
            $imposto = 0.12; // 12%

            $record['total_com_imposto'] = round(($valor + $frete) * (1 + $imposto), 2);
            return $record;
        });
    }

    public function uninstall(): void {}
}
```

---

### 3. Logger de Auditoria Extra

Grava um log detalhado de todas as operações em uma tabela própria do plugin.

**`Plugin.php`**
```php
<?php

namespace AuditoriaExtra;

class Plugin
{
    public function manifest(): array
    {
        return json_decode(file_get_contents(__DIR__ . '/plugin.json'), true);
    }

    public function boot(): void
    {
        // Garante que a tabela existe
        DB::run("
            CREATE TABLE IF NOT EXISTS auditoria_extra_logs (
                id         INT AUTO_INCREMENT PRIMARY KEY,
                evento     VARCHAR(50) NOT NULL,
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
            'INSERT INTO auditoria_extra_logs (evento, record_id, entity_id, user_id, ip) VALUES (?,?,?,?,?)',
            [$ev, $rid, $eid, $_SESSION['user_id'] ?? null, $_SERVER['REMOTE_ADDR'] ?? null]
        );

        Hooks::on('record.created', fn($rid, $eid, $i) => $log('created', $rid, $eid));
        Hooks::on('record.updated', fn($rid, $eid, $i) => $log('updated', $rid, $eid));
        Hooks::on('record.deleted', fn($rid, $eid)     => $log('deleted', $rid, $eid));
    }

    public function uninstall(): void
    {
        DB::run('DROP TABLE IF EXISTS auditoria_extra_logs');
    }
}
```

---

## Dúvidas e contribuições

- Abra uma [issue no GitHub](https://github.com/sancal/flexcore/issues) para reportar bugs ou pedir novos hooks
- PRs com novos plugins de exemplo são bem-vindos em `plugins/`
- Para sugestões de hooks novos, abra uma issue com o label `hooks`

---

*FlexCore — [github.com/sancal/flexcore](https://github.com/sancal/flexcore)*