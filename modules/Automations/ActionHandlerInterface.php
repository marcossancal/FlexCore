<?php

declare(strict_types=1);

namespace FlexCore\Modules\Automations;

/**
 * ActionHandlerInterface — ISP + OCP.
 *
 * New action types (send_sms, create_pdf, etc.) are added by implementing
 * this and registering in AutomationEngine — zero changes to existing code.
 */
interface ActionHandlerInterface
{
    /**
     * Execute the action.
     *
     * @param array $config     The action_config JSON decoded (varies per action type)
     * @param int   $recordId   The record that triggered the automation
     * @param int   $entityId   The entity the record belongs to
     * @param array $input      The raw field input that triggered the event
     */
    public function execute(array $config, int $recordId, int $entityId, array $input): void;

    /** Human-readable label for the panel UI. */
    public function label(): string;

    /**
     * JSON Schema for the config fields shown in the builder UI.
     * The panel renders these dynamically.
     *
     * Example for 'webhook':
     * [
     *   { "key": "url",    "type": "url",    "label": "URL do Webhook", "required": true },
     *   { "key": "method", "type": "select", "label": "Método",
     *     "options": ["POST","PUT"], "default": "POST" }
     * ]
     */
    public function configSchema(): array;
}
