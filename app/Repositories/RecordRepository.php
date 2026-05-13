<?php

namespace FlexCore\App\Repositories;

use DB;

/**
 * RecordRepository — acesso a dados de entity_records e record_values.
 */
class RecordRepository
{
    public function find(int $id): ?array
    {
        return DB::one('SELECT * FROM entity_records WHERE id = ?', [$id]);
    }

    public function create(array $data): int
    {
        return DB::exec(
            'INSERT INTO entity_records (entity_id, created_by) VALUES (?, ?)',
            [$data['entity_id'], $data['created_by'] ?? 0]
        );
    }

    /** Atualiza updated_at sem mudar nenhum outro campo. */
    public function touch(int $id): void
    {
        DB::run(
            'UPDATE entity_records SET updated_at = NOW() WHERE id = ?',
            [$id]
        );
    }

    public function delete(int $id): int
    {
        return DB::run('DELETE FROM entity_records WHERE id = ?', [$id])->rowCount();
    }

    /**
     * Persiste um valor em record_values escolhendo a coluna correta.
     *
     * Mapeamento de tipos → coluna:
     *   val_num  — number, currency, percent, rating, progress, duration
     *   val_date — date, datetime
     *   val_text — todos os demais (texto, JSON, base64, etc.)
     *
     * @param array  $field  Array do campo (field_type, id)
     * @param mixed  $raw    Valor a persistir
     */
    public function saveValue(int $recordId, array $field, mixed $raw): void
    {
        $valText = null;
        $valNum  = null;
        $valDate = null;

        if ($raw !== null && $raw !== '') {
            if (isNumericType($field['field_type'])) {
                $valNum = is_numeric($raw) ? (float) $raw : null;
            } elseif (isDateType($field['field_type'])) {
                $valDate = $raw;
            } else {
                $valText = (string) $raw;
            }
        }

        DB::exec(
            'INSERT INTO record_values (record_id, field_id, val_text, val_num, val_date)
             VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                 val_text = VALUES(val_text),
                 val_num  = VALUES(val_num),
                 val_date = VALUES(val_date)',
            [$recordId, $field['id'], $valText, $valNum, $valDate]
        );
    }

    /**
     * Carrega todos os valores de um registro, indexados por field_id.
     * Retorna a coluna não-nula com prioridade: val_text > val_num > val_date.
     */
    public function loadValues(int $recordId): array
    {
        $rows = DB::q(
            'SELECT field_id, val_text, val_num, val_date
               FROM record_values WHERE record_id = ?',
            [$recordId]
        );

        $out = [];
        foreach ($rows as $v) {
            if ($v['val_text'] !== null) {
                $out[$v['field_id']] = $v['val_text'];
            } elseif ($v['val_num'] !== null) {
                $out[$v['field_id']] = (string) $v['val_num'];
            } else {
                $out[$v['field_id']] = $v['val_date'];
            }
        }
        return $out;
    }

    /**
     * Busca global em val_text (todos os campos de texto).
     */
    public function searchText(int $entityId, string $q, int $limit = 25, int $offset = 0): array
    {
        $like = '%' . $q . '%';
        return DB::q(
            "SELECT DISTINCT r.id, r.created_at
               FROM entity_records r
               JOIN record_values rv ON rv.record_id = r.id
              WHERE r.entity_id = ? AND rv.val_text LIKE ?
              LIMIT {$limit} OFFSET {$offset}",
            [$entityId, $like]
        );
    }

    public function countFiltered(int $entityId, string $whereExtra, array $bindParams): int
    {
        $sql = "SELECT COUNT(*) AS n FROM entity_records r WHERE r.entity_id = ? {$whereExtra}";
        $row = DB::one($sql, array_merge([$entityId], $bindParams));
        return (int) ($row['n'] ?? 0);
    }

    public function listPaginated(int $entityId, string $whereExtra, array $bindParams, string $orderSql, int $limit, int $offset): array
    {
        $sql = "SELECT r.* FROM entity_records r WHERE r.entity_id = ? {$whereExtra} {$orderSql} LIMIT {$limit} OFFSET {$offset}";
        return DB::q($sql, array_merge([$entityId], $bindParams));
    }

    public function searchValueIds(array $fieldIds, string $q): array
    {
        if (empty($fieldIds)) return [];
        $in   = implode(',', array_map('intval', $fieldIds));
        $like = '%' . $q . '%';
        $rows = DB::q(
            "SELECT DISTINCT record_id FROM record_values WHERE field_id IN ({$in}) AND val_text LIKE ?",
            [$like]
        );
        return array_column($rows, 'record_id');
    }
    public function loadValuesBatch(array $recordIds, array $fields): array
    {
        if (empty($recordIds)) return [];

        $in = implode(',', array_map('intval', $recordIds));
        $rows = DB::q(
            "SELECT record_id, field_id, val_text, val_num, val_date
            FROM record_values
            WHERE record_id IN ({$in})",
            []
        );
        $map = [];
        foreach ($rows as $v) {
            $map[$v['record_id']][$v['field_id']] = $v;
        }
        return $map;
    }

}
