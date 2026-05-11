<?php

declare(strict_types=1);

namespace FlexCore\App\Repositories;

/**
 * RepositoryInterface — DIP + LSP.
 *
 * All repositories implement this contract. Services depend on this
 * interface, never on a concrete class — making it trivial to swap
 * MySQL for PostgreSQL, SQLite, or a test double.
 *
 * @template T of array
 */
interface RepositoryInterface
{
    /** Return all records matching optional filters. */
    public function all(array $filters = [], string $orderBy = 'id', string $dir = 'ASC'): array;

    /** Return one record by primary key, or null. */
    public function find(int $id): ?array;

    /** Return first record matching conditions, or null. */
    public function findBy(array $conditions): ?array;

    /** Insert a new record. Returns the new ID. */
    public function create(array $data): int;

    /** Update an existing record. Returns rows affected. */
    public function update(int $id, array $data): int;

    /** Delete a record by ID. Returns rows affected. */
    public function delete(int $id): int;

    /** Count records matching optional filters. */
    public function count(array $filters = []): int;

    /** Paginate results. */
    public function paginate(int $page, int $perPage, array $filters = []): array;
}
