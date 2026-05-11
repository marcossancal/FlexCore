# FlexCore

**FlexCore** é um framework de dados dinâmico, auto-hospedado, construído em PHP. Permite criar entidades personalizadas (tabelas), definir campos de qualquer tipo, gerenciar registros via interface web e expor tudo por uma API REST — sem escrever uma linha de código ou mexer no banco de dados.

Pense nele como um "Airtable self-hosted" com suporte a automações, sistema de plugins, auditoria completa e internacionalização.

---

## Funcionalidades

**Entidades dinâmicas**
Crie quantas "tabelas" quiser — Clientes, Projetos, Leads, Contratos etc. — diretamente pela interface. Cada entidade tem nome, slug único, ícone e cor de identificação.

**Campos tipados**
Cada entidade aceita campos de 14 tipos: texto curto, texto longo, número, moeda, e-mail, URL, telefone, data, data/hora, lista (simples e múltipla), checkbox, relação com outra entidade e arquivo.

**CRUD completo de registros**
Listagem em três modos — tabela (com ordenação por coluna ASC/DESC), cards e kanban — com filtros avançados por campo (11 operadores), busca global e paginação. Formulário de criação/edição, visualização de detalhe e exclusão — tudo gerado automaticamente a partir da definição de campos. Preferência de visualização salva por usuário e por entidade.

**API REST completa**
Toda entidade é exposta via API com CRUD completo. Autenticação por API Key (Bearer token), rate limiting por janela deslizante de 60 segundos, paginação, filtros por campo e ordenação. Documentação interativa disponível em `/api/docs`.

**Automações**
Configure regras "se acontecer X, faça Y" sem código. Dispare ações ao criar, atualizar ou deletar registros, com condições opcionais por campo. Ação disponível: Webhook (POST/PUT/PATCH) com retry automático (3 tentativas, backoff exponencial).

**Sistema de Plugins**
Estenda o FlexCore sem modificar o core. Plugins são pastas com `plugin.json` + `Plugin.php`. O sistema de Hooks (Actions e Filters) permite interceptar qualquer evento do ciclo de vida dos registros.

**Usuários e controle de acesso**
Três papéis globais: `admin` (acesso total), `editor` (cria e edita registros), `viewer` (somente leitura). Permissões granulares por entidade permitem configurar, para cada papel, se pode criar, editar ou excluir registros daquela entidade específica.

**Auditoria**
Todo evento relevante — criação, edição, exclusão de entidades e registros — é registrado no `audit_log` com usuário, IP e descrição.

**Internacionalização**
Interface disponível em Português (pt_BR), Inglês (en_US), Espanhol (es), Francês (fr) e Alemão (de). Idioma configurável por usuário ou globalmente.

**Instalador web**
Wizard de instalação guiado — basta apontar o domínio e fornecer as credenciais do banco.

---

## Requisitos

- PHP 7.4 ou superior
- MySQL 5.7+ ou MariaDB 10.3+
- Servidor web com suporte a `mod_rewrite` (Apache) ou `try_files` (Nginx)
- Extensões PHP: `pdo`, `pdo_mysql`, `json`, `mbstring`

---

## Instalação

**1. Faça o upload dos arquivos para o servidor**

```bash
git clone https://github.com/marcossancal/FlexCore
```

**2. Configure o servidor web**

O arquivo `.htaccess` já está incluído para Apache. Para Nginx, adicione ao bloco `server`:

```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

**3. Acesse o instalador**

Abra `https://seusite.com/install/` no navegador e siga o wizard. O instalador vai:
- Validar a conexão com o banco
- Criar todas as tabelas
- Solicitar os dados do usuário administrador
- Gerar o arquivo `.env`
- Criar o arquivo `.installed` para travar o instalador

**4. Faça login**

Acesse `https://seusite.com/login` com as credenciais criadas na instalação.

---

## Configuração (`.env`)

```env
DB_HOST=localhost
DB_PORT=3306
DB_NAME=flexcore
DB_USER=root
DB_PASS=sua_senha
APP_URL=https://seusite.com.br
DEBUG=false
```

Nunca comita o `.env` em repositórios públicos. Use `.env.example` como modelo.

---

## Estrutura de diretórios

```
flexcore/
├── index.php               # Entry point
├── config/
│   ├── bootstrap.php       # Bootstrap: env, sessão, autoload
│   ├── container.php       # DI Container (bindings)
│   └── routes.php          # Mapa central de rotas
├── core/
│   ├── Container/          # Injeção de dependência
│   ├── Hooks/              # Sistema de eventos (Actions + Filters)
│   └── Router/             # Roteador HTTP (GET, POST, PUT, DELETE + middleware)
├── app/
│   ├── Controllers/        # Controllers MVC (interface web)
│   ├── Repositories/       # Acesso a dados
│   ├── Services/           # Lógica de negócio
│   └── views/              # Templates PHP
├── api/
│   ├── Controllers/        # Controllers da API REST
│   ├── Formatters/         # Formatação de respostas JSON
│   └── Middleware/         # Auth + Rate Limiting da API
├── modules/
│   ├── Automations/        # Engine de automações + actions
│   └── Plugins/            # Loader e interfaces de plugins
├── plugins/                # Plugins instalados
├── lib/
│   ├── DB.php              # Wrapper PDO
│   ├── Auth.php            # Autenticação de sessão
│   └── helpers.php         # Funções globais
├── translates/             # Arquivos de idioma (JSON)
├── install/                # Wizard de instalação
└── docs/                   # Documentação de plugins
```

---

## Uso rápido

**Criar uma entidade**

1. Vá em **Entidades → Nova entidade**
2. Defina nome, slug, ícone e cor
3. Em **Campos**, adicione os campos desejados
4. Comece a cadastrar registros em **`/e/{slug}`**

**Criar uma automação**

1. Vá em **Automações → Nova automação**
2. Selecione a entidade e o evento (criação/atualização/exclusão)
3. Defina condições opcionais por campo
4. Configure a URL do webhook de destino

---

## API REST

A API usa autenticação por Bearer token. Gere uma chave em **API & Chaves → Nova chave** e inclua no header de todas as requisições:

```
Authorization: Bearer fc_sua_chave_aqui
```

### Endpoints

| Método | Rota | Descrição |
|--------|------|-----------|
| `GET` | `/api/v1/entities` | Lista entidades ativas |
| `GET` | `/api/v1/e/{slug}` | Lista registros (paginado) |
| `GET` | `/api/v1/e/{slug}/{id}` | Detalhe de um registro |
| `POST` | `/api/v1/e/{slug}` | Cria um registro |
| `PUT` | `/api/v1/e/{slug}/{id}` | Atualiza um registro |
| `DELETE` | `/api/v1/e/{slug}/{id}` | Exclui um registro (204) |

### Parâmetros de listagem (`GET /api/v1/e/{slug}`)

| Parâmetro | Tipo | Padrão | Descrição |
|-----------|------|--------|-----------|
| `page` | int | `1` | Página atual |
| `per_page` | int | `25` | Registros por página (máx. 100) |
| `q` | string | — | Busca global em todos os campos de texto |
| `{slug_do_campo}` | string | — | Filtro por campo específico (valor exato) |
| `sort` | string | — | Slug do campo para ordenar |
| `dir` | `asc\|desc` | `desc` | Direção da ordenação |

### Formato de resposta

```json
{
  "data": [
    {
      "id": 42,
      "created_at": "2026-05-11 10:30:00",
      "updated_at": "2026-05-11 10:30:00",
      "fields": {
        "nome": "Maria Silva",
        "email": "maria@email.com",
        "valor": 1500.00,
        "ativo": true
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

Os campos em `fields` são indexados pelo **slug** do campo (não pelo `id`). Tipos numéricos retornam `float`, checkboxes retornam `bool`, multiselect retornam `array`.

### Exemplos com curl

```bash
# Listar registros com filtro e paginação
curl -H "Authorization: Bearer fc_sua_chave" \
  "https://seusite.com/api/v1/e/clientes?page=1&per_page=10&sort=nome&dir=asc"

# Filtrar por campo específico
curl -H "Authorization: Bearer fc_sua_chave" \
  "https://seusite.com/api/v1/e/clientes?cidade=Guarulhos"

# Busca global
curl -H "Authorization: Bearer fc_sua_chave" \
  "https://seusite.com/api/v1/e/clientes?q=maria"

# Criar registro (JSON)
curl -X POST \
  -H "Authorization: Bearer fc_sua_chave" \
  -H "Content-Type: application/json" \
  -d '{"nome": "João Souza", "email": "joao@email.com", "valor": 2500}' \
  "https://seusite.com/api/v1/e/clientes"

# Atualizar registro
curl -X PUT \
  -H "Authorization: Bearer fc_sua_chave" \
  -H "Content-Type: application/json" \
  -d '{"nome": "João Souza Jr."}' \
  "https://seusite.com/api/v1/e/clientes/42"

# Excluir registro
curl -X DELETE \
  -H "Authorization: Bearer fc_sua_chave" \
  "https://seusite.com/api/v1/e/clientes/42"
```

### Códigos de erro

| Código | Situação |
|--------|---------|
| `401` | Chave ausente, inválida ou expirada |
| `403` | Chave sem permissão para esta entidade |
| `404` | Entidade ou registro não encontrado |
| `422` | Campo obrigatório ausente ou inválido |
| `429` | Rate limit atingido (`Retry-After` no header) |

---

## Instalar um plugin

1. Vá em **Plugins → Instalar plugin**
2. Faça upload do arquivo `.zip` do plugin
3. Ative o plugin na lista
4. Configure as opções do plugin (se houver)

O FlexCore já vem com dois plugins:

- **FlexCore Data Importer** — importa registros em massa via CSV com mapeamento de colunas para campos.
- **FlexCore Data Exporter** — exporta registros para CSV ou Excel (.xlsx), respeitando filtros ativos e permitindo selecionar campos. Botão "⬇ Exportar" aparece automaticamente na listagem de cada entidade.

---

## Segurança

- Senhas armazenadas com `password_hash()` (bcrypt)
- API Keys armazenadas como hash SHA-256 (nunca em texto plano)
- Todas as queries usam prepared statements via PDO
- Saídas HTML escapadas com `htmlspecialchars()`
- Rate limiting por janela deslizante de 60s por chave
- Guard de sessão em todas as rotas web (exceto `/login`)
- Rotas `/api/v1/*` ignoram sessão — autenticação exclusivamente por API Key
- Permissões granulares por entidade controlam criação, edição e exclusão de registros por papel

---

## Licença
Distribuído sob licença [GPLv3](LICENSE).