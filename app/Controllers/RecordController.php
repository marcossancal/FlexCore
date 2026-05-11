<?php

declare(strict_types=1);

namespace FlexCore\App\Controllers;
use Auth;
use DB;


/**
 * RecordController — SRP: CRUD de registros de qualquer entidade.
 *
 * Todas as rotas recebem o slug da entidade como primeiro parâmetro.
 * O controller resolve o ID e os campos antes de agir.
 *
 * Rotas cobertas:
 *   GET  /e/{slug}
 *   GET  /e/{slug}/new
 *   POST /e/{slug}/create
 *   GET  /e/{slug}/{id}
 *   GET  /e/{slug}/{id}/edit
 *   POST /e/{slug}/{id}/update
 *   POST /e/{slug}/{id}/delete
 */
class RecordController
{
    // ── List ─────────────────────────────────────────────────────────

    public function index(string $slug): void
    {
        [$entity, $fields] = $this->resolveEntity($slug);

        $list_fields = array_filter($fields, function($f) { return $f['show_in_list']; });
        $q    = trim(get('q'));
        $page = max(1, (int) get('page', 1));
        $per  = 25;

        if ($q) {
            $fieldIds = implode(',', array_column($fields, 'id') ?: [0]);
            $ids      = DB::q(
                "SELECT DISTINCT record_id FROM record_values
                  WHERE field_id IN ({$fieldIds}) AND val_text LIKE ?",
                ["%{$q}%"]
            );
            $idList  = implode(',', array_column($ids, 'record_id') ?: [0]);
            $total   = (int) DB::one(
                "SELECT COUNT(*) AS c FROM entity_records WHERE entity_id = ? AND id IN ({$idList})",
                [$entity['id']]
            )['c'];
            $offset  = ($page - 1) * $per;
            $records = $total
                ? DB::q(
                    "SELECT * FROM entity_records
                      WHERE entity_id = ? AND id IN ({$idList})
                      ORDER BY created_at DESC LIMIT {$per} OFFSET {$offset}",
                    [$entity['id']]
                )
                : [];
        } else {
            $total   = (int) DB::one(
                'SELECT COUNT(*) AS c FROM entity_records WHERE entity_id = ?',
                [$entity['id']]
            )['c'];
            $offset  = ($page - 1) * $per;
            $records = DB::q(
                "SELECT * FROM entity_records
                  WHERE entity_id = ?
                  ORDER BY created_at DESC LIMIT {$per} OFFSET {$offset}",
                [$entity['id']]
            );
        }

        foreach ($records as &$r) {
            $r['values'] = $this->loadValues($r['id']);
        }

        $pages = max(1, (int) ceil($total / $per));
        view('records/index', compact('entity', 'fields', 'list_fields', 'records', 'total', 'page', 'q', 'pages'));
    }

    // ── New form ─────────────────────────────────────────────────────

    public function create(string $slug): void
    {
        [$entity, $fields] = $this->resolveEntity($slug);
        $fields = $this->withRelationRecords($fields);
        view('records/form', compact('entity', 'fields'));
    }

    // ── Store ────────────────────────────────────────────────────────

    public function store(string $slug): void
    {
        [$entity, $fields] = $this->resolveEntity($slug);
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
        $record = $this->resolveRecord($id, $entity['id']);
        $record['values'] = $this->loadValues($id);
        $fields = $this->withRelationRecords($fields);
        view('records/form', compact('entity', 'fields', 'record'));
    }

    // ── Update ───────────────────────────────────────────────────────

    public function update(string $slug, int $id): void
    {
        [$entity, $fields] = $this->resolveEntity($slug);
        $this->resolveRecord($id, $entity['id']); // garante que existe
        $input = $this->buildInput($fields);

        $this->service()->update($id, $entity['id'], $input);

        flash('ok', 'Registro salvo!');
        redirect("/e/{$slug}/{$id}");
    }

    // ── Delete ───────────────────────────────────────────────────────

    public function destroy(string $slug, int $id): void
    {
        [$entity] = $this->resolveEntity($slug);
        $this->resolveRecord($id, $entity['id']); // garante que existe

        $this->service()->delete($id, $entity['id']);

        flash('ok', 'Registro excluído.');
        redirect("/e/{$slug}");
    }

    // ── Helpers ──────────────────────────────────────────────────────

    /**
     * Resolve entidade pelo slug ou responde 404.
     * @return array{0: array, 1: array}  [$entity, $fields]
     */
    private function resolveEntity(string $slug): array
    {
        $entity = DB::one('SELECT * FROM entities WHERE slug = ? AND active = 1', [$slug]);
        if (!$entity) { http_response_code(404); view('errors/404'); exit; }

        $fields = DB::q(
            'SELECT ef.*, ent.name AS relation_name
               FROM entity_fields ef
               LEFT JOIN entities ent ON ent.id = ef.relation_entity_id
              WHERE ef.entity_id = ?
              ORDER BY ef.position ASC',
            [$entity['id']]
        );

        return [$entity, $fields];
    }

    /** Resolve registro ou responde 404. */
    private function resolveRecord(int $id, int $entityId): array
    {
        $record = DB::one(
            'SELECT * FROM entity_records WHERE id = ? AND entity_id = ?',
            [$id, $entityId]
        );
        if (!$record) { http_response_code(404); view('errors/404'); exit; }
        return $record;
    }

    /** Carrega todos os valores de um registro em um array [field_id => valor]. */
    private function loadValues(int $recordId): array
    {
        $rows = DB::q(
            'SELECT field_id, val_text, val_num, val_date
               FROM record_values WHERE record_id = ?',
            [$recordId]
        );
        $out = [];
        foreach ($rows as $v) {
            $out[$v['field_id']] = $v['val_text']
                ?? ($v['val_num'] !== null ? (string) $v['val_num'] : ($v['val_date'] ?? null));
        }
        return $out;
    }

    /**
     * Monta o array de input no formato field_{id} => valor
     * a partir do $_POST, respeitando multiselect e checkbox.
     */
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

    /** Retorna o RecordService resolvido pelo Container. */
    private function service(): \FlexCore\App\Services\RecordService
    {
        return \FlexCore\Core\Container\Container::getInstance()
            ->make(\FlexCore\App\Services\RecordService::class);
    }

    /** Carrega os registros disponíveis para cada campo relation. */
    private function withRelationRecords(array $fields): array
    {
        foreach ($fields as &$f) {
            if ($f['field_type'] !== 'relation' || !$f['relation_entity_id']) continue;

            $rf = DB::one(
                'SELECT id FROM entity_fields
                  WHERE entity_id = ? AND show_in_list = 1
                  ORDER BY position ASC LIMIT 1',
                [$f['relation_entity_id']]
            );
            $f['relation_records'] = DB::q(
                "SELECT r.id, rv.val_text AS label
                   FROM entity_records r
                   LEFT JOIN record_values rv
                          ON rv.record_id = r.id AND rv.field_id = " . ($rf['id'] ?? 0) . "
                  WHERE r.entity_id = ?
                  ORDER BY r.created_at DESC LIMIT 200",
                [$f['relation_entity_id']]
            );
        }
        return $fields;
    }
}