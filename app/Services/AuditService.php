<?php

declare(strict_types=1);

namespace FlexCore\App\Services;

/**
 * AuditService — grava entradas na tabela audit_log.
 *
 * Encapsula a função global audit() em um serviço injetável,
 * permitindo que o RecordService não dependa de globals.
 */
class AuditService
{
    /**
     * Grava uma entrada de auditoria.
     *
     * @param string   $action    Ex: 'create_record', 'update_record', 'delete_record'
     * @param int|null $entityId  ID da entidade relacionada
     * @param int|null $recordId  ID do registro relacionado
     * @param string   $desc      Descrição legível da ação
     */
    public function log(
        string $action,
        ?int   $entityId,
        ?int   $recordId,
        string $desc
    ): void {
        \DB::exec(
            'INSERT INTO audit_log
                (user_id, action, entity_id, record_id, description, ip, created_at)
             VALUES (?, ?, ?, ?, ?, ?, NOW())',
            [
                \Auth::id() ?: null,
                $action,
                $entityId,
                $recordId,
                $desc,
                $_SERVER['REMOTE_ADDR'] ?? null,
            ]
        );
    }
}
