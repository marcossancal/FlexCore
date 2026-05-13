<?php
require_once FC_BASE . '/core/Router/Route.php';
require_once FC_BASE . '/core/Router/Router.php';

use FlexCore\Core\Router\Router;
use FlexCore\Core\Router\Route;

class RouterTest extends TestCase
{
    private Router $router;

    public function setUp(): void
    {
        $this->router = new Router();
    }

    private function dispatch(string $method, string $uri): mixed
    {
        $_SERVER['REQUEST_METHOD'] = $method;
        $_SERVER['REQUEST_URI']    = $uri;
        return $this->router->dispatch($method, $uri);
    }

    public function testRotaGetSimples(): void
    {
        $chamado = false;
        $this->router->get('/teste', function () use (&$chamado) {
            $chamado = true;
        });
        $this->dispatch('GET', '/teste');
        assertTrue($chamado, 'Handler da rota GET deve ser chamado');
    }

    public function testRotaComParametro(): void
    {
        $idRecebido = null;
        $this->router->get('/entidades/{id}', function (string $id) use (&$idRecebido) {
            $idRecebido = $id;
        });
        $this->dispatch('GET', '/entidades/42');
        assertEq('42', $idRecebido);
    }

    public function testRotaComDoisParametros(): void
    {
        $params = [];
        $this->router->get('/e/{slug}/{id}', function (string $slug, string $id) use (&$params) {
            $params = compact('slug', 'id');
        });
        $this->dispatch('GET', '/e/clientes/99');
        assertEq('clientes', $params['slug']);
        assertEq('99',       $params['id']);
    }

    public function testMetodoPostDistintoDaGet(): void
    {
        $getHit  = false;
        $postHit = false;
        $this->router->get('/rota',  function () use (&$getHit)  { $getHit  = true; });
        $this->router->post('/rota', function () use (&$postHit) { $postHit = true; });

        $this->dispatch('POST', '/rota');
        assertFalse($getHit,  'GET não deve ser chamado num POST');
        assertTrue($postHit, 'POST deve ser chamado');
    }

    public function testRotaInexistenteNaoDisparaHandler(): void
    {
        $chamado = false;
        $this->router->get('/existe', function () use (&$chamado) { $chamado = true; });
        // Não registra /nao-existe — dispatch não deve chamar o handler
        ob_start();
        try {
            $this->dispatch('GET', '/nao-existe');
        } catch (\Throwable $e) {
            // Router pode lançar 404 — também é válido
        }
        ob_end_clean();
        assertFalse($chamado, 'Handler não deve ser chamado para rota inexistente');
    }

    public function testRotasApiComPrefixo(): void
    {
        $hit = false;
        $this->router->get('/api/v1/e/{slug}', function (string $slug) use (&$hit) {
            $hit = true;
        });
        $this->dispatch('GET', '/api/v1/e/profissionais');
        assertTrue($hit, 'Rota da API deve ser encontrada');
    }
}
