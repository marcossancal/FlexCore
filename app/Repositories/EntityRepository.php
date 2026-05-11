<?php

namespace FlexCore\App\Repositories;

/**
 * EntityRepository — persiste e busca entidades.
 * Compatible: PHP 7.4+
 */
class EntityRepository extends BaseRepository implements EntityRepositoryInterface
{
    // sem tipo declarado — evita conflito com a propriedade do BaseRepository
    protected $table = 'entities';

    public function allActive(): array
    {
        return \DB::q(
            'SELECT * FROM entities WHERE active = 1 ORDER BY position ASC, name ASC'
        );
    }

    public function findBySlug(string $slug): ?array
    {
        return $this->findBy(['slug' => $slug, 'active' => 1]);
    }

    public function withFieldCount(): array
    {
        return \DB::q(
            'SELECT e.*,
                    (SELECT COUNT(*) FROM entity_fields WHERE entity_id = e.id) AS field_count,
                    (SELECT COUNT(*) FROM entity_records WHERE entity_id = e.id) AS record_count
               FROM entities e
              ORDER BY e.position ASC, e.name ASC'
        );
    }
}