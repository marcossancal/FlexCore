# FlexCore — Referência Completa de Hooks

## Como Usar

O sistema de hooks do FlexCore tem dois tipos:

**Actions** — executam efeitos colaterais, sem retorno:
```php
Hooks::on('nome.do.hook', function(...$args): void {
    // faz algo
});

// Com prioridade (menor número = executa primeiro, padrão: 10)
Hooks::on('nome.do.hook', $fn, priority: 5);
```

**Filters** — transformam um valor e o retornam:
```php
Hooks::filter('nome.do.filtro', function($valor, ...$extras) {
    // modifica $valor
    return $valor;
});

// Aplicar o filtro (feito pelo core; plugins não precisam chamar isso)
$resultado = Hooks::applyFilter('nome.do.filtro', $valorInicial, [$arg1, $arg2]);
```

---

## Índice de Hooks

| Hook | Tipo | Onde é disparado |
|---|---|---|
| `record.before_create` | Action | Antes de criar um registro |
| `record.created` | Action | Após criar um registro |
| `record.before_update` | Action | Antes de atualizar um registro |
| `record.updated` | Action | Após atualizar um registro |
| `record.before_delete` | Action | Antes de excluir um registro |
| `record.deleted` | Action | Após excluir um registro |
| `record.input` | Filter | Filtra o input antes de salvar |
| `record.values_loaded` | Filter | Filtra valores ao carregar um registro |
| `router.register` | Action | Registro de rotas pelos plugins |
| `public_routes.match` | Filter | Define se uma rota é pública (sem auth) |
| `sidebar.nav_items` | Filter | Itens do menu lateral do painel |
| `layout.head` | Filter | HTML injetado no `<head>` |
| `layout.footer_scripts` | Filter | HTML injetado antes do `</body>` |
| `field.types` | Filter | Tipos de campo disponíveis |
| `field.render_value` | Filter | Renderização de valor na listagem |
| `field.render_form` | Filter | Renderização de campo no formulário |
| `field.render_config` | Filter | Configuração de campo na tela de edição |
| `field.options_build` | Filter | Construção das opções de um campo |
| `records.list.actions` | Filter | Botões de ação na listagem de registros |
| `records.list.columns.header` | Filter | Cabeçalhos extras na tabela de registros |
| `records.list.columns.cell` | Filter | Células extras na tabela de registros |
| `api.response` | Filter | Resposta da API REST |
| `api.record` | Filter | Dados de um registro individual na API |
| `translations.loaded` | Filter | Array de traduções carregadas |

---

## Hooks de Ciclo de Vida de Registros

### `record.before_create`

Disparado pelo `RecordService` **antes** de inserir o registro no banco. Use para validação adicional, transformação de dados ou side effects pré-criação.

- **Tipo:** Action
- **Arquivo:** `app/Services/RecordService.php`

```php
Hooks::on('record.before_create', function(int $entityId, array $rawInput): void {
    // $entityId  — ID da entidade onde o registro será criado
    // $rawInput  — dados brutos do formulário/API (não processados ainda)

    if ($entityId === 5 && empty($rawInput['field_12'])) {
        throw new \DomainException('Campo obrigatório para esta entidade.');
    }
});
```

> **Atenção:** lançar uma exceção aqui interrompe a criação. O registro **não** é inserido.

---

### `record.created`

Disparado pelo `RecordService` **após** o registro ter sido criado e seus valores salvos. É aqui que o `AutomationEngine` atua internamente.

- **Tipo:** Action
- **Arquivo:** `app/Services/RecordService.php`

```php
Hooks::on('record.created', function(int $recordId, int $entityId, array $rawInput): void {
    // $recordId  — ID do registro recém-criado
    // $entityId  — ID da entidade
    // $rawInput  — dados brutos enviados (field_N => valor)

    // Exemplo: log externo, notificação, sync
    error_log("Registro #{$recordId} criado na entidade #{$entityId}");
});
```

---

### `record.before_update`

Disparado pelo `RecordService` **antes** de atualizar os valores de um registro.

- **Tipo:** Action
- **Arquivo:** `app/Services/RecordService.php`

```php
Hooks::on('record.before_update', function(int $recordId, int $entityId, array $rawInput): void {
    // $recordId  — ID do registro sendo atualizado
    // $entityId  — ID da entidade
    // $rawInput  — novos dados brutos
});
```

---

### `record.updated`

Disparado pelo `RecordService` **após** os valores serem salvos. O diff entre o estado anterior e posterior já foi registrado no audit log.

- **Tipo:** Action
- **Arquivo:** `app/Services/RecordService.php`

```php
Hooks::on('record.updated', function(int $recordId, int $entityId, array $rawInput): void {
    // $recordId  — ID do registro atualizado
    // $entityId  — ID da entidade
    // $rawInput  — dados enviados na atualização
});
```

---

### `record.before_delete`

Disparado pelo `RecordService` **antes** de excluir um registro. Use para bloquear exclusão condicional ou fazer backup dos dados.

- **Tipo:** Action
- **Arquivo:** `app/Services/RecordService.php`

```php
Hooks::on('record.before_delete', function(int $recordId, int $entityId): void {
    // $recordId — ID do registro prestes a ser excluído
    // $entityId — ID da entidade

    // Exemplo: bloquear exclusão se houver dependências
    $deps = \DB::one('SELECT COUNT(*) AS c FROM pedidos WHERE cliente_id = ?', [$recordId]);
    if ($deps['c'] > 0) {
        throw new \DomainException('Não é possível excluir: cliente possui pedidos.');
    }
});
```

> **Atenção:** lançar uma exceção aqui interrompe a exclusão.

---

### `record.deleted`

Disparado pelo `RecordService` **após** o registro ter sido excluído do banco.

- **Tipo:** Action
- **Arquivo:** `app/Services/RecordService.php`

```php
Hooks::on('record.deleted', function(int $recordId, int $entityId): void {
    // $recordId — ID do registro excluído (já não existe no banco)
    // $entityId — ID da entidade
});
```

---

## Hooks de Input e Valores

### `record.input`

Filtra o array de input **antes** de ser passado ao `RecordService`. Use para normalizar, enriquecer ou remover campos.

- **Tipo:** Filter
- **Arquivo:** `app/Controllers/RecordController.php`

```php
Hooks::filter('record.input', function(array $input, array $ctx): array {
    // $input — array de input atual (field_N => valor)
    // $ctx   — contexto: ['entity' => [...], 'fields' => [...], 'record_id' => int|null]

    // Exemplo: forçar um campo para maiúsculas
    if (isset($input['field_7'])) {
        $input['field_7'] = strtoupper($input['field_7']);
    }

    return $input; // obrigatório retornar o array
});
```

**Assinatura:**
```
applyFilter('record.input', array $input, [array $ctx])
             ──────────────────────────────────────────
             retorna: array $input (modificado ou não)
```

---

### `record.values_loaded`

Filtra os valores de um registro após serem carregados do banco, antes de exibir no formulário ou na view de detalhe.

- **Tipo:** Filter
- **Arquivo:** `app/Controllers/RecordController.php`

```php
Hooks::filter('record.values_loaded', function(array $values, array $ctx): array {
    // $values — array [field_id => valor] carregado do banco
    // $ctx    — contexto: ['entity' => [...], 'fields' => [...], 'record_id' => int]

    // Exemplo: descriptografar um campo
    if (isset($values[15])) {
        $values[15] = meuDecrypt($values[15]);
    }

    return $values;
});
```

**Assinatura:**
```
applyFilter('record.values_loaded', array $values, [array $ctx])
             ──────────────────────────────────────────────────
             retorna: array $values (modificado ou não)
```

---

## Hooks de Roteamento

### `router.register`

Disparado ao final de `config/routes.php`. É o ponto onde plugins registram suas rotas.

- **Tipo:** Action
- **Arquivo:** `config/routes.php`

```php
Hooks::on('router.register', function(\FlexCore\Core\Router\Router $router): void {
    $router->get('/meu-plugin',        [MeuPlugin\Controllers\MainController::class, 'index']);
    $router->post('/meu-plugin/salvar', [MeuPlugin\Controllers\MainController::class, 'save']);
    $router->get('/meu-plugin/{id}',   [MeuPlugin\Controllers\MainController::class, 'show']);
});
```

> **Importante:** rotas de plugin não têm o prefixo `ADMIN_PATH` automaticamente. Se quiser que a rota fique dentro do painel, prefixe manualmente: `'/painel/meu-plugin'`. Se quiser que a rota seja pública (front-end), use o caminho limpo e combine com o hook `public_routes.match`.

---

### `public_routes.match`

Determina se uma rota específica é pública (não requer autenticação). Retorne `true` para liberar a rota.

- **Tipo:** Filter
- **Arquivo:** `index.php`

```php
Hooks::filter('public_routes.match', function(bool $matched, string $path): bool {
    // $matched — resultado acumulado dos listeners anteriores (começa como false)
    // $path    — path atual da requisição (ex: '/formulario', '/api/externa/dados')

    // Liberar todas as rotas do plugin sem autenticação
    if (strpos($path, '/formulario') === 0) return true;
    if (strpos($path, '/api/externa/') === 0) return true;

    return $matched; // preserva decisões de outros plugins
});
```

**Assinatura:**
```
applyFilter('public_routes.match', bool $matched, [string $path])
             ───────────────────────────────────────────────────
             retorna: bool (true = rota pública, false = requer auth)
```

---

## Hooks de Interface

### `sidebar.nav_items`

Filtra o array de itens do menu lateral do painel admin. Use para adicionar links de navegação do plugin.

- **Tipo:** Filter
- **Arquivo:** `app/views/layout/header.php`

```php
Hooks::filter('sidebar.nav_items', function(array $items): array {
    // $items — array de items nativos do menu
    // Cada item: ['label' => string, 'url' => string, 'icon' => string, 'active' => bool]

    $items[] = [
        'label'  => 'Relatórios',
        'url'    => url('relatorios'),
        'icon'   => '📊',
        'active' => strpos($_SERVER['REQUEST_URI'] ?? '', '/relatorios') !== false,
    ];

    return $items;
});
```

**Assinatura:**
```
applyFilter('sidebar.nav_items', array $items)
             ─────────────────────────────────
             retorna: array $items
```

---

### `layout.head`

Injeta HTML dentro do `<head>` de todas as páginas do painel. Use para carregar CSS, fontes ou meta tags do plugin.

- **Tipo:** Filter
- **Arquivo:** `app/views/layout/header.php`

```php
Hooks::filter('layout.head', function(string $html): string {
    $cssUrl = url('plugins/meu-plugin/assets/style.css');
    return $html . "<link rel='stylesheet' href='{$cssUrl}'>";
});
```

**Assinatura:**
```
applyFilter('layout.head', string $html)
             ───────────────────────────
             retorna: string $html
```

---

### `layout.footer_scripts`

Injeta HTML imediatamente antes do `</body>`. Use para carregar JavaScript do plugin.

- **Tipo:** Filter
- **Arquivo:** `app/views/layout/footer.php`

```php
Hooks::filter('layout.footer_scripts', function(string $html): string {
    $jsUrl = url('plugins/meu-plugin/assets/app.js');
    return $html . "<script src='{$jsUrl}'></script>";
});
```

**Assinatura:**
```
applyFilter('layout.footer_scripts', string $html)
             ─────────────────────────────────────
             retorna: string $html
```

---

## Hooks de Tipos de Campo

### `field.types`

Registra novos tipos de campo no sistema. O retorno é o array completo de tipos disponíveis.

- **Tipo:** Filter
- **Arquivo:** `lib/helpers.php` (função `allFieldTypes()`)

```php
Hooks::filter('field.types', function(array $types): array {
    // $types — array de tipos nativos do core

    $types['cpf'] = [
        'icon'    => '🪪',
        'storage' => 'val_text',  // val_text | val_num | val_date
    ];

    $types['cnpj'] = [
        'icon'    => '🏢',
        'storage' => 'val_text',
    ];

    return $types;
});
```

**Assinatura:**
```
applyFilter('field.types', array $types)
             ────────────────────────────
             retorna: array $types
```

Valores de `storage`:
- `val_text` — MEDIUMTEXT (strings, JSON, base64)
- `val_num` — DECIMAL(18,4) (números, moeda, percentual)
- `val_date` — DATETIME (datas e datetimes)

---

### `field.render_value`

Controla como um valor é renderizado na **listagem de registros** e na **view de detalhe**. Retorne `null` para usar a renderização padrão do core.

- **Tipo:** Filter
- **Arquivo:** `lib/helpers.php` (função `renderFieldValue()`)

```php
Hooks::filter('field.render_value', function(?string $html, array $field, mixed $val, bool $full): ?string {
    // $html  — HTML produzido por outros filtros (null = nenhum ainda)
    // $field — dados do campo: ['field_type', 'name', 'slug', 'options_json', ...]
    // $val   — valor bruto do campo
    // $full  — true na view de detalhe, false na listagem

    if ($field['field_type'] !== 'cpf') return $html; // ignorar outros tipos

    // Formatar CPF: 000.000.000-00
    $digits = preg_replace('/\D/', '', (string) $val);
    if (strlen($digits) !== 11) return h($val);
    return h(substr($digits,0,3).'.'.substr($digits,3,3).'.'.substr($digits,6,3).'-'.substr($digits,9,2));
});
```

**Assinatura:**
```
applyFilter('field.render_value', ?string $html, [array $field, mixed $val, bool $full])
             ─────────────────────────────────────────────────────────────────────────
             retorna: ?string — null usa renderização do core; string é exibida diretamente
```

---

### `field.render_form`

Controla como um campo é renderizado no **formulário de criação/edição** de registros. Retorne `null` para usar o input padrão do core.

- **Tipo:** Filter
- **Arquivo:** `app/views/records/form.php`

```php
Hooks::filter('field.render_form', function(?string $html, array $field, string $name, mixed $val, bool $required): ?string {
    // $html     — HTML produzido por outros filtros (null = nenhum ainda)
    // $field    — dados do campo
    // $name     — atributo name do input (ex: "field_12")
    // $val      — valor atual do campo
    // $required — se o campo é obrigatório

    if ($field['field_type'] !== 'cpf') return $html;

    $req = $required ? 'required' : '';
    $v   = htmlspecialchars((string) $val, ENT_QUOTES);
    return "<input type='text' name='{$name}' value='{$v}' 
                   maxlength='14' placeholder='000.000.000-00'
                   class='form-input cpf-mask' {$req}>";
});
```

**Assinatura:**
```
applyFilter('field.render_form', ?string $html, [array $field, string $name, mixed $val, bool $required])
             ───────────────────────────────────────────────────────────────────────────────────────────
             retorna: ?string — null usa input padrão do core; string substitui o HTML do campo
```

---

### `field.render_config`

Injeta HTML na tela de **edição de campos** da entidade (seção de configuração avançada de um tipo específico).

- **Tipo:** Filter
- **Arquivo:** `app/views/entities/fields.php`

```php
Hooks::filter('field.render_config', function(string $html, array $ctx): string {
    // $html — HTML acumulado
    // $ctx  — ['field' => array, 'entity' => array]

    $field = $ctx['field'] ?? [];
    if (($field['field_type'] ?? '') !== 'cpf') return $html;

    return $html . '<p class="help-text">CPF será validado e formatado automaticamente.</p>';
});
```

**Assinatura:**
```
applyFilter('field.render_config', string $html, [array $ctx])
             ─────────────────────────────────────────────────
             retorna: string $html
```

---

### `field.options_build`

Constrói o JSON de opções para tipos de campo que precisam de configuração extra (ex: opções de um select customizado). Retorne `null` para usar o comportamento padrão do core.

- **Tipo:** Filter
- **Arquivo:** `app/Controllers/EntityController.php`

```php
Hooks::filter('field.options_build', function(?string $json, array $ctx): ?string {
    // $json — JSON atual (null = ainda não definido)
    // $ctx  — ['field_type' => string, 'post' => array $_POST]

    if ($ctx['field_type'] !== 'cpf') return $json;

    // Construir opções específicas do tipo CPF a partir do POST
    $options = ['validate' => (bool) ($ctx['post']['cpf_validate'] ?? false)];
    return json_encode($options);
});
```

**Assinatura:**
```
applyFilter('field.options_build', ?string $json, [array $ctx])
             ─────────────────────────────────────────────────
             retorna: ?string — null usa lógica padrão; string é salva em options_json
```

---

## Hooks de Listagem de Registros

### `records.list.actions`

Injeta HTML na barra de ações acima da tabela de registros (ao lado dos botões "Novo" e de view).

- **Tipo:** Filter
- **Arquivo:** `app/views/records/index.php`

```php
Hooks::filter('records.list.actions', function(string $html, array $ctx): string {
    // $html   — HTML acumulado de outros filtros
    // $ctx    — ['entity' => array]

    $entity = $ctx['entity'];
    $slug   = $entity['slug'];

    return $html . "<a href='/exportar/{$slug}' class='btn btn-outline'>📤 Exportar</a>";
});
```

**Assinatura:**
```
applyFilter('records.list.actions', string $html, [array $ctx])
             ─────────────────────────────────────────────────
             retorna: string $html
```

---

### `records.list.columns.header`

Injeta `<th>` extras no cabeçalho da tabela de registros.

- **Tipo:** Filter
- **Arquivo:** `app/views/records/index.php`

```php
Hooks::filter('records.list.columns.header', function(string $html, array $ctx): string {
    // $html — HTML acumulado
    // $ctx  — ['entity' => array, 'fields' => array]

    return $html . '<th>Status Externo</th>';
});
```

**Assinatura:**
```
applyFilter('records.list.columns.header', string $html, [array $ctx])
             ──────────────────────────────────────────────────────────
             retorna: string $html
```

---

### `records.list.columns.cell`

Injeta `<td>` extras em cada linha da tabela de registros. Chamado uma vez por registro.

- **Tipo:** Filter
- **Arquivo:** `app/views/records/index.php`

```php
Hooks::filter('records.list.columns.cell', function(string $html, array $ctx): string {
    // $html   — HTML acumulado
    // $ctx    — ['entity' => array, 'record' => array, 'values' => array, 'fields' => array]

    $record = $ctx['record'];
    $status = buscarStatusExterno($record['id']);

    return $html . "<td><span class='badge'>{$status}</span></td>";
});
```

**Assinatura:**
```
applyFilter('records.list.columns.cell', string $html, [array $ctx])
             ────────────────────────────────────────────────────────
             retorna: string $html
```

---

## Hooks de API

### `api.response`

Filtra a resposta completa da API REST antes de ser serializada como JSON. Aplica-se a todas as respostas de listagem e detalhe.

- **Tipo:** Filter
- **Arquivo:** Chamado no `ApiRecordController` via `ApiResponse`

```php
Hooks::filter('api.response', function(array $response, array $entity): array {
    // $response — array da resposta atual {data, meta, errors}
    // $entity   — dados da entidade consultada

    $response['meta']['versao'] = '1.0';
    $response['meta']['plugin'] = 'meu-plugin';

    return $response;
});
```

**Assinatura:**
```
applyFilter('api.response', array $response, [array $entity])
             ────────────────────────────────────────────────
             retorna: array $response
```

---

### `api.record`

Filtra os dados de **um registro individual** antes de incluí-lo na resposta da API.

- **Tipo:** Filter
- **Arquivo:** `api/Controllers/ApiRecordController.php`

```php
Hooks::filter('api.record', function(array $record, array $entity): array {
    // $record — dados do registro: {id, created_at, updated_at, fields: {...}}
    // $entity — dados da entidade

    // Exemplo: remover campo sensível da API
    unset($record['fields']['senha_interna']);

    // Exemplo: adicionar campo calculado
    $record['fields']['_url'] = 'https://meusite.com/registros/' . $record['id'];

    return $record;
});
```

**Assinatura:**
```
applyFilter('api.record', array $record, [array $entity])
             ────────────────────────────────────────────
             retorna: array $record
```

---

## Hook de Internacionalização

### `translations.loaded`

Filtra o array de traduções logo após ser carregado do JSON. Permite plugins mesclarem suas próprias strings de tradução sem editar os arquivos do core.

- **Tipo:** Filter
- **Arquivo:** `lib/helpers.php` (função `loadTranslations()`)

```php
Hooks::filter('translations.loaded', function(array $trans, string $lang): array {
    // $trans — array de traduções do core já carregado
    // $lang  — código do idioma ativo (ex: 'pt_BR', 'en_US')

    $file = __DIR__ . '/translates/' . $lang . '.json';
    if (!file_exists($file)) {
        $file = __DIR__ . '/translates/pt_BR.json'; // fallback
    }

    if (file_exists($file)) {
        $pluginTrans = json_decode(file_get_contents($file), true) ?? [];
        $trans = array_replace_recursive($trans, $pluginTrans);
    }

    return $trans;
});
```

**Assinatura:**
```
applyFilter('translations.loaded', array $trans, [string $lang])
             ─────────────────────────────────────────────────
             retorna: array $trans
```

---

## Prioridades

Todos os hooks aceitam um terceiro argumento `priority` (inteiro). Listeners com menor prioridade executam primeiro. O padrão é `10`.

```php
Hooks::on('record.created',    $fnUrgente,  priority: 1);   // executa primeiro
Hooks::on('record.created',    $fnNormal,   priority: 10);  // executa depois
Hooks::on('record.created',    $fnLento,    priority: 99);  // executa por último

Hooks::filter('api.response',  $fnA,        priority: 5);
Hooks::filter('api.response',  $fnB,        priority: 20);  // $fnB transforma o resultado de $fnA
```

---

## Inspecionando Hooks

```php
// Verificar se há listeners registrados para um hook
if (Hooks::hasListeners('record.created')) {
    // há pelo menos um listener
}
```

---

## Modo de Teste (Instância Isolada)

Para testes unitários, use `HookDispatcher` como instância em vez da classe estática `Hooks`. O estado é isolado e não contamina outros testes.

```php
use FlexCore\Core\Hooks\HookDispatcher;

$hooks = new HookDispatcher();
$hooks->on('record.created', $fn);
$hooks->fire('record.created', [1, 2, []]);

// Limpar estado da instância
$hooks->reset();

// Limpar estado global estático (para testes de integração)
HookDispatcher::resetStatic();
// ou
Hooks::reset();
```