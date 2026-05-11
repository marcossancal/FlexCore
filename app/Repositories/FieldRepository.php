<?php

declare(strict_types=1);

namespace FlexCore\App\Repositories;

/**
 * FieldRepository — lê definições de campos de uma entidade.
 *
 * Usado pelo RecordService para carregar os campos antes
 * de salvar ou validar valores.
 */
class FieldRepository extends BaseRepository
{
    protected $table = 'entity_fields';

    /**
     * Retorna todos os campos de uma entidade, ordenados por posição.
     *
     * @param  int   $entityId
     * @return array<int, array>
     */
    public function forEntity(int $entityId): array
    {
        return \DB::q(
            'SELECT ef.*, ent.name AS relation_name
               FROM entity_fields ef
               LEFT JOIN entities ent ON ent.id = ef.relation_entity_id
              WHERE ef.entity_id = ?
              ORDER BY ef.position ASC',
            [$entityId]
        );
    }
}
