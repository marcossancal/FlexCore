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

        // Snapshot after para auditoria
        $after = $this->records->loadValues($recordId);

        $this->audit->log(
            'create_record',
            $entityId,
            $recordId,
            "Registro #{$recordId} criado em {$entity['name']}",
            [],    // sem diff em criação
            [],    // sem before
            $after
        );

        Hooks::fire('record.created', [$recordId, $entityId, $rawInput]);

        return $recordId;
    }

    public function update(int $recordId, int $entityId, array $rawInput): void
    {
        // Captura snapshot ANTES
        $before = $this->records->loadValues($recordId);

        $this->records->touch($recordId);
        $fields = $this->fields->forEntity($entityId);

        Hooks::fire('record.before_update', [$recordId, $entityId, $rawInput]);

        $this->saveValues($recordId, $fields, $rawInput);

        // Captura snapshot DEPOIS e calcula diff legível
        $after = $this->records->loadValues($recordId);
        $diff  = $this->buildDiff($before, $after, $fields);

        $this->audit->log(
            'update_record',
            $entityId,
            $recordId,
            "Registro #{$recordId} atualizado",
            $diff,
            $before,
            $after
        );

        Hooks::fire('record.updated', [$recordId, $entityId, $rawInput]);
    }

    private function buildDiff(array $before, array $after, array $fields): array
    {
        $fieldMap = array_column($fields, null, 'id');
        $diff = [];

        $allKeys = array_unique(array_merge(array_keys($before), array_keys($after)));
        foreach ($allKeys as $fieldId) {
            $oldVal = $before[$fieldId] ?? null;
            $newVal = $after[$fieldId]  ?? null;
            if ($oldVal === $newVal) continue;

            $name   = $fieldMap[$fieldId]['name'] ?? "field_{$fieldId}";
            $diff[] = ['field' => $name, 'from' => $oldVal, 'to' => $newVal];
        }

        return $diff;
    }

    public function delete(int $recordId, int $entityId): void
    {
        // Captura snapshot antes de excluir
        $before = $this->records->loadValues($recordId);

        Hooks::fire('record.before_delete', [$recordId, $entityId]);

        $this->records->delete($recordId);

        $this->audit->log(
            'delete_record',
            $entityId,
            $recordId,
            "Registro #{$recordId} excluído",
            [],
            $before,
            []
        );

        Hooks::fire('record.deleted', [$recordId, $entityId]);
    }

    // ── Helpers ───────────────────────────────────────────────────────

    private function assertRequired(array $fields, array $input): void
    {
        $errors = [];
        foreach ($fields as $f) {
            if (!$f['required']) continue;
            if ($f['field_type'] === 'formula') continue;
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
        // First pass: save all non-formula fields
        foreach ($fields as $f) {
            if ($f['field_type'] === 'formula') continue;

            $key = 'field_' . $f['id'];
            $raw = $input[$key] ?? null;

            switch ($f['field_type']) {
                case 'checkbox':
                    $raw = isset($input[$key]) ? '1' : '0';
                    break;
                case 'multiselect':
                case 'tags':
                    $raw = isset($input[$key]) ? json_encode((array) $input[$key]) : null;
                    break;
                case 'uuid':
                    if (empty($raw)) {
                        $raw = $this->generateUuid();
                    }
                    break;
                case 'image':
                    if (isset($input[$key . '_file_data']) && $input[$key . '_file_data']) {
                        $raw = $input[$key . '_file_data'];
                    }
                    if (empty($raw)) {
                        $raw = $input[$key . '_keep'] ?? null;
                    }
                    break;
                case 'file':
                    if (isset($input[$key . '_file_data']) && $input[$key . '_file_data']) {
                        $raw = $input[$key . '_file_data'];
                    }
                    if (empty($raw)) {
                        $raw = $input[$key . '_keep'] ?? null;
                    }
                    break;
                case 'daterange':
                    $start = trim($input[$key . '_start'] ?? '');
                    $end   = trim($input[$key . '_end']   ?? '');
                    $raw   = ($start || $end) ? json_encode(['start' => $start, 'end' => $end]) : null;
                    break;
                case 'duration':
                    if (is_string($raw) && str_contains($raw, ':')) {
                        $parts = array_map('intval', explode(':', $raw));
                        $raw   = match(count($parts)) {
                            3 => ($parts[0] * 3600) + ($parts[1] * 60) + $parts[2],
                            2 => ($parts[0] * 60) + $parts[1],
                            default => (int)$raw,
                        };
                    }
                    break;
                case 'json':
                    if (!empty($raw) && json_decode($raw) === null) {
                        $raw = null;
                    }
                    break;
            }

            $this->records->saveValue($recordId, $f, $raw);
        }

        // Second pass: resolve formula fields
        $this->resolveFormulas($recordId, $fields);
    }

    private function resolveFormulas(int $recordId, array $fields): void
    {
        $formulaFields = array_filter($fields, fn($f) => $f['field_type'] === 'formula');
        if (empty($formulaFields)) return;

        $storedValues = $this->records->loadValues($recordId);
        $fieldById    = array_column($fields, null, 'id');
        $slugValueMap = [];
        foreach ($storedValues as $fieldId => $val) {
            if (isset($fieldById[$fieldId])) {
                $slugValueMap[$fieldById[$fieldId]['slug']] = $val;
            }
        }

        foreach ($formulaFields as $f) {
            $meta       = !empty($f['options_json']) ? (json_decode($f['options_json'], true) ?? []) : [];
            $expression = trim($meta['expression'] ?? '');
            $outputType = $meta['output'] ?? 'number';

            if ($expression === '') continue;

            $result = evaluateFormula($expression, $slugValueMap, $outputType);
            $this->records->saveValue($recordId, $f, $result);
        }
    }

    private function generateUuid(): string
    {
        $data    = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
