<?php

namespace FlexCore\Modules\Automations;

use FlexCore\Core\Hooks\Hooks;

/**
 * AutomationEngine — execute configured automations .
 * Compatible: PHP 7.4+
 */
class AutomationEngine
{
    /** @var array */
    private $actionHandlers = [];

    /** @var object */
    private $automations;

    public function __construct($automations)
    {
        $this->automations = $automations;
    }

    public function boot(): void
    {
        $self = $this;

        Hooks::on('record.created', function ($id, $entityId, $input) use ($self) {
            $self->dispatch('on_create', $entityId, $id, $input);
        });

        Hooks::on('record.updated', function ($id, $entityId, $input) use ($self) {
            $self->dispatch('on_update', $entityId, $id, $input);
        });

        Hooks::on('record.deleted', function ($id, $entityId) use ($self) {
            $self->dispatch('on_delete', $entityId, $id, []);
        });
    }

    public function registerAction(string $type, $handler): void
    {
        $this->actionHandlers[$type] = $handler;
    }

    public function dispatch(string $event, int $entityId, int $recordId, array $input): void
    {
        $rules = $this->automations->activeForEntityAndEvent($entityId, $event);

        foreach ($rules as $rule) {
            try {
                if (!$this->conditionsMet($rule, $input)) continue;
                $this->runAction($rule, $recordId, $entityId, $input);
                $this->automations->logRun($rule['id'], $recordId, 'success');
            } catch (\Throwable $e) {
                $this->automations->logRun($rule['id'], $recordId, 'error', $e->getMessage());
                error_log("AutomationEngine: rule [{$rule['id']}] failed: {$e->getMessage()}");
            }
        }
    }

    private function conditionsMet(array $rule, array $input): bool
    {
        $conditions = json_decode($rule['trigger_conditions'] ?? '[]', true);

        foreach ($conditions as $cond) {
            $actual = $input['field_' . ($cond['field_id'] ?? '')] ?? null;
            $op     = $cond['op'] ?? 'eq';
            $value  = $cond['value'] ?? '';

            if ($op === 'eq')        $passes = $actual == $value;
            elseif ($op === 'neq')   $passes = $actual != $value;
            elseif ($op === 'gt')    $passes = (float) $actual > (float) $value;
            elseif ($op === 'lt')    $passes = (float) $actual < (float) $value;
            elseif ($op === 'contains')  $passes = strpos((string) $actual, (string) $value) !== false;
            elseif ($op === 'not_empty') $passes = !empty($actual);
            elseif ($op === 'empty')     $passes = empty($actual);
            else $passes = true;

            if (!$passes) return false;
        }

        return true;
    }

    private function runAction(array $rule, int $recordId, int $entityId, array $input): void
    {
        $type    = $rule['action_type'];
        $config  = json_decode($rule['action_config'] ?? '{}', true);
        $handler = $this->actionHandlers[$type] ?? null;

        if ($handler === null) {
            throw new \RuntimeException("Sem handler para action type [{$type}].");
        }

        $handler->execute($config, $recordId, $entityId, $input);
    }
}