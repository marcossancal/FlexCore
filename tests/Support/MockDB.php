<?php
/**
 * MockDB — substitui DB::* nos testes unitários.
 * Armazena chamadas e retorna dados pré-configurados.
 *
 * Uso:
 *   MockDB::reset();
 *   MockDB::willReturn('one', ['id' => 1, 'name' => 'Teste']);
 *   MockDB::willReturn('q',   [['id' => 1], ['id' => 2]]);
 *
 *   // No código testado, DB::one() retorna o valor configurado
 *   $row = DB::one('SELECT ...', []);
 */
class MockDB
{
    private static array $returns = [];
    private static array $calls   = [];

    public static function reset(): void
    {
        self::$returns = [];
        self::$calls   = [];
    }

    /** Configura o que o método vai retornar na próxima chamada */
    public static function willReturn(string $method, mixed $value): void
    {
        self::$returns[$method][] = $value;
    }

    /** Retorna todas as chamadas registradas */
    public static function getCalls(string $method): array
    {
        return self::$calls[$method] ?? [];
    }

    /** Conta quantas vezes um método foi chamado */
    public static function countCalls(string $method): int
    {
        return count(self::$calls[$method] ?? []);
    }

    // ── Interceptores dos métodos estáticos de DB ──────────────────

    public static function one(string $sql, array $params = []): ?array
    {
        self::$calls['one'][] = compact('sql', 'params');
        return array_shift(self::$returns['one']) ?? null;
    }

    public static function q(string $sql, array $params = []): array
    {
        self::$calls['q'][] = compact('sql', 'params');
        return array_shift(self::$returns['q']) ?? [];
    }

    public static function exec(string $sql, array $params = []): int
    {
        self::$calls['exec'][] = compact('sql', 'params');
        return array_shift(self::$returns['exec']) ?? 1;
    }

    public static function run(string $sql, array $params = []): void
    {
        self::$calls['run'][] = compact('sql', 'params');
    }

    public static function lastId(): int
    {
        return array_shift(self::$returns['lastId']) ?? 1;
    }
}
