# FlexCore

**Framework PHP para sistemas de gestão de dados dinâmicos.**

FlexCore é uma plataforma CRUD/CMS que permite criar e gerenciar qualquer estrutura de dados via interface web, sem escrever código. O administrador define **Entidades** com campos configuráveis e o sistema gera automaticamente listagens, formulários, API REST, automações e auditoria — tudo extensível por plugins.

---

## Funcionalidades

- **Entidades dinâmicas** — crie "tabelas" com nome, ícone, cor e campos sem tocar no banco
- **29 tipos de campo** — texto, número, moeda, data, select, multiselect, relação, imagem, arquivo, fórmula e mais
- **API REST completa** — endpoints CRUD com autenticação por chave, paginação, filtros e rate limiting
- **Automações** — regras configuráveis (criar/atualizar/excluir registro → condições → ação webhook)
- **Sistema de plugins** — estenda qualquer comportamento sem modificar o core
- **Auditoria** — log de todas as operações com diff de campos
- **Permissões granulares** — controle de criação, edição e exclusão por entidade e papel de usuário
- **Internacionalização** — pt_BR, en_US, es, de, fr (fique a vontade para criar novos arquivos JSON para o seu idioma nativo!)
- **Tema configurável** — cores, logo, favicon, modo claro/escuro
- **Instalador web** — wizard completo para configurar banco, admin e ambiente inicial

---

## Requisitos

| Componente | Versão mínima |
|---|---|
| PHP | 7.4+ |
| MySQL / MariaDB | 5.7+ / 10.3+ |
| Extensões PHP | PDO, pdo_mysql, json, session, mbstring, curl |
| Servidor web | Apache (mod_rewrite) ou Nginx |

> Funciona em XAMPP, Laragon, servidores compartilhados e VPS sem dependências externas.

---

## Instalação

### 1. Clonar ou extrair os arquivos

```bash
git clone https://github.com/marcossancal/FlexCore
# ou extraia o .zip no diretório do servidor web
```

### 2. Configurar o servidor web

**Apache** — o arquivo `.htaccess` já está incluído e configura o rewrite automático.

Certifique-se de que `AllowOverride All` está ativo para o diretório.

**Nginx:**
```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

**Router.php**

Configurado na raiz do diretório para o caso de não usar nenhum dos dois.

### 3. Executar o instalador

Acesse no navegador:
```
http://seudominio.com/flexcore/install/
```

O wizard irá:
1. Coletar as credenciais do banco de dados
2. Criar todas as tabelas necessárias
3. Criar o usuário administrador
4. Configurar o `ADMIN_PATH` (prefixo do painel, ex: `/painel`)
5. Gerar o arquivo `.env`
6. Criar o sentinel `.installed`

### 4. Acessar o painel

```
http://seudominio.com/flexcore/painel/login
```

> O `ADMIN_PATH` pode ser configurado para qualquer valor durante a instalação (ex: `admin`, `gestao`, `backoffice`).

---

## Configuração

O arquivo `.env` é gerado pelo instalador e contém:

```env
DB_HOST=localhost
DB_PORT=3306
DB_NAME=flexcore
DB_USER=root
DB_PASS=senha

DEBUG=false
ADMIN_PATH=painel
```

| Variável | Descrição |
|---|---|
| `DB_HOST` | Host do banco de dados |
| `DB_PORT` | Porta do MySQL (padrão: 3306) |
| `DB_NAME` | Nome do banco de dados |
| `DB_USER` | Usuário do banco |
| `DB_PASS` | Senha do banco |
| `DEBUG` | `true` exibe erros PHP; use apenas em desenvolvimento |
| `ADMIN_PATH` | Segmento de URL do painel (ex: `painel` → `/painel/login`) |

---

## Estrutura de Pastas

```
flexcore/
├── index.php          ← entry point único
├── .env               ← configuração de ambiente
├── .htaccess          ← reescrita de URL
├── config/            ← bootstrap, container DI, rotas
├── core/              ← Container, Hooks, Router
├── lib/               ← DB, Auth, helpers globais
├── app/               ← Controllers, Repositories, Services, Views
├── api/               ← Controllers REST, Middleware, Formatters
├── modules/           ← Plugins e Automations
├── plugins/           ← plugins instalados
├── translates/        ← arquivos de idioma JSON
├── install/           ← wizard de instalação
└── tests/             ← testes unitários e de feature
```

Documentação detalhada em `architecture.md`.

---

## Entidades e Campos

Uma **Entidade** é equivalente a uma tabela de dados configurável. Cada entidade tem:

- Nome, slug, ícone, cor e descrição
- Lista de campos com tipos e opções
- Permissões por papel de usuário
- Configuração de resposta customizada da API

### Tipos de Campo Disponíveis

| Categoria | Tipos |
|---|---|
| Texto | `text`, `textarea`, `richtext`, `email`, `url`, `phone`, `password` |
| Números | `number`, `currency`, `percent`, `rating`, `progress`, `duration` |
| Data e Hora | `date`, `datetime`, `time`, `daterange` |
| Seleção | `select`, `multiselect`, `checkbox`, `tags`, `user`, `color` |
| Relacionamento | `relation` |
| Especiais | `uuid`, `json`, `ip`, `formula` |
| Mídia | `image`, `file` |

O hook `field.types` permite plugins registrarem tipos adicionais.

---

## API REST

A API REST é acessível em `/api/v1/` e requer autenticação por chave (gerenciada no painel em **API → Chaves**).

### Autenticação

```http
Authorization: Bearer {sua_chave_api}
```

Ou via query string: `?api_key={sua_chave_api}`

### Endpoints

| Método | Endpoint | Descrição |
|---|---|---|
| GET | `/api/v1/entities` | Lista todas as entidades ativas |
| GET | `/api/v1/e/{slug}` | Lista registros de uma entidade |
| GET | `/api/v1/e/{slug}/{id}` | Detalhe de um registro |
| POST | `/api/v1/e/{slug}` | Cria um registro |
| PUT | `/api/v1/e/{slug}/{id}` | Atualiza um registro |
| DELETE | `/api/v1/e/{slug}/{id}` | Exclui um registro |

### Parâmetros de Listagem

| Parâmetro | Descrição | Padrão |
|---|---|---|
| `page` | Página atual | 1 |
| `per_page` | Itens por página (máx. 100) | 25 |
| `q` | Busca textual global | — |
| `field_{slug}` | Filtro por campo específico | — |
| `sort` | Campo para ordenação (slug) | — |
| `dir` | Direção: `asc` ou `desc` | asc |

### Formato de Resposta

```json
{
  "data": [
    {
      "id": 42,
      "created_at": "2026-05-16 10:00:00",
      "updated_at": "2026-05-16 10:00:00",
      "fields": {
        "nome": "João Silva",
        "email": "joao@exemplo.com",
        "valor": 1500.00
      }
    }
  ],
  "meta": {
    "total": 87,
    "page": 1,
    "per_page": 25,
    "pages": 4
  },
  "errors": null
}
```

### Rate Limiting

Cada chave tem um limite configurável de requisições por minuto (padrão: 60). Os headers `X-RateLimit-Limit`, `X-RateLimit-Remaining` e `X-RateLimit-Reset` são enviados em toda resposta. Ao exceder o limite, o sistema retorna `429` com `Retry-After`.

---

## Automações

Configure no painel em **Automações** regras automáticas baseadas em eventos de registros:

1. **Trigger** — escolha a entidade e o evento: `Ao criar`, `Ao atualizar` ou `Ao excluir`
2. **Condições** (opcional) — filtre por valor de campo: igual, diferente, maior, menor, contém, vazio/não vazio
3. **Ação** — configure a ação a ser executada

**Ação nativa: Webhook**

Envia uma requisição HTTP POST para a URL configurada com o payload:
```json
{
  "event": "record.created",
  "entity_id": 3,
  "record_id": 42,
  "input": { "field_1": "valor", ... }
}
```

Plugins podem registrar novos tipos de ação via `AutomationEngine::registerAction()`.

---

## Sistema de Plugins

O FlexCore é extensível por plugins que adicionam funcionalidades sem modificar o core.

### Instalar um Plugin

1. Coloque o diretório do plugin em `plugins/`
2. Acesse **Painel → Plugins**
3. Clique em **Ativar**

O painel também suporta instalação via upload de `.zip` e via URL de registry externo.

### Estrutura Mínima de um Plugin

```
plugins/meu-plugin/
├── plugin.json    ← manifesto com id, name, version, requires, namespace
└── Plugin.php     ← implementa PluginInterface (manifest, boot, uninstall)
```

```php
// Plugin.php
class Plugin implements PluginInterface
{
    public function boot(): void
    {
        // Registre hooks, rotas e configurações aqui
        Hooks::on('record.created', function(int $id, int $entityId, array $input): void {
            // ...
        });
    }
}
```

Documentação completa em `PLUGINS.md`.

---

## Hooks Disponíveis

O sistema expõe hooks em todos os pontos extensíveis:

| Hook | Tipo | Descrição |
|---|---|---|
| `record.before_create` / `record.created` | Action | Ciclo de vida de criação |
| `record.before_update` / `record.updated` | Action | Ciclo de vida de atualização |
| `record.before_delete` / `record.deleted` | Action | Ciclo de vida de exclusão |
| `record.input` | Filter | Normaliza input antes de salvar |
| `record.values_loaded` | Filter | Transforma valores ao carregar |
| `router.register` | Action | Plugins registram suas rotas |
| `public_routes.match` | Filter | Define rotas sem autenticação |
| `sidebar.nav_items` | Filter | Adiciona itens ao menu lateral |
| `layout.head` | Filter | Injeta HTML no `<head>` |
| `layout.footer_scripts` | Filter | Injeta scripts antes do `</body>` |
| `field.types` | Filter | Registra novos tipos de campo |
| `field.render_value` | Filter | Renderização customizada de valor |
| `field.render_form` | Filter | Input customizado no formulário |
| `api.response` | Filter | Transforma resposta da API |
| `translations.loaded` | Filter | Mescla traduções de plugins |

Referência completa em `HOOKS.md`.

---

## Papéis e Permissões

O sistema tem três papéis nativos:

| Papel | Acesso |
|---|---|
| `admin` | Acesso completo: entidades, campos, configurações, usuários, plugins, API |
| `editor` | Criar e editar registros nas entidades permitidas |
| `viewer` | Apenas visualizar registros |

As permissões de **criar**, **editar** e **excluir** podem ser configuradas por entidade e papel de usuário na tela de entidades.

---

## Banco de Dados

O FlexCore usa um modelo **EAV (Entity-Attribute-Value) tipado** para armazenar dados dinamicamente:

| Tabela | Descrição |
|---|---|
| `entities` | Definição das entidades |
| `entity_fields` | Campos de cada entidade |
| `entity_records` | Registros (linhas de dados) |
| `record_values` | Valores dos campos (EAV com 3 colunas tipadas) |
| `entity_permissions` | Permissões por entidade e papel |
| `users` | Usuários do sistema |
| `settings` | Configurações globais (chave-valor) |
| `api_keys` | Chaves de API com permissões e rate limit |
| `api_key_hits` | Histórico de uso para rate limiting |
| `automations` | Regras de automação configuradas |
| `automation_logs` | Log de execuções de automações |
| `plugins` | Plugins instalados e seus metadados |
| `audit_log` | Log de auditoria de todas as operações |

---

## Internacionalização

O sistema está traduzido em 5 idiomas. Para adicionar um idioma:

1. Crie `translates/{codigo}.json` seguindo o formato dos existentes
2. Adicione o `_meta` com `lang` e `name`
3. O idioma aparece automaticamente nas configurações

Plugins adicionam suas próprias strings via o hook `translations.loaded` sem editar os arquivos do core.

---

## Testes

O FlexCore inclui uma suíte de testes própria (sem dependências externas):
Esses testes não estão completos, ajuda é muito bem vinda...

```bash
php tests/run.php
```

Testes disponíveis:
- `tests/Unit/HooksTest.php` — sistema de hooks
- `tests/Unit/HelpersTest.php` — funções auxiliares
- `tests/Unit/RouterTest.php` — roteamento
- `tests/Unit/PluginLoaderTest.php` — carregamento de plugins
- `tests/Unit/PluginManifestTest.php` — manifesto de plugins
- `tests/Unit/i18nTest.php` — internacionalização
- `tests/Feature/ApiRecordTest.php` — endpoints da API REST

---

## Segurança

- Senhas armazenadas com `password_hash()` (bcrypt)
- Proteção contra SQL injection via PDO com prepared statements
- Sanitização de output com `htmlspecialchars()` via helper `h()`
- API keys armazenadas como hash SHA-256 (a chave original nunca é salva)
- Rate limiting na API com janela deslizante
- Session regeneration no login (`session_regenerate_id(true)`)
- Rotas do painel protegidas por verificação de sessão em cada request

---

## Customização Visual

No painel em **Configurações → Tema**:

- Cor de destaque (accent color primária)
- Cor secundária (accent color 2)
- Modo claro / escuro
- Preset de tema
- Logo e favicon personalizados
- Nome da aplicação

---

## Licença

Consulte o arquivo `LICENSE` para os termos completos.

---

## Documentação

| Arquivo | Conteúdo |
|---|---|
| `README.md` | Este arquivo — visão geral e início rápido |
| `ARCHITECTURE.md` | Arquitetura detalhada, camadas e fluxos |
| `PLUGINS.md` | Guia completo para criação de plugins |
| `HOOKS.md` | Referência de todos os hooks com assinaturas e exemplos |