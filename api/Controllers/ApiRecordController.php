<?php

declare(strict_types=1);

namespace FlexCore\Api\Controllers;

use FlexCore\Api\Formatters\ApiResponse;

/**
 * ApiRecordController — CRUD REST de registros e entidades.
 * Compatible: PHP 7.4+
 *
 * Rotas cobertas (todas passam pelo ApiAuthMiddleware):
 *   GET  /api/v1/entities            → lista entidades ativas
 *   GET  /api/v1/e/{slug}            → lista registros (paginado + filtros)
 *   GET  /api/v1/e/{slug}/{id}       → detalhe de um registro
 *   POST /api/v1/e/{slug}            → cria registro
 *   PUT  /api/v1/e/{slug}/{id}       → atualiza registro
 *   DELETE /api/v1/e/{slug}/{id}     → exclui registro
 *
 * Formato de resposta (ApiResponse):
 *   { "data": ..., "meta": ..., "errors": null }
 *
 * Valores dos registros são retornados indexados pelo slug do campo:
 *   { "nome_cliente": "João", "valor": 1500.00 }
 */
class ApiRecordController
{
    // ── GET /api/v1/entities ─────────────────────────────────────────
    public function entities(): void
    {
        $rows = \DB::q(
            'SELECT id, name, slug, icon, description, color,
                    (SELECT COUNT(*) FROM entity_fields  WHERE entity_id = e.id) AS field_count,
                    (SELECT COUNT(*) FROM entity_records WHERE entity_id = e.id) AS record_count
               FROM entities e
              WHERE e.active = 1
              ORDER BY e.position ASC, e.name ASC'
        );

        ApiResponse::ok($rows, ['total' => count($rows)]);
    }

    // ── GET /api/v1/e/{slug} ─────────────────────────────────────────

    public function index(string $slug): void
    {
        [$entity, $fields] = $this->resolveEntity($slug);
        $this->checkPermission($entity);

        $page   = max(1, (int) ($_GET['page']   ?? 1));
        $perPage = min(100, max(1, (int) ($_GET['per_page'] ?? 25)));
        $offset  = ($page - 1) * $perPage;

        // ── Monta WHERE de filtros opcionais ──────────────────────────
        // Suporta ?q=texto (busca global) e ?field_{slug}=valor (por campo)
        [$whereExtra, $params] = $this->buildFilterWhere($fields, $entity['id']);

        $totalRow = \DB::one(
            "SELECT COUNT(*) AS c FROM entity_records r
              WHERE r.entity_id = ? {$whereExtra}",
            array_merge([$entity['id']], $params)
        );
        $total = (int) ($totalRow['c'] ?? 0);

        // Ordenação: ?sort=field_slug&dir=asc|desc
        $orderSql = $this->buildOrderSql($fields);

        $records = \DB::q(
            "SELECT r.id, r.created_at, r.updated_at
               FROM entity_records r
              WHERE r.entity_id = ? {$whereExtra}
              {$orderSql}
              LIMIT {$perPage} OFFSET {$offset}",
            array_merge([$entity['id']], $params)
        );

        $ids    = array_column($records, 'id');
        $valMap = $this->loadValuesBySlugBatch($ids, $fields);

        foreach ($records as &$r) {
            $r['fields'] = $valMap[$r['id']] ?? [];
        }
        unset($r);

        ApiResponse::ok($records, [
            'total'    => $total,
            'page'     => $page,
            'per_page' => $perPage,
            'pages'    => max(1, (int) ceil($total / $perPage)),
        ]);
    }

    // ── GET /api/v1/e/{slug}/{id} ─────────────────────────────────────

    public function show(string $slug, int $id): void
    {
        [$entity, $fields] = $this->resolveEntity($slug);
        $this->checkPermission($entity);

        $record = $this->resolveRecord($id, $entity['id']);
        $record['fields'] = $this->loadValuesBySlug($id, $fields);

        ApiResponse::ok($record);
    }

    // ── POST /api/v1/e/{slug} ─────────────────────────────────────────

    public function store(string $slug): void
    {
        [$entity, $fields] = $this->resolveEntity($slug);
        $this->checkPermission($entity, 'write');

        $body  = $this->parseBody();
        $input = $this->mapBodyToInput($body, $fields);

        try {
            $recordId = $this->recordService()->create($entity['id'], $input, 0);
        } catch (\DomainException $e) {
            ApiResponse::validationError([$e->getMessage()]);
            return;
        }

        $record           = \DB::one('SELECT * FROM entity_records WHERE id = ?', [$recordId]);
        $record['fields'] = $this->loadValuesBySlug($recordId, $fields);

        ApiResponse::created($record);
    }

    // ── PUT /api/v1/e/{slug}/{id} ─────────────────────────────────────

    public function update(string $slug, int $id): void
    {
        [$entity, $fields] = $this->resolveEntity($slug);
        $this->checkPermission($entity, 'write');

        $this->resolveRecord($id, $entity['id']);

        $body  = $this->parseBody();
        $input = $this->mapBodyToInput($body, $fields);

        try {
            $this->recordService()->update($id, $entity['id'], $input);
        } catch (\DomainException $e) {
            ApiResponse::validationError([$e->getMessage()]);
            return;
        }

        $record           = \DB::one('SELECT * FROM entity_records WHERE id = ?', [$id]);
        $record['fields'] = $this->loadValuesBySlug($id, $fields);

        ApiResponse::ok($record);
    }

    // ── DELETE /api/v1/e/{slug}/{id} ──────────────────────────────────

    public function destroy(string $slug, int $id): void
    {
        [$entity] = $this->resolveEntity($slug);
        $this->checkPermission($entity, 'write');

        $this->resolveRecord($id, $entity['id']);
        $this->recordService()->delete($id, $entity['id']);

        ApiResponse::noContent();
    }

    // ── Helpers ───────────────────────────────────────────────────────

    /**
     * Resolve entidade pelo slug ou retorna 404.
     * @return array{0: array, 1: array}  [$entity, $fields]
     */
    private function resolveEntity(string $slug): array
    {
        $entity = \DB::one('SELECT * FROM entities WHERE slug = ? AND active = 1', [$slug]);
        if (!$entity) {
            ApiResponse::notFound("Entidade '{$slug}' não encontrada.");
            exit;
        }

        $fields = \DB::q(
            'SELECT ef.*, ent.slug AS relation_slug
               FROM entity_fields ef
               LEFT JOIN entities ent ON ent.id = ef.relation_entity_id
              WHERE ef.entity_id = ?
              ORDER BY ef.position ASC',
            [$entity['id']]
        );

        return [$entity, $fields];
    }

    /** Resolve registro ou retorna 404. */
    private function resolveRecord(int $id, int $entityId): array
    {
        $record = \DB::one(
            'SELECT * FROM entity_records WHERE id = ? AND entity_id = ?',
            [$id, $entityId]
        );
        if (!$record) {
            ApiResponse::notFound("Registro #{$id} não encontrado.");
            exit;
        }
        return $record;
    }

    /**
     * Verifica permissão da API key para esta entidade.
     * scope "all" → sempre permitido.
     * scope "custom" → verifica array de entities.
     * access "read"/"write" verificado quando $require = 'write'.
     */
    private function checkPermission(array $entity, string $require = 'read'): void
    {
        // O ApiAuthMiddleware injeta a API key em $request->context['api_key'].
        // O Route::call() disponibiliza esse Request em $GLOBALS['_flexcore_request'].
        $request = $GLOBALS['_flexcore_request'] ?? null;
        $keyRow  = $request ? ($request->context['api_key'] ?? null) : null;

        // Se não há contexto de middleware, a rota não está protegida —
        // o middleware já rejeitou chaves inválidas antes de chegar aqui.
        if ($keyRow === null) return;

        $perms = json_decode($keyRow['permissions'] ?? '{}', true) ?? [];

        if (($perms['scope'] ?? 'all') === 'all') return;

        $allowed = $perms['entities'] ?? [];
        if (!in_array($entity['slug'], $allowed, true)) {
            ApiResponse::forbidden("Esta chave não tem acesso à entidade '{$entity['slug']}'.");
            exit;
        }

        if ($require === 'write') {
            $access = $perms['access'] ?? ['read', 'write'];
            if (!in_array('write', $access, true)) {
                ApiResponse::forbidden('Esta chave tem acesso somente leitura.');
                exit;
            }
        }
    }

    /**
     * Lê o body da request: JSON ou form-data.
     */
    private function parseBody(): array
    {
        $ct = $_SERVER['CONTENT_TYPE'] ?? '';
        if (strpos($ct, 'application/json') !== false) {
            $raw = file_get_contents('php://input');
            return json_decode($raw ?: '{}', true) ?? [];
        }
        return $_POST;
    }

    /**
     * Converte body da API (indexado por slug do campo) para o formato
     * interno do RecordService (indexado por field_{id}).
     */
    private function mapBodyToInput(array $body, array $fields): array
    {
        $input = [];
        foreach ($fields as $f) {
            $key    = 'field_' . $f['id'];
            $slugKey = $f['slug'];

            if (!array_key_exists($slugKey, $body)) continue;

            $raw = $body[$slugKey];

            if ($f['field_type'] === 'multiselect') {
                $input[$key] = is_array($raw) ? $raw : [$raw];
            } elseif ($f['field_type'] === 'checkbox') {
                $input[$key] = $raw ? '1' : '0';
            } else {
                $input[$key] = $raw !== null ? (string) $raw : null;
            }
        }
        return $input;
    }

    /**
     * Carrega valores de um registro indexados pelo slug do campo.
     * Tipos numéricos e de data são convertidos adequadamente.
     */
    private function loadValuesBySlug(int $recordId, array $fields): array
    {
        $rows = \DB::q(
            'SELECT field_id, val_text, val_num, val_date
            FROM record_values WHERE record_id = ?',
            [$recordId]
        );

        $byId = [];
        foreach ($rows as $v) {
            $byId[$v['field_id']] = $v;
        }

        return $this->hydrateFields($byId, $fields);
    }
    /**
     * Monta cláusula WHERE extra para filtros de listagem.
     *
     * Suporta:
     *   ?q=texto              → busca val_text LIKE %texto% em todos os campos
     *   ?field_{slug}=valor   → filtra campo específico por valor exato
     *
     * @return array{0: string, 1: array}  [$whereClause, $params]
     */
// Suporte a: ?valor__gt=100, ?nome__contains=silva, ?status__in=ativo,pendente
private function parseFilterParam(string $key, string $val, array $slugMap): ?array
{
    $op = 'eq';
    $slug = $key;

    if (str_contains($key, '__')) {
        [$slug, $op] = explode('__', $key, 2);
    }

    if (!isset($slugMap[$slug])) return null;

    return ['field' => $slugMap[$slug], 'op' => $op, 'val' => $val];
}

private function valColumn(string $fieldType): string
{
    if (isNumericType($fieldType)) return 'val_num';
    if (isDateType($fieldType))    return 'val_date';
    return 'val_text';
}

    private function buildFilterCondition(array $filter): array
    {
        $f   = $filter['field'];
        $op  = $filter['op'];
        $val = $filter['val'];
        $col = $this->valColumn($f['field_type']);
        $fId = (int) $f['id'];

        $operators = [
            'eq'          => ["rv2.{$col} = ?",           [$val]],
            'neq'         => ["rv2.{$col} != ?",          [$val]],
            'gt'          => ["rv2.{$col} > ?",            [$val]],
            'lt'          => ["rv2.{$col} < ?",            [$val]],
            'gte'         => ["rv2.{$col} >= ?",           [$val]],
            'lte'         => ["rv2.{$col} <= ?",           [$val]],
            'contains'    => ["rv2.{$col} LIKE ?",         ["%{$val}%"]],
            'starts_with' => ["rv2.{$col} LIKE ?",         ["{$val}%"]],
            'empty'       => ["(rv2.{$col} IS NULL OR rv2.{$col} = '')", []],
            'not_empty'   => ["rv2.{$col} IS NOT NULL AND rv2.{$col} != ''", []],
            'in'          => null, // tratado separado
        ];

        if ($op === 'in') {
            $vals  = array_map('trim', explode(',', $val));
            $marks = implode(',', array_fill(0, count($vals), '?'));
            $cond  = "rv2.{$col} IN ({$marks})";
            return [$fId, $cond, $vals];
        }

        [$cond, $params] = $operators[$op] ?? $operators['eq'];
        return [$fId, $cond, $params];
    }

    /**
     * Monta ORDER BY a partir de ?sort=field_slug&dir=asc|desc.
     * Fallback: created_at DESC.
     */
    private function buildOrderSql(array $fields): string
    {
        $sortSlug = trim($_GET['sort'] ?? '');
        $dir      = strtoupper(trim($_GET['dir']  ?? 'DESC'));
        $dir      = in_array($dir, ['ASC', 'DESC'], true) ? $dir : 'DESC';

        if ($sortSlug === '') {
            return "ORDER BY r.created_at {$dir}";
        }

        $slugMap = array_column($fields, null, 'slug');
        if (!isset($slugMap[$sortSlug])) {
            return "ORDER BY r.created_at {$dir}";
        }

        $f   = $slugMap[$sortSlug];
        $col = in_array($f['field_type'], ['number', 'currency'], true)
            ? 'val_num'
            : (in_array($f['field_type'], ['date', 'datetime'], true) ? 'val_date' : 'val_text');

        $fId = (int) $f['id'];
        return "ORDER BY (
            SELECT rv_sort.{$col} FROM record_values rv_sort
             WHERE rv_sort.record_id = r.id AND rv_sort.field_id = {$fId}
             LIMIT 1
        ) {$dir}";
    }

    /** Retorna o RecordService do Container de DI. */
    private function recordService(): \FlexCore\App\Services\RecordService
    {
        return \FlexCore\Core\Container\Container::getInstance()
            ->make(\FlexCore\App\Services\RecordService::class);
    }

    private function loadValuesBySlugBatch(array $recordIds, array $fields): array
    {
        if (empty($recordIds)) return [];

        $in   = implode(',', array_map('intval', $recordIds));
        $rows = \DB::q(
            "SELECT record_id, field_id, val_text, val_num, val_date
            FROM record_values
            WHERE record_id IN ({$in})"
        );

        $byRecord = [];
        foreach ($rows as $v) {
            $byRecord[$v['record_id']][$v['field_id']] = $v;
        }

        $out = [];
        foreach ($recordIds as $rid) {
            $out[$rid] = $this->hydrateFields($byRecord[$rid] ?? [], $fields);
        }

        return $out;
    }

    private function hydrateFields(array $byId, array $fields): array
    {
        $out = [];
        foreach ($fields as $f) {
            $v  = $byId[$f['id']] ?? null;
            $ft = $f['field_type'];

            if ($v === null) {
                $out[$f['slug']] = null;
                continue;
            }

            if (isNumericType($ft)) {
                $out[$f['slug']] = $v['val_num'] !== null ? (float) $v['val_num'] : null;
            } elseif (isDateType($ft)) {
                $out[$f['slug']] = $v['val_date'];
            } elseif ($ft === 'multiselect' || $ft === 'tags') {
                $out[$f['slug']] = $v['val_text'] !== null ? json_decode($v['val_text'], true) : [];
            } elseif ($ft === 'checkbox') {
                $out[$f['slug']] = $v['val_text'] === '1';
            } elseif ($ft === 'daterange' || $ft === 'json') {
                $out[$f['slug']] = $v['val_text'] !== null ? json_decode($v['val_text'], true) : null;
            } elseif ($ft === 'password') {
                $out[$f['slug']] = null;
            } elseif ($ft === 'image' || $ft === 'file') {
                $out[$f['slug']] = $v['val_text'] !== null ? '[binary]' : null;
            } else {
                $out[$f['slug']] = $v['val_text'];
            }
        }
        return $out;
    }

}