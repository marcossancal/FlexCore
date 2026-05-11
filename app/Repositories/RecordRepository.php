<?php

declare(strict_types=1);

namespace FlexCore\App\Repositories;

/**
 * RecordRepository — persiste registros e valores (EAV).
 *
 * Usado pelo RecordService para isolar toda a camada SQL
 * de entity_records e record_values.
 */
class RecordRepository extends BaseRepository
{
    protected $table = 'entity_records';

    // ── Registro ──────────────────────────────────────────────────────

    /** Atualiza updated_at sem mudar nenhum outro campo. */
    public function touch(int $recordId): void
    {
        \DB::run(
            'UPDATE entity_records SET updated_at = NOW() WHERE id = ?',
            [$recordId]
        );
    }

    // ── Valores EAV ───────────────────────────────────────────────────

    /**
     * Persiste um valor de campo (UPSERT).
     *
     * @param int   $recordId ID do registro
     * @param array $field    Array do campo (field_type, id)
     * @param mixed $raw      Valor bruto do formulário
     */
    public function saveValue(int $recordId, array $field, mixed $raw): void
    {
        if ($raw === null || $raw === '') {
            \DB::run(
                'DELETE FROM record_values WHERE record_id = ? AND field_id = ?',
                [$recordId, $field['id']]
            );
            return;
        }

        [$valText, $valNum, $valDate] = [null, null, null];

        if (in_array($field['field_type'], ['number', 'currency'], true)) {
            $valNum = (float) $raw;
        } elseif (in_array($field['field_type'], ['date', 'datetime'], true)) {
            $valDate = $raw;
        } else {
            $valText = (string) $raw;
        }

        \DB::run(
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
     * Carrega todos os valores de um registro em [field_id => valor].
     */
    public function loadValues(int $recordId): array
    {
        $rows = \DB::q(
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
}
