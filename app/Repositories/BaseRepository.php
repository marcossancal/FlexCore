<?php

namespace FlexCore\App\Repositories;

/**
 * BaseRepository — CRUD base usando DB estático.
 * Compatible: PHP 7.4+
 */
abstract class BaseRepository implements RepositoryInterface
{
    /** @var string — nome da tabela, definido na subclasse */
    protected $table = '';

    /** @var string */
    protected $primaryKey = 'id';

    public function all(array $filters = [], string $orderBy = 'id', string $dir = 'ASC'): array
    {
        [$where, $params] = $this->buildWhere($filters);
        $dir = strtoupper($dir) === 'DESC' ? 'DESC' : 'ASC';
        $col = preg_replace('/[^a-z0-9_.]/i', '', $orderBy);
        return \DB::q("SELECT * FROM {$this->table}{$where} ORDER BY {$col} {$dir}", $params);
    }

    public function find(int $id): ?array
    {
        return \DB::one(
            "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?",
            [$id]
        );
    }

    public function findBy(array $conditions): ?array
    {
        [$where, $params] = $this->buildWhere($conditions);
        return \DB::one("SELECT * FROM {$this->table}{$where} LIMIT 1", $params);
    }

    public function create(array $data): int
    {
        $data = $this->withTimestamps($data, true);
        $cols = implode(', ', array_keys($data));
        $phs  = implode(', ', array_fill(0, count($data), '?'));
        return \DB::exec(
            "INSERT INTO {$this->table} ({$cols}) VALUES ({$phs})",
            array_values($data)
        );
    }

    public function update(int $id, array $data): int
    {
        $data = $this->withTimestamps($data, false);
        $set  = implode(', ', array_map(function ($k) { return "{$k} = ?"; }, array_keys($data)));
        return \DB::run(
            "UPDATE {$this->table} SET {$set} WHERE {$this->primaryKey} = ?",
            array_merge(array_values($data), [$id])
        );
    }

    public function delete(int $id): int
    {
        return \DB::run(
            "DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?",
            [$id]
        );
    }

    public function count(array $filters = []): int
    {
        [$where, $params] = $this->buildWhere($filters);
        $row = \DB::one("SELECT COUNT(*) AS c FROM {$this->table}{$where}", $params);
        return (int) ($row['c'] ?? 0);
    }

    public function paginate(int $page, int $perPage, array $filters = []): array
    {
        $total  = $this->count($filters);
        $offset = ($page - 1) * $perPage;
        [$where, $params] = $this->buildWhere($filters);

        $items = \DB::q(
            "SELECT * FROM {$this->table}{$where} ORDER BY {$this->primaryKey} DESC LIMIT ? OFFSET ?",
            array_merge($params, [$perPage, $offset])
        );

        return [
            'data'         => $items,
            'total'        => $total,
            'per_page'     => $perPage,
            'current_page' => $page,
            'last_page'    => max(1, (int) ceil($total / $perPage)),
        ];
    }

    // ── Helpers ───────────────────────────────────────────────────────

    protected function buildWhere(array $conditions): array
    {
        if (empty($conditions)) return ['', []];

        $clauses = [];
        $params  = [];

        foreach ($conditions as $col => $val) {
            $safeCol = preg_replace('/[^a-zA-Z0-9_.]/', '', (string) $col);
            if ($safeCol === '' || $safeCol !== $col) {
                throw new \InvalidArgumentException(
                    "buildWhere: nome de coluna inválido: [{$col}]"
                );
            }
            if (is_null($val)) {
                $clauses[] = "{$safeCol} IS NULL";
            } else {
                $clauses[] = "{$safeCol} = ?";
                $params[]  = $val;
            }
        }

        return [' WHERE ' . implode(' AND ', $clauses), $params];
    }

    private function withTimestamps(array $data, bool $create): array
    {
        $now = date('Y-m-d H:i:s');
        if ($create && !isset($data['created_at'])) {
            $data['created_at'] = $now;
        }
        $data['updated_at'] = $now;
        return $data;
    }
}