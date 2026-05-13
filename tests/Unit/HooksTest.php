<?php
require_once FC_BASE . '/core/Hooks/Hooks.php';
require_once FC_BASE . '/core/Hooks/HookDispatcher.php';

use FlexCore\Core\Hooks\HookDispatcher;

class HooksTest extends TestCase
{
    private HookDispatcher $d;

    public function setUp(): void
    {
        $this->d = new HookDispatcher();
        $this->d->reset();
    }

    public function testActionDisparada(): void
    {
        $chamado = false;
        $this->d->on('record.created', function () use (&$chamado) { $chamado = true; });
        $this->d->fire('record.created');
        assertTrue($chamado);
    }

    public function testActionNaoDisparadaParaEventoDiferente(): void
    {
        $chamado = false;
        $this->d->on('record.created', function () use (&$chamado) { $chamado = true; });
        $this->d->fire('record.deleted');
        assertFalse($chamado);
    }

    public function testMultiplasActionsNaMesmaHook(): void
    {
        $contador = 0;
        $this->d->on('test.event', function () use (&$contador) { $contador++; });
        $this->d->on('test.event', function () use (&$contador) { $contador++; });
        $this->d->on('test.event', function () use (&$contador) { $contador++; });
        $this->d->fire('test.event');
        assertEq(3, $contador);
    }

    public function testFilterModificaValor(): void
    {
        $this->d->filter('val.test', fn($v) => strtoupper($v));
        assertEq('HELLO', $this->d->applyFilter('val.test', 'hello'));
    }

    public function testFilterEncadeado(): void
    {
        $this->d->filter('chain', fn($v) => $v . '_a');
        $this->d->filter('chain', fn($v) => $v . '_b');
        $this->d->filter('chain', fn($v) => $v . '_c');
        assertEq('inicio_a_b_c', $this->d->applyFilter('chain', 'inicio'));
    }

    public function testFilterSemRegistroRetornaOriginal(): void
    {
        assertEq('original', $this->d->applyFilter('sem.filtro', 'original'));
    }

    public function testActionRecebeArgumentos(): void
    {
        // Convenção do projeto: fire() recebe array indexado.
        // Os argumentos são passados posicionalmente para o listener.
        // Ex: Hooks::fire('record.created', [$recordId, $entityId, $rawInput])
        $idRecebido     = null;
        $entityRecebido = null;
        $this->d->on('record.updated', function (int $id, string $entity) use (&$idRecebido, &$entityRecebido) {
            $idRecebido     = $id;
            $entityRecebido = $entity;
        });
        $this->d->fire('record.updated', [42, 'clientes']);
        assertEq(42,         $idRecebido);
        assertEq('clientes', $entityRecebido);
    }

    public function testHasListeners(): void
    {
        assertFalse($this->d->hasListeners('evento.vazio'));
        $this->d->on('evento.com.listener', fn() => null);
        assertTrue($this->d->hasListeners('evento.com.listener'));
    }

    public function testResetLimpaListeners(): void
    {
        $this->d->on('reset.test', fn() => null);
        $this->d->reset();
        assertFalse($this->d->hasListeners('reset.test'));
    }
}