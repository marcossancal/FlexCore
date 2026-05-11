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

### API REST — completar o CRUD

A API existe com autenticação e rate limiting, mas as rotas de leitura/escrita de registros precisam ser finalizadas.

- `GET /api/v1/entities` — lista entidades disponíveis
- `GET /api/v1/e/{slug}` — lista registros com paginação e filtros por campo
- `GET /api/v1/e/{slug}/{id}` — detalhe de um registro
- `POST /api/v1/e/{slug}` — cria registro (já existe lógica no `RecordService`, só falta a rota)
- `PUT /api/v1/e/{slug}/{id}` — atualiza registro
- `DELETE /api/v1/e/{slug}/{id}` — exclui registro

A rota `GET /api/docs` já existe no código (comentada em `routes.php`) — descomentá-la e conectar ao `ApiKeyController::docs()`.

---

### Permissões granulares por entidade

Hoje o controle de acesso é só por papel global (`admin`, `editor`, `viewer`). O próximo passo natural é permitir configurar, por entidade, quem pode criar, editar ou excluir registros.

- Tabela `entity_permissions` (entity_id, role, can_create, can_edit, can_delete)
- Guard no `RecordController` e no `RecordService`
- UI de permissões na tela de edição de entidade

---

### Filtros avançados na listagem de registros

A listagem atual suporta busca textual simples em todos os campos. Evoluir para:

- Filtros por campo específico (sidebar ou chips na listagem)
- Filtros por tipo: igual, contém, maior que, entre datas
- Combinação de múltiplos filtros (AND)
- Persistência dos filtros ativos na URL (query string)

---

### Ordenação de colunas na listagem

Clicar no cabeçalho de coluna para ordenar por aquele campo (ASC/DESC). Requer ajuste no `RecordController::index()` e nas views.

---

### Exportação de registros (CSV / Excel)

Botão "Exportar" na listagem que gera um CSV dos registros filtrados. Um plugin de exportação é a abordagem ideal para não poluir o core — similar ao `flexcore-data-importer`.

---

### Views de entidade

Hoje só existe a view de tabela. Adicionar:

- **Kanban** — agrupa registros por campo `select` (ex.: status)
- **Cards** — visualização em grid de cartões
- Preferência salva por usuário por entidade

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