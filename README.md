# FlexCore

**FlexCore** is a self-hosted dynamic data framework built with PHP. It allows you to create custom entities (tables), define fields of any type, manage records through a web interface, and expose everything through a REST API — without writing a single line of code or touching the database.

Think of it as a “self-hosted Airtable” with support for automations, a plugin system, full audit logging, and internationalization.

---

## Features

### **Dynamic Entities**

Create as many “tables” as you want — Customers, Projects, Leads, Contracts, etc. — directly from the interface. Each entity has a name, unique slug, icon, and identification color.

### **Typed Fields**

Each entity supports 29 field types, organized into 8 groups:

* **Text & communication** — short text, long text, rich text (HTML), email, URL, phone, password/sensitive data
* **Numbers & values** — number, currency, percentage, rating (1–5 stars), progress (0–100%), duration
* **Date & time** — date, datetime, time, date range
* **Selections & lists** — select (single), multiselect, checkbox, free tags, system user, color picker
* **Relationships** — relation to another entity
* **Special data** — UUID (auto-generated), raw JSON, IP/hostname
* **Media & files** — image (PNG/JPG/WEBP/GIF) and generic file uploads, both stored as base64 in the database (`MEDIUMTEXT`, ~16MB max)

### **Complete Record CRUD**

Three listing modes — table (with ASC/DESC column sorting), cards, and kanban — including advanced field filtering (11 operators), global search, and pagination. Create/edit forms, detail views, and deletion are all automatically generated from field definitions. View preferences are saved per user and per entity.

### **Complete REST API**

Every entity is automatically exposed through a full CRUD API. Authentication uses API Keys (Bearer token), with sliding-window rate limiting (60 seconds), pagination, field filters, and sorting support. Interactive documentation is available at `/api/docs`.

### **Automations**

Configure rules like “if X happens, do Y” without code. Trigger actions when records are created, updated, or deleted, with optional field-based conditions. Available action: Webhook (POST/PUT/PATCH) with automatic retry support (3 attempts, exponential backoff).

### **Plugin System**

Extend FlexCore without modifying the core. Plugins are folders containing `plugin.json` + `Plugin.php`. The Hooks system (Actions and Filters) allows interception of any record lifecycle event.

### **Users & Access Control**

Three global roles: `admin` (full access), `editor` (can create and edit records), and `viewer` (read-only). Granular entity permissions allow configuring whether each role can create, edit, or delete records for a specific entity.

### **Audit Logging**

Every relevant event — entity creation, updates, deletions, and record changes — is logged in `audit_log` with user, IP address, and description.

### **Internationalization**

Interface available in Portuguese (`pt_BR`), English (`en_US`), Spanish (`es`), French (`fr`), and German (`de`). Language can be configured globally or per user.

### **Web Installer**

Guided installation wizard — simply point your domain and provide your database credentials.

---

## Requirements

* PHP 7.4 or higher
* MySQL 5.7+ or MariaDB 10.3+
* Web server with `mod_rewrite` (Apache) or `try_files` (Nginx) support
* PHP extensions: `pdo`, `pdo_mysql`, `json`, `mbstring`

---

## Installation

### **1. Upload the files to your server**

```bash
git clone https://github.com/marcossancal/FlexCore
```

### **2. Configure the web server**

The `.htaccess` file is already included for Apache. For Nginx, add this to your `server` block:

```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

### **3. Run the installer**

Open `https://yourdomain.com/install/` in your browser and follow the wizard. The installer will:

* Validate the database connection
* Create all required tables
* Ask for the administrator account information
* Generate the `.env` file
* Create the `.installed` file to lock the installer

### **4. Login**

Access `https://yourdomain.com/login` using the credentials created during installation.

---

## Configuration (`.env`)

```env
DB_HOST=localhost
DB_PORT=3306
DB_NAME=flexcore
DB_USER=root
DB_PASS=your_password
APP_URL=https://yourdomain.com
DEBUG=false
```

Never commit your `.env` file to public repositories. Use `.env.example` as a template.

---

## Directory Structure

```text
flexcore/
├── index.php               # Entry point
├── config/
│   ├── bootstrap.php       # Bootstrap: env, session, autoload
│   ├── container.php       # DI Container (bindings)
│   └── routes.php          # Central route map
├── core/
│   ├── Container/          # Dependency injection
│   ├── Hooks/              # Event system (Actions + Filters)
│   └── Router/             # HTTP router (GET, POST, PUT, DELETE + middleware)
├── app/
│   ├── Controllers/        # MVC controllers (web interface)
│   ├── Repositories/       # Data access
│   ├── Services/           # Business logic
│   └── views/              # PHP templates
├── api/
│   ├── Controllers/        # REST API controllers
│   ├── Formatters/         # JSON response formatting
│   └── Middleware/         # API Auth + Rate Limiting
├── modules/
│   ├── Automations/        # Automation engine + actions
│   └── Plugins/            # Plugin loader and interfaces
├── plugins/                # Installed plugins
├── lib/
│   ├── DB.php              # PDO wrapper
│   ├── Auth.php            # Session authentication
│   └── helpers.php         # Global helper functions
├── translates/             # Language files (JSON)
├── install/                # Installation wizard
└── docs/                   # Plugin documentation
```

---

## Quick Start

### **Create an Entity**

1. Go to **Entities → New Entity**
2. Define the name, slug, icon, and color
3. Under **Fields**, add the desired fields
4. Start creating records at **`/e/{slug}`**

### **Create an Automation**

1. Go to **Automations → New Automation**
2. Select the entity and event (create/update/delete)
3. Define optional field-based conditions
4. Configure the destination webhook URL

---

## REST API

The API uses Bearer token authentication. Generate a key in **API & Keys → New Key** and include it in all requests:

```text
Authorization: Bearer fc_your_api_key_here
```

### Endpoints

| Method   | Route                   | Description              |
| -------- | ----------------------- | ------------------------ |
| `GET`    | `/api/v1/entities`      | List active entities     |
| `GET`    | `/api/v1/e/{slug}`      | List records (paginated) |
| `GET`    | `/api/v1/e/{slug}/{id}` | Record details           |
| `POST`   | `/api/v1/e/{slug}`      | Create a record          |
| `PUT`    | `/api/v1/e/{slug}/{id}` | Update a record          |
| `DELETE` | `/api/v1/e/{slug}/{id}` | Delete a record (204)    |

### Listing Parameters (`GET /api/v1/e/{slug}`)

| Parameter      | Type        | Default | Description                             |
| -------------- | ----------- | ------- | --------------------------------------- |
| `page`         | int         | `1`     | Current page                            |
| `per_page`     | int         | `25`    | Records per page (max. 100)             |
| `q`            | string      | —       | Global search across text fields        |
| `{field_slug}` | string      | —       | Exact match filter for a specific field |
| `sort`         | string      | —       | Field slug used for sorting             |
| `dir`          | `asc\|desc` | `desc`  | Sorting direction                       |

### Response Format

```json
{
  "data": [
    {
      "id": 42,
      "created_at": "2026-05-11 10:30:00",
      "updated_at": "2026-05-11 10:30:00",
      "fields": {
        "name": "Maria Silva",
        "email": "maria@email.com",
        "amount": 1500.00,
        "active": true
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

Fields inside `fields` are indexed by the field **slug** (not by `id`). Numeric field types return `float`, checkboxes return `bool`, and multiselect fields return `array`.

### curl Examples

```bash
# List records with filters and pagination
curl -H "Authorization: Bearer fc_your_key" \
  "https://yourdomain.com/api/v1/e/customers?page=1&per_page=10&sort=name&dir=asc"

# Filter by a specific field
curl -H "Authorization: Bearer fc_your_key" \
  "https://yourdomain.com/api/v1/e/customers?city=NewYork"

# Global search
curl -H "Authorization: Bearer fc_your_key" \
  "https://yourdomain.com/api/v1/e/customers?q=maria"

# Create record (JSON)
curl -X POST \
  -H "Authorization: Bearer fc_your_key" \
  -H "Content-Type: application/json" \
  -d '{"name": "John Smith", "email": "john@email.com", "amount": 2500}' \
  "https://yourdomain.com/api/v1/e/customers"

# Update record
curl -X PUT \
  -H "Authorization: Bearer fc_your_key" \
  -H "Content-Type: application/json" \
  -d '{"name": "John Smith Jr."}' \
  "https://yourdomain.com/api/v1/e/customers/42"

# Delete record
curl -X DELETE \
  -H "Authorization: Bearer fc_your_key" \
  "https://yourdomain.com/api/v1/e/customers/42"
```

### Error Codes

| Code  | Situation                                           |
| ----- | --------------------------------------------------- |
| `401` | Missing, invalid, or expired API key                |
| `403` | API key lacks permission for this entity            |
| `404` | Entity or record not found                          |
| `422` | Missing or invalid required field                   |
| `429` | Rate limit exceeded (`Retry-After` header included) |

---

## Installing a Plugin

1. Go to **Plugins → Install Plugin**
2. Upload the plugin `.zip` file
3. Activate the plugin from the list
4. Configure plugin options (if available)

FlexCore already ships with two plugins:

* **FlexCore Data Importer** — bulk import records from CSV with column-to-field mapping.
* **FlexCore Data Exporter** — export records to CSV or Excel (`.xlsx`), respecting active filters and allowing field selection. An “⬇ Export” button automatically appears in each entity listing.

---

## Security

* Passwords stored using `password_hash()` (bcrypt)
* API Keys stored as SHA-256 hashes (never in plain text)
* All queries use prepared statements via PDO
* HTML output escaped with `htmlspecialchars()`
* Sliding-window rate limiting (60s per key)
* Session guard applied to all web routes (except `/login`)
* `/api/v1/*` routes ignore sessions — authentication exclusively through API Keys
* Granular entity permissions control record creation, editing, and deletion per role

---

## License

Distributed under the [GPLv3](LICENSE) license.
