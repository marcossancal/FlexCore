<?php
require_once FC_BASE . '/modules/Plugins/PluginManifest.php';

use FlexCore\Modules\Plugins\PluginManifest;

class PluginManifestTest extends TestCase
{
    private string $tmpFile;

    public function setUp(): void
    {
        $this->tmpFile = sys_get_temp_dir() . '/fc_manifest_' . uniqid() . '.json';
    }

    public function tearDown(): void
    {
        if (file_exists($this->tmpFile)) unlink($this->tmpFile);
    }

    private function escreve(array $data): void
    {
        file_put_contents($this->tmpFile, json_encode($data));
    }

    public function testCarregaManifestCompleto(): void
    {
        $this->escreve([
            'id'          => 'meu-plugin',
            'name'        => 'Meu Plugin',
            'version'     => '2.1.0',
            'description' => 'Descrição',
            'author'      => 'Marcos',
            'url'         => 'https://exemplo.com',
            'requires'    => '1.0.0',
            'hooks'       => ['record.created'],
            'settings'    => [['key' => 'token', 'label' => 'Token']],
        ]);

        $m = PluginManifest::fromJson($this->tmpFile);
        assertEq('meu-plugin',       $m->id);
        assertEq('Meu Plugin',       $m->name);
        assertEq('2.1.0',            $m->version);
        assertEq('Marcos',           $m->author);
        assertEq('1.0.0',            $m->requires);
        assertCount(1, $m->hooks);
        assertCount(1, $m->settings);
    }

    public function testValoresDefaultQuandoAusentes(): void
    {
        $this->escreve(['id' => 'minimal']);
        $m = PluginManifest::fromJson($this->tmpFile);
        assertEq('minimal', $m->id);
        assertEq('0.0.0',   $m->version);
        assertEq('0.1.0',   $m->requires);
        assertEq([],        $m->hooks);
        assertEq([],        $m->settings);
    }

    public function testLancaExcecaoArquivoInexistente(): void
    {
        assertThrows(RuntimeException::class, function () {
            PluginManifest::fromJson('/arquivo/que/nao/existe.json');
        });
    }

    public function testLancaExcecaoJsonInvalido(): void
    {
        file_put_contents($this->tmpFile, 'isso nao e json valido {{{');
        assertThrows(RuntimeException::class, function () {
            PluginManifest::fromJson($this->tmpFile);
        });
    }

    public function testToArrayContemTodosOsCampos(): void
    {
        $this->escreve(['id' => 'teste', 'name' => 'Teste', 'version' => '1.0.0']);
        $m    = PluginManifest::fromJson($this->tmpFile);
        $arr  = $m->toArray();

        foreach (['id', 'name', 'version', 'description', 'author', 'url', 'requires', 'hooks', 'settings'] as $key) {
            assertArrayHasKey($key, $arr, "toArray() deve conter a chave '{$key}'");
        }
    }
}
