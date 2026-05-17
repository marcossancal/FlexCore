# FlexCore — Arquitetura do Sistema

## Visão Geral

FlexCore é um framework PHP para construção de sistemas de gestão de dados (CMS/CRUD) totalmente dinâmicos. A ideia central é simples: o administrador define **Entidades** (como tabelas com nome e campos configuráveis) e o sistema gera automaticamente interfaces de listagem, formulário, API REST e automações — sem escrever código.

A extensibilidade é feita via **sistema de plugins** com hooks de ação e filtro, permitindo adicionar comportamentos, rotas, campos e UI sem tocar no core.

---

## Estrutura de Diretórios

```
flex_system/
│
├── index.php                  ← Entry point único da aplicação
├── router.php                 ← Arquivo auxiliar (ponto de entrada alternativo)
├── .env                       ← Variáveis de ambiente (DB, DEBUG, ADMIN_PATH)
├── .env.example               ← Template de configuração
├── .installed                 ← Sentinel file: instalação concluída
├── .htaccess                  ← Reescrita de URL para index.php
│
├── config/
│   ├── bootstrap.php          ← Inicialização: .env, autoloader, aliases, helpers, plugins, automations
│   ├── container.php          ← Bindings do DI Container (repositórios, serviços, automations)
│   └── routes.php             ← Mapa central de rotas (admin + API REST)
│
├── core/
│   ├── Container/
│   │   └── Container.php      ← DI Container com autowiring via Reflection
│   ├── Hooks/
│   │   ├── HookDispatcher.php ← Engine do sistema de hooks (actions + filters, estático + instância)
│   │   └── Hooks.php          ← Alias estático para HookDispatcher (API pública para plugins)
│   └── Router/
│       ├── Router.php         ← HTTP Router com suporte a parâmetros de rota e middlewares
│       ├── Route.php          ← Representação de uma rota com matching e execução
│       ├── Request.php        ← Abstração do request HTTP (headers, body, bearer token)
│       └── MiddlewareInterface.php ← Contrato para middlewares de rota
│
├── lib/
│   ├── DB.php                 ← PDO singleton wrapper com helpers (q, one, exec, run, setting)
│   ├── Auth.php               ← Gerenciamento de sessão e autenticação
│   └── helpers.php            ← Funções globais: __, url(), view(), renderFieldValue(), allFieldTypes()...
│
├── app/
│   ├── Controllers/           ← Controllers da interface administrativa (web)
│   │   ├── AuthController.php
│   │   ├── DashboardController.php
│   │   ├── EntityController.php    ← CRUD de entidades e campos
│   │   ├── RecordController.php    ← CRUD de registros por entidade
│   │   ├── SettingsController.php  ← Configurações globais e gestão de usuários
│   │   ├── ApiKeyController.php    ← Gestão de chaves de API
│   │   ├── AutomationController.php
│   │   └── PluginController.php    ← Instalação, ativação e configuração de plugins
│   ├── Repositories/          ← Acesso a dados (padrão Repository)
│   │   ├── BaseRepository.php
│   │   ├── RecordRepository.php
│   │   ├── FieldRepository.php
│   │   ├── EntityRepository.php
│   │   └── AutomationRepository.php
│   ├── Services/
│   │   ├── RecordService.php       ← Orquestra ciclo de vida de registros + dispara hooks
│   │   └── AuditService.php        ← Log de auditoria de operações
│   └── views/                 ← Templates PHP (layout, entities, records, plugins, api, auth...)
│
├── api/
│   ├── Controllers/
│   │   └── ApiRecordController.php ← REST API: entities, records (CRUD), paginação, filtros
│   ├── Formatters/
│   │   └── ApiResponse.php         ← Formata respostas JSON padronizadas
│   └── Middleware/
│       └── ApiAuthMiddleware.php   ← Valida API key + rate limiting com janela deslizante
│
├── modules/
│   ├── Plugins/
│   │   ├── PluginInterface.php     ← Contrato que todo plugin deve implementar
│   │   ├── PluginLoader.php        ← Descobre, valida e inicializa plugins ativos
│   │   └── PluginManifest.php      ← Value object imutável com metadados do plugin
│   └── Automations/
│       ├── AutomationEngine.php    ← Escuta hooks de records e despacha automações configuradas
│       ├── ActionHandlerInterface.php ← Contrato para handlers de ação (ex: webhook)
│       └── Actions/
│           └── WebhookAction.php   ← Ação nativa: dispara webhook HTTP
│
├── plugins/                   ← Plugins instalados (cada um em seu próprio diretório)
│   └── meu-plugin/
│       ├── plugin.json        ← Manifesto do plugin
│       └── Plugin.php         ← Implementação de PluginInterface
│
├── install/
│   ├── index.php              ← Wizard de instalação (configura .env, cria tabelas, admin)
│   └── schema.sql             ← DDL completo do banco de dados
│
├── translates/                ← Arquivos de tradução JSON
│   ├── pt_BR.json
│   ├── en_US.json
│   ├── es.json
│   ├── de.json
│   └── fr.json
│
└── tests/
    ├── run.php
    ├── Feature/
    └── Unit/
```

---

## Camadas da Arquitetura

### 1. Entry Point (`index.php`)

Única porta de entrada da aplicação. Responsabilidades fixas e documentadas:

1. Define constantes globais (`BASE`, `APP_VERSION`)
2. Carrega o bootstrap
3. Configura CORS
4. Guarda de instalação (redireciona para `/install/` se necessário)
5. Testa conexão com banco
6. Carrega traduções com base no usuário ou configuração global
7. Guarda de autenticação (redireciona para login se não autenticado)
8. Instancia o Router, carrega as rotas e faz dispatch

### 2. Bootstrap (`config/bootstrap.php`)

Responsável por preparar o ambiente antes do dispatch:

- Lê o arquivo `.env` e popula `$_ENV`
- Configura `display_errors` conforme `DEBUG`
- Registra o **PSR-4 autoloader** manual que mapeia namespaces `FlexCore\*` para diretórios
- Cria aliases globais `DB` e `Auth` via `class_alias()`
- Define `BASE_PATH` (subpasta da aplicação) e `ADMIN_PATH` (prefixo do painel)
- Inicializa o DI Container via `config/container.php`
- Instancia e executa o `PluginLoader` (carrega plugins ativos do banco)
- Inicializa o `AutomationEngine` (registra listeners nos hooks de records)

### 3. DI Container (`core/Container/Container.php`)

Container de injeção de dependência singleton com:

- `bind()` — registra interface → implementação concreta
- `singleton()` — mesma coisa, mas cacheia a primeira instância
- `instance()` — registra uma instância já construída
- `make()` — resolve a dependência (com autowiring via `ReflectionClass`)

O autowiring inspeciona o construtor via Reflection e resolve recursivamente as dependências de tipo declaradas.

### 4. Sistema de Hooks (`core/Hooks/`)

Motor de eventos do sistema. Dois modos de uso:

**Actions** — fire-and-forget, múltiplos listeners, sem retorno:
```php
Hooks::on('record.created', function(int $id, int $entityId, array $input) { ... });
Hooks::fire('record.created', [$id, $entityId, $input]);
```

**Filters** — transforma um valor, cada listener recebe e retorna:
```php
Hooks::filter('api.response', function(array $response, array $entity): array {
    $response['custom'] = 'valor';
    return $response;
});
$result = Hooks::applyFilter('api.response', $originalData, [$entity]);
```

Prioridade numérica (menor = executa primeiro, padrão: 10):
```php
Hooks::on('record.created', $fn, priority: 5); // executa antes dos de prioridade 10
```

A classe `Hooks` é um alias estático para `HookDispatcher::*Static()`. Para testes unitários, use `HookDispatcher` como instância isolada.

### 5. Router (`core/Router/`)

Router HTTP simples com suporte a:

- Métodos: GET, POST, PUT, DELETE, ANY
- Parâmetros de rota: `/e/{slug}/{id}`
- Middlewares encadeados por rota: `$router->get(...)->middleware(new ApiAuthMiddleware())`
- Base path configurável (para subpastas)

O dispatch percorre as rotas registradas, faz match do método + URI, extrai parâmetros e chama o handler `[Controller::class, 'método']`.

### 6. Banco de Dados (`lib/DB.php`)

PDO singleton com helpers convenientes:

| Método | Retorno | Uso |
|---|---|---|
| `DB::q($sql, $params)` | `array` | SELECT múltiplas linhas |
| `DB::one($sql, $params)` | `?array` | SELECT uma linha |
| `DB::exec($sql, $params)` | `int` | INSERT → retorna `lastInsertId()` |
| `DB::run($sql, $params)` | `int` | UPDATE/DELETE → retorna `rowCount()` |
| `DB::setting($key, $default)` | `string` | Lê configuração (cache em memória por request) |
| `DB::setSetting($key, $val)` | `void` | Grava configuração (invalida cache) |

Configurado via `.env`: `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`.

### 7. Modelo de Dados

O banco usa um modelo **EAV (Entity-Attribute-Value)** tipado:

```
entities          → define tipos de objeto (ex: "Clientes", "Pedidos")
entity_fields     → define os campos de cada entidade (nome, tipo, opções)
entity_records    → cada registro pertence a uma entidade
record_values     → valores dos campos, separados por tipo de coluna:
                      val_text  (MEDIUMTEXT) — texto, JSON, base64
                      val_num   (DECIMAL 18,4) — números, moeda, percentual
                      val_date  (DATETIME) — datas e datetimes
```

Isso permite criar qualquer estrutura de dados dinamicamente sem alterar o schema.

### 8. Sistema de Entidades e Campos

O `EntityController` gerencia entidades e seus campos. Cada campo tem:

- `field_type` — um dos 29 tipos suportados (text, number, date, select, relation, image, formula...)
- `options_json` — configuração extra (opções de select, tamanho máximo de arquivo, expressão de fórmula)
- `required`, `show_in_list`, `position` — comportamento na UI

O hook `field.types` permite a plugins registrar tipos de campo adicionais.

### 9. Ciclo de Vida de um Registro (`RecordService`)

```
RecordController::store()
  → RecordService::create()
    → assertRequired()            — valida campos obrigatórios
    → Hooks::fire('record.before_create')
    → RecordRepository::create()  — insere entity_record
    → saveValues()                — salva valores campo a campo
      → [1ª passagem] todos os campos não-formula
      → [2ª passagem] resolve formulas com valores já salvos
    → AuditService::log()
    → Hooks::fire('record.created')
      → AutomationEngine::dispatch() — avalia e executa automações configuradas
```

### 10. API REST (`api/`)

Rotas `/api/v1/*` são separadas do painel admin. Autenticação via `ApiAuthMiddleware`:

1. Extrai token do header `Authorization: Bearer {key}` ou `?api_key=`
2. Busca no banco pelo hash SHA-256 da chave
3. Verifica validade e expiração
4. Aplica rate limiting com janela deslizante de 60s (tabela `api_key_hits`)
5. Injeta a chave no contexto do request para uso posterior

Resposta padronizada via `ApiResponse`:
```json
{
  "data": [...],
  "meta": { "total": 42, "page": 1, "per_page": 25 },
  "errors": null
}
```

### 11. Automações (`modules/Automations/`)

Sistema de automações configuráveis via interface:

- **Trigger**: evento (`on_create`, `on_update`, `on_delete`) + entidade + condições
- **Condições**: avaliam campos do input (`eq`, `neq`, `gt`, `lt`, `contains`, `not_empty`, `empty`)
- **Action**: tipo de ação (`webhook` nativo, extensível por plugins)

O `AutomationEngine` se registra nos hooks `record.created/updated/deleted` durante o boot e despacha as regras configuradas no banco.

### 12. Sistema de Plugins

Ver documentação dedicada em `PLUGINS.md`.

### 13. Internacionalização

Suporte a múltiplos idiomas via arquivos JSON em `/translates/`:

- Função `__('chave.aninhada', ['var' => 'valor'])` para tradução
- Idioma resolvido por: preferência do usuário → configuração global → fallback `pt_BR`
- Hook `translations.loaded` permite plugins injetar suas próprias traduções

### 14. Autenticação e Autorização

- Sessão PHP gerenciada por `Auth`
- Papéis: `admin`, `editor`, `viewer`
- Permissões granulares por entidade na tabela `entity_permissions`
- Rotas públicas configuráveis via hook `public_routes.match`
- API REST usa autenticação separada por chave (sem sessão)

---

## Fluxo Completo de uma Requisição

```
HTTP Request
    │
    ▼
index.php
    ├── bootstrap.php (env, autoloader, DB, Auth, plugins, automations)
    ├── CORS headers
    ├── Installation guard
    ├── DB connection test
    ├── Language loader
    ├── Auth guard (sessão ou API key via middleware)
    │
    ▼
Router::dispatch()
    ├── Match method + URI → Route
    ├── Run middlewares (ex: ApiAuthMiddleware)
    │
    ▼
Controller::método($params)
    ├── [Web] Auth::require() → valida papel
    ├── Lê input via Request / $_POST / $_GET
    ├── Chama Service ou Repository
    │
    ▼
Service (ex: RecordService)
    ├── Valida dados
    ├── Hooks::fire('record.before_create')
    ├── Repository::create()
    ├── Hooks::fire('record.created')
    │       └── AutomationEngine::dispatch()
    │               └── WebhookAction::execute()
    │
    ▼
Response
    ├── [Web] view() → include PHP template
    └── [API] ApiResponse::ok() → JSON
```

---

## Princípios de Design

- **Sem framework externo** — PHP puro, sem Composer obrigatório no core
- **PSR-4** — autoloading manual compatível com a convenção de namespaces
- **Open/Closed** — core não é modificado para adicionar funcionalidades; extensão via plugins e hooks
- **Repository Pattern** — acesso a dados isolado dos controllers
- **Service Layer** — lógica de negócio em Services, não em Controllers
- **EAV tipado** — flexibilidade de schema sem sacrificar performance nas queries de tipos
- **Hook-first** — qualquer ponto extensível do sistema expõe um hook antes de ser implementado