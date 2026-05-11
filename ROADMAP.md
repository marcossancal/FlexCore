# Roadmap do FlexCore

Este documento lista os próximos passos naturais para a evolução do sistema, organizados por área e prioridade estimada. Cada item parte do que já existe e representa uma extensão lógica das funcionalidades atuais.

---

## Como este roadmap funciona

Os itens estão agrupados em três horizontes:

- **Próximo (v1.1–v1.2)** — melhorias de alto impacto no que já existe, baixo risco de quebra
- **Médio prazo (v1.3–v2.0)** — funcionalidades novas que exigem mais planejamento
- **Visão de longo prazo** — mudanças arquiteturais ou expansões de escopo

---

## Próximo (v1.1–v1.2)

### ✅ API REST — CRUD completo _(concluído em v1.1)_

Implementado em `api/Controllers/ApiRecordController.php`:

- `GET /api/v1/entities` — lista entidades ativas com contagem de campos e registros
- `GET /api/v1/e/{slug}` — lista registros com paginação, busca global (`?q=`), filtro por campo (`?{slug}=valor`) e ordenação (`?sort=&dir=`)
- `GET /api/v1/e/{slug}/{id}` — detalhe de um registro
- `POST /api/v1/e/{slug}` — cria registro (delega ao `RecordService`, dispara hooks e auditoria)
- `PUT /api/v1/e/{slug}/{id}` — atualiza registro
- `DELETE /api/v1/e/{slug}/{id}` — exclui registro (retorna 204)

A rota `GET /api/docs` foi descomentada e está ativa.

O `Router` ganhou suporte a `PUT`, `DELETE` e middleware encadeado por rota (`->middleware(...)`). O guard de sessão em `index.php` ignora rotas `/api/v1/*`.

---

### ✅ Permissões granulares por entidade _(concluído em v1.1)_

Implementado via:
- Tabela `entity_permissions` (entity_id, role, can_create, can_edit, can_delete)
- Guard `checkEntityPermission()` no `RecordController` (store, edit, update, destroy)
- UI na aba "Permissões" em `/entities/{id}/edit?tab=permissoes`
- Migration standalone em `install/migrations/001_entity_permissions.sql`

Quando não há linha configurada para uma entidade, o comportamento é irrestrito (retrocompatibilidade). Admins nunca são bloqueados pelo guard.

---

### ✅ Filtros avançados na listagem de registros _(concluído em v1.1)_

Implementado em `RecordController::index()` e `records/index.php`:
- Sidebar colapsável com seletor de campo + operador + valor
- 11 operadores: `eq`, `neq`, `contains`, `not_contains`, `starts_with`, `gt`, `lt`, `gte`, `lte`, `empty`, `not_empty`
- Operadores disponíveis variam por tipo de campo (texto, número, data, checkbox, select…)
- Filtros múltiplos combinados com AND
- Persistência na URL via `?filters[]=fieldId:op:valor`
- Chips dos filtros ativos com botão de remoção individual
- Busca global `?q=` mantida em paralelo (retrocompatível)

---

### ✅ Ordenação de colunas na listagem _(concluído em v1.1)_

Implementado em `RecordController::index()` e na view tabela:
- Parâmetros `?sort_field={id|created_at}&sort_dir=asc|desc` na URL
- Cabeçalhos clicáveis com ícone ↑ ↓ ↕ indicando estado da ordenação
- Ordenação via subconsulta ao EAV (`val_text`, `val_num` ou `val_date` conforme o tipo do campo)
- Filtros e paginação preservados ao trocar ordenação

---

### ✅ Exportação de registros (CSV / Excel) _(concluído em v1.1)_

Implementado como plugin `flexcore-data-exporter`:
- Botão "⬇ Exportar" injetado na barra de ações via hook `records.list.actions`
- Tela de configuração: seleção de formato (CSV ou Excel) e campos a exportar
- Filtros ativos da listagem (`?q=` e `?filters[]`) são repassados à exportação
- **CSV**: UTF-8 com BOM, separador `;`, abre corretamente no Excel
- **Excel (.xlsx)**: gerado sem dependências externas (ZipArchive + SpreadsheetML)
- Limite de 10.000 registros por exportação
- Instalar: fazer upload de `flexcore-data-exporter.zip` em Plugins

---

### ✅ Views de entidade _(concluído em v1.1)_

Implementado em `RecordController::index()` e `records/index.php`:
- **Tabela** — exibição padrão com cabeçalhos clicáveis para ordenação
- **Cards** — grid de cartões com título/subtítulo/campos extras (primeiro campo = título)
- **Kanban** — colunas geradas a partir das opções do primeiro campo `select` visível na lista
- Preferência salva por usuário por entidade em `settings` com chave `view_pref_{userId}_{entityId}`
- Seletor de view (☰ ⊞ ⊟) na barra de status, salvo via POST em `/e/{slug}/set-view`

---

### Tipos de campo adicionais

- **Rich text** (editor WYSIWYG leve, ex.: TipTap ou Quill)
- **Imagem** (upload com preview inline)
- **Rating** (1–5 estrelas)
- **Fórmula** (valor calculado a partir de outros campos numéricos)

---

### Notificações por e-mail

Action de Automação `email_notification`:
- Destinatários configuráveis (endereço fixo ou campo da entidade do tipo `email`)
- Template de assunto e corpo com placeholders `{{campo}}`
- Suporte a SMTP configurável via Settings

---

## Médio prazo (v1.3–v2.0)

### API Keys com permissões por entidade

Hoje as API Keys têm `scope: all`. Implementar:

```json
{
  "scope": "entity",
  "entities": ["clientes", "projetos"],
  "access": ["read", "write"]
}
```

O `ApiAuthMiddleware` já lê o campo `permissions` — basta implementar a verificação.

---

### Relações bidirecionais e lookup fields

O tipo `relation` já permite apontar um campo para outra entidade. Evoluir para:

- Exibir registros relacionados na view de detalhe do registro pai (painel "reverso")
- Campos lookup: exibir o valor de um campo da entidade relacionada sem sair da entidade atual
- Rollup: soma ou contagem de registros relacionados

---

### Histórico de alterações por registro

Usar o `audit_log` existente para exibir, na tela de detalhe do registro, um timeline de "quem mudou o quê e quando". Requer gravar o valor anterior e o novo no `description` do audit.

---

### Automações com múltiplas ações por regra

Hoje cada automação tem uma única ação. Evoluir para:

- Array de ações em sequência (webhook → e-mail → atualizar campo)
- Ações condicionais dentro da sequência
- `action_config` vira `actions_config: [{type, config}, ...]`

---

### Ações de automação nativas adicionais

- **`update_record`** — atualiza um campo do próprio registro ou de um registro relacionado
- **`create_record`** — cria um novo registro em outra entidade
- **`http_request`** — request HTTP genérico com método, headers e body customizáveis (evolução do webhook)

---

### Dashboard personalizável

Hoje o dashboard exibe contagem de entidades e últimos registros. Evoluir para:

- Widgets configuráveis: contagem, tabela, gráfico de barras/linhas
- Widgets baseados em filtros (ex.: "registros criados esta semana")
- Layout de colunas arrastável

---

### Gerenciamento de múltiplos usuários com convite por e-mail

- Fluxo de convite: admin digita e-mail → sistema envia link de ativação → novo usuário define a senha
- Exige configuração de SMTP (viabilizada pelo item de notificações por e-mail)

---

### Importador de dados — melhorias

O plugin `flexcore-data-importer` já existe. Próximos passos:

- Importação de relações (resolver por valor de campo em vez de ID)
- Preview de N linhas antes de confirmar a importação
- Relatório de erros por linha
- Suporte a XLSX além de CSV

---

### PSR-4 Autoload via Composer

Introduzir o autoload do Composer para eliminar o carregamento manual em cascata no `bootstrap.php`. Manter compatibilidade com hospedagens sem Composer disponível via script de build que gera um `vendor/autoload.php` embutido.

---

## Visão de longo prazo

### API pública para plugins (Marketplace)

Sistema centralizado (ou via GitHub Releases) para descobrir e instalar plugins sem upload manual. O `PluginController` já tem a estrutura de instalação via ZIP — a etapa seguinte é uma UI de busca conectada a um repositório remoto.

---

### Múltiplos workspaces / multi-tenant

Isolar dados por workspace, permitindo que uma única instalação sirva múltiplas equipes ou clientes. Requer:

- Coluna `workspace_id` em todas as tabelas de dados
- Middleware de workspace
- UI de criação e troca de workspace

---

### Suporte a PostgreSQL

O `lib/DB.php` é um wrapper PDO puro. O schema SQL usa algumas sintaxes MySQL-específicas (ex.: `AUTO_INCREMENT`, `TINYINT`). Com ajustes no schema e no `DB.php`, o suporte a PostgreSQL é viável e amplia os ambientes de deploy.

---

### CLI para gerenciamento

Comandos de linha de comando para:

```bash
php flexcore make:entity nome_entidade
php flexcore make:plugin nome-plugin
php flexcore migrate
php flexcore user:create admin@email.com
```

---

## Contribuindo com o roadmap

Qualquer item aqui pode se tornar uma issue ou pull request. Consulte o [CONTRIBUTING.md](CONTRIBUTING.md) para entender como propor uma funcionalidade, reportar um bug ou submeter código.

Se você tem interesse em trabalhar em um item específico, abra uma issue para alinhar a abordagem antes de iniciar a implementação.