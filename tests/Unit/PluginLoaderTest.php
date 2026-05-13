<?php
require_once FC_BASE . '/modules/Plugins/PluginInterface.php';
require_once FC_BASE . '/modules/Plugins/PluginManifest.php';
require_once FC_BASE . '/modules/Plugins/PluginLoader.php';

use FlexCore\Modules\Plugins\PluginLoader;
use FlexCore\Modules\Plugins\PluginManifest;

class PluginLoaderTest extends TestCase
{
    private string $tmpDir;

    public function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/fc_test_plugins_' . uniqid();
        mkdir($this->tmpDir, 0777, true);
    }

    public function tearDown(): void
    {
        if (!is_dir($this->tmpDir)) return;
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->tmpDir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($it as $f) { $f->isDir() ? rmdir($f) : unlink($f); }
        rmdir($this->tmpDir);
    }

    private function criaPlugin(string $id, string $version = '1.0.0', string $requires = '1.0.0'): void
    {
        $dir = $this->tmpDir . '/' . $id;
        mkdir($dir, 0777, true);

        $manifest = [
            'id' => $id, 'name' => "Plugin {$id}", 'version' => $version,
            'description' => 'Teste', 'author' => 'Tester',
            'namespace' => 'FcTest' . ucfirst(str_replace('-', '', $id)),
            'requires' => $requires, 'hooks' => [], 'settings' => [],
            'url' => '', 'class' => 'FcTest' . ucfirst(str_replace('-', '', $id)) . '\\Plugin',
        ];
        file_put_contents($dir . '/plugin.json', json_encode($manifest));

        $ns = 'FcTest' . ucfirst(str_replace('-', '', $id));
        file_put_contents($dir . '/Plugin.php', <<<PHP
<?php
namespace {$ns};
use FlexCore\Modules\Plugins\PluginInterface;
use FlexCore\Modules\Plugins\PluginManifest;
class Plugin implements PluginInterface {
    public function boot(): void {}
    public function manifest(): PluginManifest {
        return PluginManifest::fromJson(__DIR__ . '/plugin.json');
    }
    public function uninstall(): void {}
}
PHP);
    }

    public function testNenhumPluginEmPastaVazia(): void
    {
        $loader = new PluginLoader($this->tmpDir, '1.0.0');
        $loader->loadAll();
        assertCount(0, $loader->loaded());
    }

    public function testPastaInexistente(): void
    {
        $loader = new PluginLoader('/pasta/inexistente_' . uniqid(), '1.0.0');
        $loader->loadAll();
        assertCount(0, $loader->loaded());
    }

    public function testCarregaPluginValido(): void
    {
        $this->criaPlugin('plugin-a');
        $loader = new PluginLoader($this->tmpDir, '1.0.0');
        $loader->loadAll();
        assertCount(1, $loader->loaded());
    }

    public function testIgnoraPluginSemManifest(): void
    {
        mkdir($this->tmpDir . '/sem-manifest');
        file_put_contents($this->tmpDir . '/sem-manifest/Plugin.php', '<?php');
        $loader = new PluginLoader($this->tmpDir, '1.0.0');
        $loader->loadAll();
        assertCount(0, $loader->loaded());
    }

    public function testIgnoraPluginSemPluginPhp(): void
    {
        $dir = $this->tmpDir . '/so-json';
        mkdir($dir);
        file_put_contents($dir . '/plugin.json', json_encode(['id' => 'so-json']));
        $loader = new PluginLoader($this->tmpDir, '1.0.0');
        $loader->loadAll();
        assertCount(0, $loader->loaded());
    }

    public function testFiltraPluginInativo(): void
    {
        $this->criaPlugin('plugin-ativo');
        $this->criaPlugin('plugin-inativo');
        $loader = new PluginLoader($this->tmpDir, '1.0.0');
        $loader->loadAll(['plugin-ativo']);
        assertCount(1, $loader->loaded());
    }

    public function testIgnoraPluginIncompativel(): void
    {
        $this->criaPlugin('muito-novo', '1.0.0', '99.0.0');
        $loader = new PluginLoader($this->tmpDir, '1.0.0');
        $loader->loadAll();
        assertCount(0, $loader->loaded(), 'Plugin que exige FlexCore 99.x não deve carregar em 1.0.0');
    }

    public function testCarregaMultiplosPlugins(): void
    {
        $this->criaPlugin('plugin-x');
        $this->criaPlugin('plugin-y');
        $this->criaPlugin('plugin-z');
        $loader = new PluginLoader($this->tmpDir, '1.0.0');
        $loader->loadAll();
        assertCount(3, $loader->loaded());
    }
}
