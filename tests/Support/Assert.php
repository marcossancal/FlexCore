<?php
/**
 * Funções de asserção — lançam AssertionError em caso de falha.
 * Uso: assertEq($a, $b) | assertTrue($x) | assertContains('foo', $str) etc.
 */

function assertEq(mixed $expected, mixed $actual, string $msg = ''): void
{
    if ($expected !== $actual) {
        $e = json_encode($expected, JSON_UNESCAPED_UNICODE);
        $a = json_encode($actual,   JSON_UNESCAPED_UNICODE);
        throw new AssertionError($msg ?: "Esperado {$e}, obtido {$a}");
    }
}

function assertNotEq(mixed $expected, mixed $actual, string $msg = ''): void
{
    if ($expected === $actual) {
        $v = json_encode($actual, JSON_UNESCAPED_UNICODE);
        throw new AssertionError($msg ?: "Esperava que os valores fossem diferentes, ambos são {$v}");
    }
}

function assertTrue(mixed $value, string $msg = ''): void
{
    if ($value !== true && !$value) {
        $v = json_encode($value, JSON_UNESCAPED_UNICODE);
        throw new AssertionError($msg ?: "Esperado true, obtido {$v}");
    }
}

function assertFalse(mixed $value, string $msg = ''): void
{
    if ($value !== false && $value) {
        $v = json_encode($value, JSON_UNESCAPED_UNICODE);
        throw new AssertionError($msg ?: "Esperado false, obtido {$v}");
    }
}

function assertNull(mixed $value, string $msg = ''): void
{
    if ($value !== null) {
        throw new AssertionError($msg ?: 'Esperado null, obtido ' . json_encode($value));
    }
}

function assertNotNull(mixed $value, string $msg = ''): void
{
    if ($value === null) {
        throw new AssertionError($msg ?: 'Esperado valor não-nulo, obtido null');
    }
}

function assertContains(string $needle, string $haystack, string $msg = ''): void
{
    if (!str_contains($haystack, $needle)) {
        throw new AssertionError($msg ?: "Esperava encontrar \"{$needle}\" na string");
    }
}

function assertNotContains(string $needle, string $haystack, string $msg = ''): void
{
    if (str_contains($haystack, $needle)) {
        throw new AssertionError($msg ?: "Não esperava encontrar \"{$needle}\" na string");
    }
}

function assertCount(int $expected, array $array, string $msg = ''): void
{
    $actual = count($array);
    if ($expected !== $actual) {
        throw new AssertionError($msg ?: "Esperado array com {$expected} itens, tem {$actual}");
    }
}

function assertEmpty(mixed $value, string $msg = ''): void
{
    if (!empty($value)) {
        throw new AssertionError($msg ?: 'Esperado valor vazio');
    }
}

function assertNotEmpty(mixed $value, string $msg = ''): void
{
    if (empty($value)) {
        throw new AssertionError($msg ?: 'Esperado valor não-vazio');
    }
}

function assertArrayHasKey(string|int $key, array $array, string $msg = ''): void
{
    if (!array_key_exists($key, $array)) {
        throw new AssertionError($msg ?: "Array não tem a chave \"{$key}\"");
    }
}

function assertThrows(string $exceptionClass, callable $fn, string $msg = ''): void
{
    try {
        $fn();
        throw new AssertionError($msg ?: "Esperava exceção {$exceptionClass}, nenhuma foi lançada");
    } catch (\Throwable $e) {
        if (!($e instanceof $exceptionClass)) {
            throw new AssertionError(
                $msg ?: "Esperava {$exceptionClass}, obteve " . get_class($e) . ": " . $e->getMessage()
            );
        }
    }
}

function assertMatchesRegex(string $pattern, string $value, string $msg = ''): void
{
    if (!preg_match($pattern, $value)) {
        throw new AssertionError($msg ?: "Valor \"{$value}\" não bate com o padrão {$pattern}");
    }
}

function assertJsonValid(string $json, string $msg = ''): void
{
    json_decode($json);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new AssertionError($msg ?: 'JSON inválido: ' . json_last_error_msg());
    }
}
