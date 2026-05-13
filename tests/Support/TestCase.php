<?php
/**
 * Classe base para todos os testes do FlexCore.
 * Fornece setUp/tearDown e acesso ao MockDB.
 */
abstract class TestCase
{
    /** Roda antes de cada método test*() */
    public function setUp(): void {}

    /** Roda depois de cada método test*() */
    public function tearDown(): void {}

    /** Pula o teste com uma mensagem */
    protected function skip(string $reason = ''): void
    {
        throw new SkipException($reason);
    }
}

class SkipException extends \RuntimeException {}
