# Arquitetura do FlexCore

Este documento descreve as decisões de design e a estrutura interna do FlexCore para desenvolvedores que desejam entender, modificar ou estender o sistema.

---

## Visão geral

O FlexCore segue um padrão **MVC estruturado** sem framework externo. Toda requisição HTTP entra por um único ponto (`index.php`), passa por guards de instalação e autenticação, e é despachada pelo Router para o Controller correto.

Existem dois caminhos distintos:

- **Rotas web** (`/e/{slug}`, `/entities`, etc.) — requerem sessão autenticada, respondem HTML.
- **Rotas de API** (`/api/v1/*`) — requerem API Key via Bearer token, respondem JSON. O guard de sessão é ignorado; o `ApiAuthMiddleware` é encadeado diretamente na rota.

```
HTTP Request
    │
    ▼
index.php (entry point)
    │
    ├─ config/bootstrap.php    (env, sessão, autoload de todas as classes)
    ├─ Guard de instalação      (.env + .installed existem?)
    ├─ Guard de DB              (DB::get() funciona?)
    ├─ Carrega idioma           (loadTranslations)
    ├─ Guard de autenticação    (ignorado para /api/v1/*)
    │
    ▼
config/routes.php → Router::dispatch()
    │
    ├─ Rota web ──────────────────────────────────────────┐
    │   Controller → Service → Repository → DB            │
    │   └─ view() → app/views/*.php → Response HTML       │
    │                                                      │
    └─ Rota de API (/api/v1/*) ───────────────────────────┘
        ApiAuthMiddleware (valida key + rate limit)
        └─ ApiRecordController → Service → Repository → DB
           └─ ApiResponse::ok() → Response JSON
```

---

## Camadas da aplicação

### Entry point — `index.php`

Responsabilidades estritas:
- Definir constantes globais (`BASE`, `APP_VERSION`)
- Incluir o bootstrap
- Executar os guards sequenciais (instalação → DB → idioma → auth)
- Instanciar o Router e despachar

O guard de autenticação verifica `Auth::check()` mas pula rotas cujo caminho começa com `/api/v1/` — essas usam autenticação própria por API Key.

---

### Bootstrap — `config/bootstrap.php`

Responsável por:
- Carregar o `.env` manualmente (sem biblioteca externa)
- Configurar `error_reporting` com base em `DEBUG`
- Iniciar a sessão
- Fazer `require_once` de todas as classes em ordem de dependência
- Definir `BASE_PATH` (para ambientes em subpasta)
- Instanciar o DI Container via `config/container.php`
- Inicializar o `PluginLoader` (apenas se `.installed` existe)
- Inicializar o `AutomationEngine` via Container

O carregamento é manual (`require_once` em cascata), sem PSR-4 autoload — decisão intencional para eliminar dependência do Composer.

---

### Container de DI — `core/Container/Container.php`

Container de injeção de dependência simples, implementado do zero. Padrão Singleton com `Container::getInstance()`.

**Bindings** são configurados em `config/container.php`:

```php
$container->singleton(RecordService::class, function($c) {
    return new RecordService(
        $c->make(RecordRepository::class),
        $c->make(FieldRepository::class),
        $c->make(EntityRepository::class),
        $c->make(AuditService::class),
    );
});
```

Suporta:
- `bind(abstract, factory)` — cria nova instância a cada `make()`
- `singleton(abstract, factory)` — cria e reutiliza a mesma instância

Controllers **não** são resolvidos pelo Container — são instanciados diretamente pelo `Route::call()` via `new $className()`. O Container é usado apenas para Services e Repositories.

---

### Router — `core/Router/`

Roteador HTTP leve com suporte a:
- Métodos `GET`, `POST`, `PUT`, `DELETE`, `any()`
- Parâmetros de rota `{slug}` extraídos por regex
- Middlewares encadeados por rota via `->middleware(...)`
- Todos os métodos de registro retornam a instância de `Route` (fluent interface)

O `Router` lê `BASE_PATH` para funcionar corretamente em subpastas.

**Registro de rota com middleware:**
```php
$router->get('/api/v1/e/{slug}', [ApiRecordController::class, 'index'])
       ->middleware(new ApiAuthMiddleware());
```

**Fluxo de despacho:**
```
Router::dispatch()
  → encontra rota pelo método + padrão regex
  → Route::call($params)
      → se há middlewares: cria Request com parâmetros de rota
        e executa a cadeia middleware → handler
      → se não há middlewares: chama handler diretamente com
        os parâmetros de rota como argumentos posicionais
```

O `Request` resolvido pelo middleware fica disponível em `$GLOBALS['_flexcore_request']` para que o controller de API acesse o contexto injetado (ex: `$request->context['api_key']`).

**`MiddlewareInterface`:**
```php
interface MiddlewareInterface {
    public function handle(Request $request, callable $next): void;
}
```

---

### Sistema de Hooks — `core/Hooks/HookDispatcher.php`

Implementa dois padrões de evento:

**Actions** — "fire and forget", múltiplos listeners, sem retorno:
```php
Hooks::on('record.created', function($id, $entityId, $input) { ... });
Hooks::fire('record.created', [$recordId, $entityId, $rawInput]);
```

**Filters** — transforma um valor passando por uma cadeia de transformadores:
```php
Hooks::filter('record.value', function($value, $field) { return strtoupper($value); });
$value = Hooks::applyFilter('record.value', $rawValue, [$field]);
```

Ambos suportam `$priority` (padrão: 10). Listeners de menor prioridade executam primeiro. `Hooks` é um alias de `HookDispatcher`.

**Hooks disponíveis no core:**

| Hook | Tipo | Quando dispara |
|------|------|----------------|
| `record.before_create` | Action | Antes de inserir o registro |
| `record.created` | Action | Após inserir e salvar valores |
| `record.before_update` | Action | Antes de atualizar |
| `record.updated` | Action | Após atualizar valores |
| `record.before_delete` | Action | Antes de deletar |
| `record.deleted` | Action | Após deletar |

---

### Biblioteca de acesso a dados — `lib/DB.php`

Wrapper estático sobre PDO. Conexão lazy via `DB::get()`.

Métodos principais:

| Método | Retorno | Uso |
|--------|---------|-----|
| `DB::q($sql, $params)` | `array` | SELECT múltiplas linhas |
| `DB::one($sql, $params)` | `array\|null` | SELECT uma linha |
| `DB::exec($sql, $params)` | `int` (lastInsertId) | INSERT |
| `DB::run($sql, $params)` | `int` (rowCount) | UPDATE / DELETE |
| `DB::setting($key, $default)` | `string` | Lê `settings` table |

Todas as queries usam prepared statements. Sem ORM, sem query builder — SQL direto.

---

### Modelo de dados

```
entities                        entity_fields
─────────                       ─────────────
id (PK)                         id (PK)
name, slug (unique)             entity_id (FK → entities)
icon, color, description        name, slug
position, active                field_type
api_responses (JSON)            options_json, relation_entity_id
created_by, created_at          required, show_in_list, position

entity_records                  record_values
──────────────                  ─────────────
id (PK)                         id (PK)
entity_id (FK → entities)       record_id (FK → entity_records)
created_by, created_at          field_id  (FK → entity_fields)
updated_at                      val_text, val_num, val_date
                                (UNIQUE: record_id + field_id)
```

O padrão EAV (Entity-Attribute-Value) em `record_values` permite campos dinâmicos sem alterar o schema. Três colunas de valor (`val_text`, `val_num`, `val_date`) para evitar conversões e melhorar performance em buscas numéricas e por data.

**Tipos de campo suportados (29 tipos)** — mapeados para a coluna de storage correta pelo `RecordRepository::saveValue()` via `isNumericType()` e `isDateType()` de `lib/helpers.php`:

| Grupo | Tipos | Coluna |
|-------|-------|--------|
| Texto e comunicação | `text`, `textarea`, `richtext`, `email`, `url`, `phone`, `password` | `val_text` |
| Números e valores | `number`, `currency`, `percent`, `rating`, `progress`, `duration` | `val_num` |
| Data e tempo | `date`, `datetime` | `val_date` |
| Data e tempo (texto) | `time`, `daterange` | `val_text` |
| Seleção e listas | `select`, `multiselect`, `checkbox`, `tags`, `user`, `color` | `val_text` |
| Relacionamentos | `relation` | `val_text` |
| Dados especiais | `uuid`, `json`, `ip` | `val_text` |
| Mídia e arquivos | `image`, `file` | `val_text` (base64, MEDIUMTEXT ≈16MB) |

**Armazenamento de imagem e arquivo como base64** — a decisão de usar base64 em `val_text` (MEDIUMTEXT, suporta até 16MB) elimina dependência de sistema de arquivos, mantendo o FlexCore 100% portável em hospedagens compartilhadas. A conversão FileReader → base64 ocorre no browser antes do POST. Um arquivo de 5MB vira ≈6.7MB no banco. Recomendado: imagens até 2MB, arquivos até 5MB.

**Tipos com comportamento especial no `RecordService::saveValues()`:**
- `checkbox` — `isset($input[$key])` → `"1"` ou `"0"` (checkbox HTML não envia campo se desmarcado)
- `multiselect` / `tags` — converte array PHP para JSON string
- `uuid` — chama `generateUuid()` (RFC 4122 v4) se o valor estiver vazio
- `duration` — aceita string `"H:M:S"` e converte para segundos inteiros em `val_num`
- `daterange` — constrói JSON `{"start":"YYYY-MM-DD","end":"YYYY-MM-DD"}` a partir de dois campos `_start` / `_end`
- `image` / `file` — lê `field_N_file_data` (base64 gerado no front) ou `field_N_keep` (preserva valor existente)
- `password` — sem conversão especial; armazenado em texto plano em `val_text` (responsabilidade do operador criptografar se necessário)
- `json` — descartado se `json_decode()` retornar `null` (JSON inválido)

**Demais tabelas:**

```
users               → autenticação e controle de acesso
settings            → configurações chave-valor do sistema
entity_permissions  → permissões granulares por entidade e papel (can_create/edit/delete)
api_keys            → chaves de API (hash SHA-256)
api_key_hits        → registro de hits para rate limiting (sliding window)
automations         → regras de automação
automation_logs     → log de execuções de automações
plugins             → plugins instalados e suas configurações
audit_log           → log de auditoria de ações do sistema
```

---

### Repositories — `app/Repositories/`

Encapsulam o acesso ao banco por entidade de domínio. Todos estendem `BaseRepository` que provê métodos CRUD genéricos.

| Repository | Responsabilidade |
|------------|-----------------|
| `EntityRepository` | Entidades e seus campos |
| `FieldRepository` | Campos de uma entidade |
| `RecordRepository` | Registros e seus valores (EAV) |
| `AutomationRepository` | Automações e logs de execução |

Interfaces (`RepositoryInterface`, `EntityRepositoryInterface`) permitem substituição e testes.

---

### Services — `app/Services/`

Orquestram a lógica de negócio usando Repositories e disparando Hooks.

**`RecordService`** — ciclo de vida completo dos registros:
1. Valida campos obrigatórios (`assertRequired`)
2. Dispara `record.before_create`
3. Cria o registro em `entity_records`
4. Persiste os valores em `record_values` com tipagem correta
5. Registra na auditoria
6. Dispara `record.created`

O mesmo padrão se repete para update e delete. O `RecordService` é compartilhado entre os controllers web (`RecordController`) e os controllers de API (`ApiRecordController`).

**`AuditService`** — insere entradas no `audit_log` com usuário, IP e descrição.

---

### Controllers web — `app/Controllers/`

Thin controllers: recebem a request, delegam para Service/Repository, redirecionam ou renderizam a view.

| Controller | Responsabilidade |
|------------|-----------------|
| `AuthController` | Login e logout |
| `DashboardController` | Página inicial com estatísticas |
| `EntityController` | CRUD de entidades, campos e permissões por papel |
| `RecordController` | CRUD de registros + guard de permissão + filtros avançados |
| `SettingsController` | Configurações do sistema + CRUD de usuários |
| `ApiKeyController` | Gerenciamento de chaves de API (interface web) |
| `AutomationController` | CRUD de automações + visualização de logs |
| `PluginController` | Instalação, ativação, configuração e remoção de plugins |

**Permissões granulares — `RecordController::checkEntityPermission()`**

Verificada nas operações de escrita (store, edit, update, destroy). Consulta `entity_permissions` pelo `entity_id` e `role` do usuário logado. Quando não há linha configurada para a entidade, libera tudo (retrocompatibilidade). Admins nunca são bloqueados.

**Filtros avançados — `RecordController::index()`**

A listagem suporta dois mecanismos paralelos:
- `?q=texto` — busca global em `val_text` de todos os campos (legado)
- `?filters[]=fieldId:op:valor` — filtro por campo específico com operador

Operadores disponíveis (variam por `field_type`): `eq`, `neq`, `contains`, `not_contains`, `starts_with`, `gt`, `lt`, `gte`, `lte`, `empty`, `not_empty`. Todos os filtros são combinados com AND via subconsultas EXISTS na tabela `record_values`. A view `records/index.php` renderiza uma sidebar com seletor de campo/operador/valor, chips dos filtros ativos com remoção individual e paginação preservando os filtros na URL.

**Ordenação de colunas — `RecordController::index()`**

Parâmetros `?sort_field={id|created_at}&sort_dir=asc|desc`. Para campos do tipo `number`/`currency` usa `val_num`; para `date`/`datetime` usa `val_date`; demais usam `val_text`. Ordenação por subconsulta ao EAV. Cabeçalhos da tabela são links que alternam ASC/DESC preservando filtros e paginação.

**Views alternativas — `RecordController::index()` + `RecordController::setView()`**

Três modos de visualização: `table` (padrão), `cards` (grid de cartões) e `kanban` (colunas por opções de campo `select`). A preferência do usuário é salva via POST em `/e/{slug}/set-view` e armazenada na tabela `settings` com chave `view_pref_{userId}_{entityId}`. O seletor de view (☰ ⊞ ⊟) fica na barra de status da listagem.

---

### API REST — `api/`

#### Autenticação — `ApiAuthMiddleware`

1. Extrai a key do header `Authorization: Bearer {key}` ou parâmetro `api_key`
2. Busca por `hash('sha256', $rawKey)` na tabela `api_keys`
3. Verifica se ativa e não expirada
4. Executa o rate limiting por janela deslizante de 60s
5. Registra `last_used_at` e injeta `$request->context['api_key']`

**Rate limiting — sliding window:**
- Insere um hit em `api_key_hits` com precisão de milissegundos
- Limpa hits mais antigos que a janela antes de contar
- Retorna headers padrão: `X-RateLimit-Limit`, `X-RateLimit-Remaining`, `X-RateLimit-Reset`
- HTTP 429 com `Retry-After` quando o limite é atingido

#### Controller — `ApiRecordController`

Implementa os 6 endpoints REST. Usa o mesmo `RecordService` dos controllers web.

**Particularidades:**
- Body aceita JSON (`Content-Type: application/json`) ou form-data
- Campos são indexados pelo **slug** nos inputs e nas respostas (não pelo `id` interno)
- Tipagem automática na saída: `number`/`currency` → `float`, `checkbox` → `bool`, `multiselect` → `array`
- Filtros de listagem: `?q=` (busca global em `val_text`) e `?{slug_campo}=valor` (valor exato)
- Ordenação: `?sort={slug}&dir=asc|desc` — subconsulta ao EAV para ordenar pelo valor correto
- Permissões por escopo da API Key: `scope: all` libera tudo; `scope: custom` verifica `entities[]` e `access[]`

#### Formatação — `ApiResponse`

Todas as respostas seguem o envelope:
```json
{
  "data":   { ... } | [ ... ] | null,
  "meta":   { ... } | null,
  "errors": [ "mensagem" ] | null
}
```

Métodos disponíveis: `ok()`, `created()`, `noContent()`, `error()`, `notFound()`, `unauthorized()`, `forbidden()`, `validationError()`.

---

### Sistema de Plugins — `modules/Plugins/`

**Descoberta:** `PluginLoader` itera `plugins/` em busca de diretórios com `plugin.json` + `Plugin.php`.

**Compatibilidade:** Verifica `requires` do manifesto contra `APP_VERSION` via `version_compare`.

**Ativação:** Apenas plugins com `plugin_id` na tabela `plugins` com `active = 1` são carregados.

**Interface `PluginInterface`:**
```php
interface PluginInterface {
    public function boot(): void;
    public function manifest(): array;
}
```

O `boot()` é o ponto de entrada — é onde o plugin registra seus hooks, adiciona rotas ou configura serviços.

**Plugin de configuração:** O `plugin.json` define um array `settings` que o painel de Plugins renderiza automaticamente como formulário (tipos: text, url, select, textarea, checkbox).

**Hook de extensão de UI — `records.list.actions` (Filter):**
Permite que plugins injetem HTML na barra de ações da listagem de registros. Recebe a string HTML atual e o array `['entity' => [...]]`. Retorna a string HTML modificada. O plugin `flexcore-data-exporter` usa este hook para injetar o botão "⬇ Exportar".

---

### Módulo de Automações — `modules/Automations/`

**`AutomationEngine`** escuta os hooks `record.created`, `record.updated` e `record.deleted` no `boot()`.

**Fluxo de execução:**
1. `dispatch()` recebe evento + entityId + recordId + input
2. Busca automações ativas para aquela entidade/evento
3. Para cada automação, verifica condições (`conditionsMet`)
4. Chama `runAction()` com o handler registrado
5. Loga sucesso ou erro em `automation_logs`

**Condições suportadas:** `eq`, `neq`, `gt`, `lt`, `contains`, `not_empty`, `empty`

**`WebhookAction`:**
- Envia `POST/PUT/PATCH` com payload JSON para a URL configurada
- Inclui header `X-FlexCore-Key` (HMAC-like: `sha256(url + recordId)`)
- Retry automático: 3 tentativas com backoff exponencial (1s → 2s → 4s)

**Interface `ActionHandlerInterface`:**
```php
interface ActionHandlerInterface {
    public function execute(array $config, int $recordId, int $entityId, array $input): void;
    public function label(): string;
    public function configSchema(): array;
}
```

Novas ações podem ser registradas via `$automationEngine->registerAction('tipo', $handler)` — idealmente a partir de um plugin.

---

### Internacionalização — `lib/helpers.php` + `translates/`

Arquivos JSON com estrutura aninhada de chaves. A função `__('chave.aninhada', ['var' => 'valor'])` resolve a tradução com suporte a interpolação via `:variavel`.

Idiomas disponíveis: `pt_BR`, `en_US`, `es`, `fr`, `de`.

O idioma ativo é determinado em cascata:
1. Preferência do usuário logado (`users.lang`)
2. Configuração global (`settings.app_lang`)
3. Fallback: `pt_BR`

---

## Decisões de design notáveis

**Sem Composer / sem framework externo** — todas as dependências são zero. Isso simplifica deploy em hospedagens compartilhadas (como cPanel/Hostgator) sem acesso ao terminal.

**Carregamento manual em cascata** — em vez de PSR-4 autoload, o `bootstrap.php` faz `require_once` de cada arquivo em ordem. Verboso, mas previsível e sem "magia".

**SQL direto com prepared statements** — sem ORM nem query builder. Facilita otimização de queries, mas exige disciplina para não vazar SQL nos Controllers (deve ficar nos Repositories).

**EAV para campos dinâmicos** — a abordagem Entity-Attribute-Value permite criar entidades sem DDL, mas impõe limitações em queries complexas (JOINs por campo são custosos). Para volumes altos, considerar colunas dinâmicas ou JSON column.

**Plugins como pastas, não como Composer packages** — facilita instalação via upload de `.zip` na interface web, sem terminal. A desvantagem é que não há resolução automática de conflitos de dependência.

**Middleware encadeado no Route, não no Router** — o `Router` não tem um stack global de middleware. Cada rota declara seus próprios middlewares com `->middleware(...)`. Isso evita efeitos colaterais entre rotas web e rotas de API que coexistem no mesmo `routes.php`.