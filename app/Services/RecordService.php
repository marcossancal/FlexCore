<?php

namespace FlexCore\App\Services;

use FlexCore\Core\Hooks\Hooks;

/**
 * RecordService — orquestra o ciclo de vida dos registros.
 * Compatible: PHP 7.4+
 */
class RecordService
{
    /** @var object */
    private $records;
    /** @var object */
    private $fields;
    /** @var object */
    private $entities;
    /** @var object */
    private $audit;

    public function __construct($records, $fields, $entities, $audit)
    {
        $this->records  = $records;
        $this->fields   = $fields;
        $this->entities = $entities;
        $this->audit    = $audit;
    }

    public function create(int $entityId, array $rawInput, int $createdBy = 0): int
    {
        $entity = $this->entities->find($entityId);
        $fields = $this->fields->forEntity($entityId);

        $this->assertRequired($fields, $rawInput);

        Hooks::fire('record.before_create', [$entityId, $rawInput]);

        $recordId = $this->records->create([
            'entity_id'  => $entityId,
            'created_by' => $createdBy,
        ]);

        $this->saveValues($recordId, $fields, $rawInput);

        $this->audit->log('create_record', $entityId, $recordId,
            "Registro #{$recordId} criado em {$entity['name']}"
        );

        Hooks::fire('record.created', [$recordId, $entityId, $rawInput]);

        return $recordId;
    }

    public function update(int $recordId, int $entityId, array $rawInput): void
    {
        $this->records->touch($recordId);

        $fields = $this->fields->forEntity($entityId);

        Hooks::fire('record.before_update', [$recordId, $entityId, $rawInput]);

        $this->saveValues($recordId, $fields, $rawInput);

        $this->audit->log('update_record', $entityId, $recordId,
            "Registro #{$recordId} atualizado"
        );

        Hooks::fire('record.updated', [$recordId, $entityId, $rawInput]);
    }

    public function delete(int $recordId, int $entityId): void
    {
        Hooks::fire('record.before_delete', [$recordId, $entityId]);

        $this->records->delete($recordId);

        $this->audit->log('delete_record', $entityId, $recordId,
            "Registro #{$recordId} excluído"
        );

        Hooks::fire('record.deleted', [$recordId, $entityId]);
    }

    // ── Helpers ───────────────────────────────────────────────────────

    private function assertRequired(array $fields, array $input): void
    {
        $errors = [];
        foreach ($fields as $f) {
            if (!$f['required']) continue;
            $val = $input['field_' . $f['id']] ?? null;
            if ($val === null || $val === '' || $val === []) {
                $errors[] = "Campo \"{$f['name']}\" é obrigatório.";
            }
        }
        if (!empty($errors)) {
            throw new \DomainException(implode(' ', $errors));
        }
    }

    private function saveValues(int $recordId, array $fields, array $input): void
    {
        foreach ($fields as $f) {
            $key = 'field_' . $f['id'];
            $raw = $input[$key] ?? null;

            if ($f['field_type'] === 'multiselect') {
                $raw = isset($input[$key]) ? json_encode((array) $input[$key]) : null;
            } elseif ($f['field_type'] === 'checkbox') {
                $raw = isset($input[$key]) ? '1' : '0';
            }

            $this->records->saveValue($recordId, $f, $raw);
        }
    }
}