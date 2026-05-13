<?php
/**
 * Feature: API REST de registros
 *
 * Estes testes verificam o comportamento do ApiRecordController
 * simulando requisições HTTP reais via cURL contra a instância local.
 *
 * Configuração: defina FC_TEST_URL e FC_TEST_KEY no .env ou como
 * variáveis de ambiente antes de rodar:
 *
 *   FC_TEST_URL=http://localhost/FlexCore FC_TEST_KEY=fc_xxx php tests/run.php Feature
 */
class ApiRecordTest extends TestCase
{
    private string $baseUrl;
    private string $apiKey;
    private bool   $hasServer;

    public function setUp(): void
    {
        $this->baseUrl   = rtrim($_ENV['FC_TEST_URL'] ?? getenv('FC_TEST_URL') ?: '', '/');
        $this->apiKey    = $_ENV['FC_TEST_KEY']  ?? getenv('FC_TEST_KEY')  ?? '';
        $this->hasServer = !empty($this->baseUrl) && !empty($this->apiKey);
    }

    private function request(string $method, string $path, array $body = []): array
    {
        if (!$this->hasServer) {
            $this->skip('FC_TEST_URL e FC_TEST_KEY não configurados');
        }

        $url = $this->baseUrl . $path;
        $ch  = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $this->apiKey,
                'Content-Type: application/json',
                'Accept: application/json',
            ],
            CURLOPT_TIMEOUT        => 10,
        ]);
        if (!empty($body)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }

        $raw    = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $decoded = json_decode($raw, true);
        return ['status' => $status, 'body' => $decoded, 'raw' => $raw];
    }

    public function testListaEntidades(): void
    {
        $res = $this->request('GET', '/api/v1/entities');
        assertEq(200, $res['status']);
        assertArrayHasKey('data', $res['body']);
    }

    public function testAutenticacaoSemChave(): void
    {
        if (!$this->hasServer) $this->skip('Servidor não configurado');

        $ch = curl_init($this->baseUrl . '/api/v1/entities');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ['Accept: application/json'],
            CURLOPT_TIMEOUT        => 10,
        ]);
        $raw    = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        assertEq(401, $status, 'Sem API Key deve retornar 401');
    }

    public function testListaRegistrosDaEntidade(): void
    {
        $res = $this->request('GET', '/api/v1/e/profissionais?per_page=5');
        assertEq(200, $res['status']);
        assertArrayHasKey('data', $res['body']);
        assertArrayHasKey('meta', $res['body']);
        assertTrue($res['body']['meta']['per_page'] <= 5);
    }

    public function testEntidadeInexistenteRetorna404(): void
    {
        $res = $this->request('GET', '/api/v1/e/entidade_que_nao_existe_xyz');
        assertEq(404, $res['status']);
    }

    public function testRespostaTemEstruturaPadrao(): void
    {
        $res = $this->request('GET', '/api/v1/e/profissionais?per_page=1');
        assertEq(200, $res['status']);
        assertArrayHasKey('data',   $res['body']);
        assertArrayHasKey('meta',   $res['body']);
        assertArrayHasKey('errors', $res['body']);
    }

    public function testMetaPaginacao(): void
    {
        $res = $this->request('GET', '/api/v1/e/profissionais?page=1&per_page=3');
        assertEq(200, $res['status']);
        $meta = $res['body']['meta'];
        assertArrayHasKey('total',    $meta);
        assertArrayHasKey('page',     $meta);
        assertArrayHasKey('per_page', $meta);
        assertArrayHasKey('pages',    $meta);
        assertEq(1, $meta['page']);
        assertEq(3, $meta['per_page']);
    }

    public function testFiltroStatusRetornaApenasAtivos(): void
    {
        $res = $this->request('GET', '/api/v1/e/profissionais?status=ativo&per_page=100');
        assertEq(200, $res['status']);
        foreach ($res['body']['data'] as $record) {
            assertEq('ativo', $record['fields']['status'] ?? 'ativo',
                'Todos os registros devem ter status=ativo');
        }
    }

    public function testCriaRegistroRetorna201(): void
    {
        $res = $this->request('POST', '/api/v1/e/solicitacoes', [
            'nome'      => 'Teste API ' . uniqid(),
            'email'     => 'teste@api.com',
            'whatsapp'  => '11999990000',
            'profissao' => 'Psicólogo(a)',
            'registro'  => 'CRP 00/00000',
            'estado'    => 'SP',
            'cidade'    => 'São Paulo',
            'declaracao'=> '1',
            'status'    => 'nova',
        ]);
        assertEq(201, $res['status'], 'Criar registro deve retornar 201');
        assertArrayHasKey('data', $res['body']);
        assertArrayHasKey('id',   $res['body']['data']);
    }

    public function testCriaRegistroSemCampoObrigatorioRetorna422(): void
    {
        $res = $this->request('POST', '/api/v1/e/solicitacoes', [
            // 'nome' propositalmente ausente
            'email' => 'teste@api.com',
        ]);
        assertEq(422, $res['status'], 'Campo obrigatório ausente deve retornar 422');
    }
}
