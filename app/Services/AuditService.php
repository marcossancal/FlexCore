<?php

declare(strict_types=1);

namespace FlexCore\App\Services;

/**
 * AuditService — grava e reverte entradas na tabela audit_log.
 *
 * A função global audit() em helpers.php já cuida da migration e da gravação.
 * Este serviço é responsável apenas pelo revert().
 *
 * Rollbacks suportados:
 *   create_record  → exclui o registro criado
 *   update_record  → restaura valores anteriores dos campos
 *   delete_record  → impossível (dados excluídos do banco)
 *   create_entity  → exclui a entidade (se vazia)
 *   update_entity  → restaura dados anteriores da entidade
 *   delete_entity  → recria a entidade com todos os campos originais
 */
class AuditService
{
    /**
     * Garante que as colunas extras existam.
     * Chamado pelo AuditController antes de qualquer query.
     */
    public function ensureColumns(): void
    {
        static $done = false;
        if ($done) return;
        $done = true;

        $cols = array_column(\DB::q("SHOW COLUMNS FROM audit_log"), 'Field');

        if (!in_array('before_json', $cols, true))
            \DB::run("ALTER TABLE audit_log ADD COLUMN before_json MEDIUMTEXT DEFAULT NULL AFTER description");
        if (!in_array('after_json', $cols, true))
            \DB::run("ALTER TABLE audit_log ADD COLUMN after_json MEDIUMTEXT DEFAULT NULL AFTER before_json");
        if (!in_array('reverted_by', $cols, true))
            \DB::run("ALTER TABLE audit_log ADD COLUMN reverted_by INT DEFAULT NULL AFTER after_json");
        if (!in_array('reverted_at', $cols, true))
            \DB::run("ALTER TABLE audit_log ADD COLUMN reverted_at DATETIME DEFAULT NULL AFTER reverted_by");
        if (!in_array('revert_of', $cols, true))
            \DB::run("ALTER TABLE audit_log ADD COLUMN revert_of INT DEFAULT NULL AFTER reverted_at");
    }

    /**
     * Grava uma entrada de auditoria (usado pelo RecordService via injeção).
     */
    public function log(
        string $action,
        ?int   $entityId,
        ?int   $recordId,
        string $desc,
        array  $diff   = [],
        array  $before = [],
        array  $after  = [],
        ?int   $revertOf = null
    ): int {
        $this->ensureColumns();

        $fullDesc = $desc;
        if (!empty($diff)) {
            $lines = [];
            foreach ($diff as $d) {
                $from    = is_array($d['from']) ? json_encode($d['from'], JSON_UNESCAPED_UNICODE) : (string)($d['from'] ?? '');
                $to      = is_array($d['to'])   ? json_encode($d['to'],   JSON_UNESCAPED_UNICODE) : (string)($d['to']   ?? '');
                $lines[] = "[{$d['field']}] {$from} → {$to}";
            }
            $fullDesc .= "\n" . implode("\n", $lines);
        }

        $beforeJson = empty($before) ? null : json_encode($before, JSON_UNESCAPED_UNICODE);
        $afterJson  = empty($after)  ? null : json_encode($after,  JSON_UNESCAPED_UNICODE);

        return \DB::exec(
            'INSERT INTO audit_log
                (user_id, action, entity_id, record_id, description, before_json, after_json, revert_of, ip, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())',
            [\Auth::id() ?: null, $action, $entityId, $recordId, $fullDesc,
             $beforeJson, $afterJson, $revertOf, $_SERVER['REMOTE_ADDR'] ?? null]
        );
    }

    // ── Rollback ──────────────────────────────────────────────────────

    /**
     * @throws \RuntimeException com mensagem amigável para exibir ao usuário
     */
    public function revert(int $auditId): void
    {
        $this->ensureColumns();

        $entry = \DB::one('SELECT * FROM audit_log WHERE id = ?', [$auditId]);
        if (!$entry) {
            throw new \RuntimeException("Entrada de auditoria #{$auditId} não encontrada.");
        }
        if (!empty($entry['reverted_at'])) {
            throw new \RuntimeException(
                "Esta ação já foi desfeita em " . date('d/m/Y H:i', strtotime($entry['reverted_at'])) . "."
            );
        }

        $action   = $entry['action'];
        $recordId = (int)$entry['record_id'];
        $entityId = (int)$entry['entity_id'];
        $before   = !empty($entry['before_json']) ? json_decode($entry['before_json'], true) : [];
        $after    = !empty($entry['after_json'])  ? json_decode($entry['after_json'],  true) : [];

        switch ($action) {

            // ── Registro: desfazer edição ─────────────────────────────
            case 'update_record':
                if (empty($before)) {
                    throw new \RuntimeException(
                        "Snapshot anterior não disponível. Somente edições feitas após a atualização do sistema têm rollback."
                    );
                }
                $this->restoreRecordValues($recordId, $entityId, $before);
                \DB::run('UPDATE entity_records SET updated_at = NOW() WHERE id = ?', [$recordId]);
                $desc = "Rollback da edição do Registro #{$recordId} (desfez audit #{$auditId})";
                break;

            // ── Registro: desfazer criação ────────────────────────────
            case 'create_record':
                if (!\DB::one('SELECT id FROM entity_records WHERE id = ?', [$recordId])) {
                    throw new \RuntimeException("O registro #{$recordId} já não existe.");
                }
                \DB::run('DELETE FROM entity_records WHERE id = ?', [$recordId]);
                $desc = "Rollback da criação: Registro #{$recordId} excluído (desfez audit #{$auditId})";
                break;

            // ── Registro: desfazer exclusão ───────────────────────────
            case 'delete_record':
                throw new \RuntimeException(
                    "Não é possível desfazer uma exclusão de registro — os dados foram removidos permanentemente. Restaure um backup do banco para recuperar."
                );

            // ── Entidade: desfazer exclusão → RECRIA ─────────────────
            case 'delete_entity':
                if (empty($before) || empty($before['entity'])) {
                    throw new \RuntimeException(
                        "Snapshot da entidade não disponível. Somente exclusões feitas após a atualização do sistema têm rollback."
                    );
                }
                $entityId = $this->restoreEntity($before, $auditId);
                $entName  = $before['entity']['name'] ?? "#{$entry['entity_id']}";
                $fieldQty = count($before['fields'] ?? []);
                $desc     = "Rollback da exclusão: entidade '{$entName}' restaurada com {$fieldQty} campo(s) (desfez audit #{$auditId})";
                break;

            // ── Entidade: desfazer criação → EXCLUI ──────────────────
            case 'create_entity':
                $exists = \DB::one('SELECT id, name FROM entities WHERE id = ?', [$entityId]);
                if (!$exists) {
                    throw new \RuntimeException("A entidade #{$entityId} já não existe.");
                }
                $recCount = (int)(\DB::one(
                    'SELECT COUNT(*) AS n FROM entity_records WHERE entity_id = ?', [$entityId]
                )['n'] ?? 0);
                if ($recCount > 0) {
                    throw new \RuntimeException(
                        "A entidade possui {$recCount} registro(s). Exclua os registros antes de desfazer a criação da entidade."
                    );
                }
                \DB::run('DELETE FROM entities WHERE id = ?', [$entityId]);
                $desc = "Rollback da criação: entidade '{$exists['name']}' excluída (desfez audit #{$auditId})";
                break;

            // ── Entidade: desfazer edição → RESTAURA DADOS ────────────
            case 'update_entity':
                if (empty($before)) {
                    throw new \RuntimeException(
                        "Snapshot anterior da entidade não disponível. Somente edições feitas após a atualização do sistema têm rollback."
                    );
                }
                // update_entity tem before como array direto da linha entities
                // (não usa a chave 'entity' — isso é exclusivo do delete_entity)
                $e = isset($before['entity']) ? $before['entity'] : $before;
                \DB::run(
                    'UPDATE entities
                        SET name = ?, slug = ?, icon = ?, color = ?, description = ?,
                            position = ?, active = ?, updated_at = NOW()
                      WHERE id = ?',
                    [
                        $e['name']        ?? '',
                        $e['slug']        ?? '',
                        $e['icon']        ?? '📋',
                        $e['color']       ?? '#00d4ff',
                        $e['description'] ?? null,
                        (int)($e['position'] ?? 0),
                        (int)($e['active']   ?? 1),
                        $entityId,
                    ]
                );
                $desc = "Rollback da edição da entidade #{$entityId} (desfez audit #{$auditId})";
                break;

            default:
                throw new \RuntimeException("Rollback não suportado para a ação '{$action}'.");
        }

        // Marca entrada original como revertida
        \DB::run(
            'UPDATE audit_log SET reverted_by = ?, reverted_at = NOW() WHERE id = ?',
            [\Auth::id() ?: null, $auditId]
        );

        // Grava entrada de rollback
        $this->log('revert', $entityId ?: null, $recordId ?: null, $desc, [], [], [], $auditId);
    }

    // ── Helpers internos ──────────────────────────────────────────────

    /**
     * Restaura os valores de um registro a partir de um snapshot [field_id => value].
     */
    private function restoreRecordValues(int $recordId, int $entityId, array $snapshot): void
    {
        $fields   = \DB::q('SELECT * FROM entity_fields WHERE entity_id = ?', [$entityId]);
        $fieldMap = array_column($fields, null, 'id');

        foreach ($snapshot as $fieldId => $value) {
            $field = $fieldMap[$fieldId] ?? null;
            if (!$field) continue;

            $valText = $valNum = $valDate = null;
            if ($value !== null && $value !== '') {
                if (isNumericType($field['field_type'])) {
                    $valNum = is_numeric($value) ? (float)$value : null;
                } elseif (isDateType($field['field_type'])) {
                    $valDate = $value;
                } else {
                    $valText = (string)$value;
                }
            }

            \DB::run(
                'INSERT INTO record_values (record_id, field_id, val_text, val_num, val_date)
                 VALUES (?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE
                     val_text = VALUES(val_text),
                     val_num  = VALUES(val_num),
                     val_date = VALUES(val_date)',
                [$recordId, $fieldId, $valText, $valNum, $valDate]
            );
        }
    }

    /**
     * Recria uma entidade e seus campos a partir do snapshot do delete_entity.
     * Retorna o ID da nova entidade.
     *
     * @throws \RuntimeException se o slug já estiver em uso
     */
    private function restoreEntity(array $before, int $auditId): int
    {
        $e = $before['entity'];

        $conflict = \DB::one('SELECT id FROM entities WHERE slug = ?', [$e['slug']]);
        if ($conflict) {
            throw new \RuntimeException(
                "Não foi possível restaurar: já existe uma entidade com o slug '{$e['slug']}'. " .
                "Renomeie ou exclua essa entidade antes de desfazer."
            );
        }

        $newId = \DB::exec(
            'INSERT INTO entities (name, slug, icon, color, description, position, active, api_responses, created_by, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $e['name'],
                $e['slug'],
                $e['icon']          ?? '📋',
                $e['color']         ?? '#00d4ff',
                $e['description']   ?? null,
                (int)($e['position'] ?? 0),
                (int)($e['active']   ?? 1),
                $e['api_responses'] ?? null,
                $e['created_by']    ?? null,
                $e['created_at']    ?? date('Y-m-d H:i:s'),
            ]
        );

        foreach (($before['fields'] ?? []) as $f) {
            \DB::exec(
                'INSERT INTO entity_fields
                    (entity_id, name, slug, field_type, options_json, relation_entity_id,
                     required, show_in_list, position, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $newId,
                    $f['name'],
                    $f['slug'],
                    $f['field_type']         ?? 'text',
                    $f['options_json']       ?? null,
                    $f['relation_entity_id'] ?? null,
                    (int)($f['required']     ?? 0),
                    (int)($f['show_in_list'] ?? 1),
                    (int)($f['position']     ?? 0),
                    $f['created_at']         ?? date('Y-m-d H:i:s'),
                ]
            );
        }

        return $newId;
    }
}