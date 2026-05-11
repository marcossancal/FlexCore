<?php

declare(strict_types=1);

namespace FlexCore\App\Controllers;
use Auth;
use DB;

/**
 * RecordController — SRP: CRUD de registros de qualquer entidade.
 *
 * Rotas cobertas:
 *   GET  /e/{slug}
 *   GET  /e/{slug}/new
 *   POST /e/{slug}/create
 *   GET  /e/{slug}/{id}
 *   GET  /e/{slug}/{id}/edit
 *   POST /e/{slug}/{id}/update
 *   POST /e/{slug}/{id}/delete
 *   POST /e/{slug}/set-view   (salva preferência de view via form POST)
 */
class RecordController
{
    // ── List ─────────────────────────────────────────────────────────

    public function index(string $slug): void
    {
        [$entity, $fields] = $this->resolveEntity($slug);

        $list_fields = array_filter($fields, fn($f) => $f['show_in_list']);
        $page        = max(1, (int) get('page', 1));
        $per         = 25;

        // ── Preferência de view (tabela / cards / kanban) ─────────────
        // Prioridade: ?view= na URL > salvo em settings > 'table' (padrão)
        $viewKey     = 'view_pref_' . Auth::id() . '_' . $entity['id'];
        $savedView   = DB::one(
            "SELECT sval FROM settings WHERE skey = ?", [$viewKey]
        )['sval'] ?? 'table';
        $currentView = in_array(get('view'), ['table','cards','kanban'], true)
            ? get('view')
            : $savedView;
        if (!in_array($currentView, ['table','cards','kanban'], true)) {
            $currentView = 'table';
        }

        // ── Filtros avançados ─────────────────────────────────────────
        $q             = trim(get('q'));
        $rawFilters    = (array) ($_GET['filters'] ?? []);
        $activeFilters = $this->parseFilters($rawFilters, $fields);

        [$whereExtra, $bindParams] = $this->buildAdvancedWhere($q, $activeFilters, $fields, $entity['id']);

        // ── Ordenação ────────────────────────────────────────────────
        // ?sort_field=created_at|{field_id}  ?sort_dir=asc|desc
        $sortField = get('sort_field', 'created_at');
        $sortDir   = strtolower(get('sort_dir', 'desc')) === 'asc' ? 'ASC' : 'DESC';
        $fieldMap  = array_column($fields, null, 'id');

        if ($sortField === 'created_at') {
            $orderSql = "ORDER BY r.created_at {$sortDir}";
        } elseif (isset($fieldMap[(int)$sortField])) {
            $sf  = $fieldMap[(int)$sortField];
            $col = in_array($sf['field_type'], ['number','currency'], true) ? 'val_num'
                 : (in_array($sf['field_type'], ['date','datetime'], true)  ? 'val_date' : 'val_text');
            $fId = (int) $sf['id'];
            $orderSql = "ORDER BY (
                SELECT rv_s.{$col} FROM record_values rv_s
                 WHERE rv_s.record_id = r.id AND rv_s.field_id = {$fId}
                 LIMIT 1
            ) {$sortDir}";
        } else {
            $orderSql = "ORDER BY r.created_at DESC";
        }

        $total = (int) DB::one(
            "SELECT COUNT(*) AS c FROM entity_records r WHERE r.entity_id = ? {$whereExtra}",
            array_merge([$entity['id']], $bindParams)
        )['c'];

        $offset  = ($page - 1) * $per;
        $records = DB::q(
            "SELECT * FROM entity_records r
              WHERE r.entity_id = ? {$whereExtra}
              {$orderSql}
              LIMIT {$per} OFFSET {$offset}",
            array_merge([$entity['id']], $bindParams)
        );

        foreach ($records as &$r) {
            $r['values'] = $this->loadValues($r['id']);
        }
        unset($r);

        // Kanban: campo select que será usado para agrupar colunas
        // Usa o primeiro campo do tipo 'select' marcado como show_in_list, ou null
        $kanbanField = null;
        foreach ($list_fields as $f) {
            if ($f['field_type'] === 'select') { $kanbanField = $f; break; }
        }

        $pages = max(1, (int) ceil($total / $per));

        view('records/index', compact(
            'entity', 'fields', 'list_fields', 'records',
            'total', 'page', 'pages', 'q', 'activeFilters', 'rawFilters',
            'currentView', 'sortField', 'sortDir', 'kanbanField'
        ));
    }

    // ── Salva preferência de view ─────────────────────────────────────

    public function setView(string $slug): void
    {
        [$entity] = $this->resolveEntity($slug);
        $view = $_POST['view'] ?? 'table';
        if (!in_array($view, ['table','cards','kanban'], true)) $view = 'table';

        $viewKey = 'view_pref_' . Auth::id() . '_' . $entity['id'];
        DB::exec(
            "INSERT INTO settings (skey, sval, label, grp) VALUES (?, ?, '', 'view_prefs')
             ON DUPLICATE KEY UPDATE sval = VALUES(sval)",
            [$viewKey, $view]
        );

        // Reconstrói a query string de retorno preservando filtros e ordenação,
        // mas sem o parâmetro view (ele está salvo em settings agora).
        $allowed = ['q', 'filters', 'sort_field', 'sort_dir', 'page'];
        $qs = [];
        foreach ($allowed as $k) {
            if (!isset($_POST[$k]) || $_POST[$k] === '') continue;
            if (is_array($_POST[$k])) {
                foreach ($_POST[$k] as $v) { $qs[] = $k . '[]=' . urlencode($v); }
            } else {
                $qs[] = $k . '=' . urlencode($_POST[$k]);
            }
        }
        $path = '/e/' . $slug . (count($qs) ? '?' . implode('&', $qs) : '');
        redirect($path);
    }

    // ── New form ─────────────────────────────────────────────────────

    public function create(string $slug): void
    {
        [$entity, $fields] = $this->resolveEntity($slug);
        $this->checkEntityPermission($entity, 'can_create');
        $fields = $this->withRelationRecords($fields);
        view('records/form', compact('entity', 'fields'));
    }

    // ── Store ────────────────────────────────────────────────────────

    public function store(string $slug): void
    {
        [$entity, $fields] = $this->resolveEntity($slug);
        $this->checkEntityPermission($entity, 'can_create');
        $input = $this->buildInput($fields);

        try {
            $recId = $this->service()->create($entity['id'], $input, Auth::id());
        } catch (\DomainException $e) {
            flash('error', $e->getMessage());
            redirect("/e/{$slug}/new");
            return;
        }

        flash('ok', 'Registro criado!');
        redirect("/e/{$slug}/{$recId}");
    }

    // ── Show ─────────────────────────────────────────────────────────

    public function show(string $slug, int $id): void
    {
        [$entity, $fields] = $this->resolveEntity($slug);
        $record = $this->resolveRecord($id, $entity['id']);
        $record['values'] = $this->loadValues($id);
        view('records/show', compact('entity', 'fields', 'record'));
    }

    // ── Edit form ────────────────────────────────────────────────────

    public function edit(string $slug, int $id): void
    {
        [$entity, $fields] = $this->resolveEntity($slug);
        $this->checkEntityPermission($entity, 'can_edit');
        $record = $this->resolveRecord($id, $entity['id']);
        $record['values'] = $this->loadValues($id);
        $fields = $this->withRelationRecords($fields);
        view('records/form', compact('entity', 'fields', 'record'));
    }

    // ── Update ───────────────────────────────────────────────────────

    public function update(string $slug, int $id): void
    {
        [$entity, $fields] = $this->resolveEntity($slug);
        $this->checkEntityPermission($entity, 'can_edit');
        $this->resolveRecord($id, $entity['id']);
        $input = $this->buildInput($fields);
        $this->service()->update($id, $entity['id'], $input);

        flash('ok', 'Registro salvo!');
        redirect("/e/{$slug}/{$id}");
    }

    // ── Delete ───────────────────────────────────────────────────────

    public function destroy(string $slug, int $id): void
    {
        [$entity] = $this->resolveEntity($slug);
        $this->checkEntityPermission($entity, 'can_delete');
        $this->resolveRecord($id, $entity['id']);
        $this->service()->delete($id, $entity['id']);

        flash('ok', 'Registro excluído.');
        redirect("/e/{$slug}");
    }

    // ── Permission guard ─────────────────────────────────────────────

    private function checkEntityPermission(array $entity, string $op): void
    {
        $role = Auth::user()['role'] ?? 'viewer';
        if ($role === 'admin') return;

        $perm = DB::one(
            'SELECT can_create, can_edit, can_delete
               FROM entity_permissions WHERE entity_id = ? AND role = ?',
            [$entity['id'], $role]
        );
        if ($perm === null) return;

        if (empty($perm[$op])) {
            http_response_code(403); view('errors/403'); exit;
        }
    }

    // ── Advanced filter helpers ───────────────────────────────────────

    private const OPERATORS = [
        'eq'          => ['label' => 'igual a',          'col' => 'auto'],
        'neq'         => ['label' => 'diferente de',     'col' => 'auto'],
        'contains'    => ['label' => 'contém',           'col' => 'val_text'],
        'not_contains'=> ['label' => 'não contém',       'col' => 'val_text'],
        'starts_with' => ['label' => 'começa com',       'col' => 'val_text'],
        'gt'          => ['label' => 'maior que',        'col' => 'auto'],
        'lt'          => ['label' => 'menor que',        'col' => 'auto'],
        'gte'         => ['label' => 'maior ou igual a', 'col' => 'auto'],
        'lte'         => ['label' => 'menor ou igual a', 'col' => 'auto'],
        'empty'       => ['label' => 'está vazio',       'col' => 'auto'],
        'not_empty'   => ['label' => 'não está vazio',   'col' => 'auto'],
    ];

    public static function operatorsFor(string $fieldType): array
    {
        $text    = ['eq','neq','contains','not_contains','starts_with','empty','not_empty'];
        $numeric = ['eq','neq','gt','lt','gte','lte','empty','not_empty'];
        $map = [
            'text'        => $text, 'textarea' => $text, 'email' => $text,
            'url'         => $text, 'phone'    => $text,
            'select'      => ['eq','neq','empty','not_empty'],
            'multiselect' => ['contains','not_contains','empty','not_empty'],
            'number'      => $numeric, 'currency' => $numeric,
            'checkbox'    => ['eq','empty','not_empty'],
            'date'        => $numeric, 'datetime' => $numeric,
            'relation'    => ['eq','neq','empty','not_empty'],
        ];
        return array_intersect_key(self::OPERATORS, array_flip($map[$fieldType] ?? array_keys(self::OPERATORS)));
    }

    private function parseFilters(array $rawFilters, array $fields): array
    {
        $fieldMap = array_column($fields, null, 'id');
        $parsed   = [];
        foreach ($rawFilters as $raw) {
            $parts = explode(':', $raw, 3);
            if (count($parts) < 2) continue;
            [$fId, $op] = $parts;
            $value = $parts[2] ?? '';
            $fId   = (int) $fId;
            if (!isset($fieldMap[$fId]) || !isset(self::OPERATORS[$op])) continue;
            $parsed[] = ['field' => $fieldMap[$fId], 'op' => $op, 'value' => $value, 'raw' => $raw];
        }
        return $parsed;
    }

    private function buildAdvancedWhere(string $q, array $activeFilters, array $fields, int $entityId): array
    {
        $where = ''; $params = [];

        if ($q !== '') {
            $fieldIds = implode(',', array_column($fields, 'id') ?: [0]);
            $ids      = DB::q(
                "SELECT DISTINCT record_id FROM record_values WHERE field_id IN ({$fieldIds}) AND val_text LIKE ?",
                ["%{$q}%"]
            );
            $idList  = implode(',', array_column($ids, 'record_id') ?: [0]);
            $where  .= " AND r.id IN ({$idList})";
        }

        foreach ($activeFilters as $f) {
            $field = $f['field']; $op = $f['op']; $val = $f['value'];
            $fId   = (int) $field['id'];
            $col   = in_array($field['field_type'], ['number','currency'], true) ? 'val_num'
                   : (in_array($field['field_type'], ['date','datetime'], true)  ? 'val_date' : 'val_text');

            if ($op === 'empty') {
                $where .= " AND NOT EXISTS (SELECT 1 FROM record_values rv_e WHERE rv_e.record_id=r.id AND rv_e.field_id=? AND rv_e.{$col} IS NOT NULL AND rv_e.{$col}!='')";
                $params[] = $fId; continue;
            }
            if ($op === 'not_empty') {
                $where .= " AND EXISTS (SELECT 1 FROM record_values rv_ne WHERE rv_ne.record_id=r.id AND rv_ne.field_id=? AND rv_ne.{$col} IS NOT NULL AND rv_ne.{$col}!='')";
                $params[] = $fId; continue;
            }
            $sqlOp = match($op) {
                'eq'           => '= ?',
                'neq'          => '!= ?',
                'gt'           => '> ?',
                'lt'           => '< ?',
                'gte'          => '>= ?',
                'lte'          => '<= ?',
                'contains'     => "LIKE CONCAT('%',?,'%')",
                'not_contains' => "NOT LIKE CONCAT('%',?,'%')",
                'starts_with'  => "LIKE CONCAT(?,'%')",
                default        => '= ?',
            };
            $where .= " AND EXISTS (SELECT 1 FROM record_values rv_f WHERE rv_f.record_id=r.id AND rv_f.field_id=? AND rv_f.{$col} {$sqlOp})";
            $params[] = $fId; $params[] = $val;
        }

        return [$where, $params];
    }

    // ── Core helpers ─────────────────────────────────────────────────

    private function resolveEntity(string $slug): array
    {
        $entity = DB::one('SELECT * FROM entities WHERE slug = ? AND active = 1', [$slug]);
        if (!$entity) { http_response_code(404); view('errors/404'); exit; }

        $fields = DB::q(
            'SELECT ef.*, ent.name AS relation_name
               FROM entity_fields ef
               LEFT JOIN entities ent ON ent.id = ef.relation_entity_id
              WHERE ef.entity_id = ? ORDER BY ef.position ASC',
            [$entity['id']]
        );
        return [$entity, $fields];
    }

    private function resolveRecord(int $id, int $entityId): array
    {
        $record = DB::one(
            'SELECT * FROM entity_records WHERE id = ? AND entity_id = ?', [$id, $entityId]
        );
        if (!$record) { http_response_code(404); view('errors/404'); exit; }
        return $record;
    }

    private function loadValues(int $recordId): array
    {
        $rows = DB::q(
            'SELECT field_id, val_text, val_num, val_date FROM record_values WHERE record_id = ?',
            [$recordId]
        );
        $out = [];
        foreach ($rows as $v) {
            $out[$v['field_id']] = $v['val_text']
                ?? ($v['val_num'] !== null ? (string) $v['val_num'] : ($v['val_date'] ?? null));
        }
        return $out;
    }

    private function buildInput(array $fields): array
    {
        $input = [];
        foreach ($fields as $f) {
            $key = 'field_' . $f['id'];
            if ($f['field_type'] === 'multiselect') {
                $input[$key] = isset($_POST[$key]) ? (array) $_POST[$key] : null;
            } elseif ($f['field_type'] === 'checkbox') {
                $input[$key] = isset($_POST[$key]) ? '1' : '0';
            } else {
                $input[$key] = $_POST[$key] ?? null;
            }
        }
        return $input;
    }

    private function service(): \FlexCore\App\Services\RecordService
    {
        return \FlexCore\Core\Container\Container::getInstance()
            ->make(\FlexCore\App\Services\RecordService::class);
    }

    private function withRelationRecords(array $fields): array
    {
        foreach ($fields as &$f) {
            if ($f['field_type'] !== 'relation' || !$f['relation_entity_id']) continue;
            $rf = DB::one(
                'SELECT id FROM entity_fields WHERE entity_id = ? AND show_in_list = 1 ORDER BY position ASC LIMIT 1',
                [$f['relation_entity_id']]
            );
            $f['relation_records'] = DB::q(
                "SELECT r.id, rv.val_text AS label FROM entity_records r
                   LEFT JOIN record_values rv ON rv.record_id = r.id AND rv.field_id = " . ($rf['id'] ?? 0) . "
                  WHERE r.entity_id = ? ORDER BY r.created_at DESC LIMIT 200",
                [$f['relation_entity_id']]
            );
        }
        return $fields;
    }
}