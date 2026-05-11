<?php

declare(strict_types=1);

namespace FlexCore\App\Repositories;

/**
 * AutomationRepository — lê regras e grava logs de execução.
 *
 * Usado pelo AutomationEngine para:
 *   - activeForEntityAndEvent() → buscar regras ativas para um evento
 *   - logRun()                  → gravar resultado de cada execução
 */
class AutomationRepository
{
    /**
     * Retorna todas as automações ativas para uma entidade + evento.
     *
     * @param  int    $entityId  ID da entidade que disparou o evento
     * @param  string $event     'on_create' | 'on_update' | 'on_delete' | 'on_field_change'
     * @return array<int, array>
     */
    public function activeForEntityAndEvent(int $entityId, string $event): array
    {
        return \DB::q(
            'SELECT * FROM automations
              WHERE active = 1
                AND trigger_entity_id = ?
                AND trigger_event     = ?
              ORDER BY id ASC',
            [$entityId, $event]
        );
    }

    /**
     * Grava o resultado de uma execução na tabela automation_logs.
     *
     * @param int    $ruleId    ID da automação
     * @param int    $recordId  ID do registro que disparou
     * @param string $status    'success' | 'error'
     * @param string $message   Mensagem de erro (vazia em caso de sucesso)
     */
    public function logRun(
        int    $ruleId,
        int    $recordId,
        string $status,
        string $message = ''
    ): void {
        \DB::exec(
            'INSERT INTO automation_logs
                (automation_id, record_id, status, message, created_at)
             VALUES (?, ?, ?, ?, NOW())',
            [$ruleId, $recordId, $status, $message]
        );

        // Atualiza contador e last_run_at na regra
        \DB::run(
            'UPDATE automations
                SET run_count   = run_count + 1,
                    last_run_at = NOW()
              WHERE id = ?',
            [$ruleId]
        );
    }
}
