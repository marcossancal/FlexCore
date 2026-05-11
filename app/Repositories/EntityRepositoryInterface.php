<?php

declare(strict_types=1);

namespace FlexCore\App\Repositories;

/**
 * EntityRepositoryInterface — ISP: only the entity-specific methods
 * that consumers actually need, beyond the base CRUD contract.
 */
interface EntityRepositoryInterface extends RepositoryInterface
{
    public function allActive(): array;

    public function findBySlug(string $slug): ?array;

    public function withFieldCount(): array;
}
