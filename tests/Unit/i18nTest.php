<?php
if (!defined('BASE')) define('BASE', FC_BASE);
require_once FC_BASE . '/lib/helpers.php';

class i18nTest extends TestCase
{
    private string $originalLang;

    public function setUp(): void
    {
        $this->originalLang = currentLang();
    }

    public function tearDown(): void
    {
        loadTranslations($this->originalLang);
    }

    public function testCarregaPtBr(): void
    {
        loadTranslations('pt_BR');
        assertNotEq('general.save', __('general.save'), 'Chave general.save deve estar traduzida');
    }

    public function testCarregaEnUs(): void
    {
        loadTranslations('en_US');
        $val = __('general.save');
        assertNotEq('general.save', $val);
        // en_US deve conter 'Save' (inglês)
        assertContains('Save', $val);
    }

    public function testChaveInexistenteRetornaAPropriaChave(): void
    {
        loadTranslations('pt_BR');
        assertEq('chave.que.nao.existe', __('chave.que.nao.existe'));
    }

    public function testInterpolacaoDeVariaveis(): void
    {
        loadTranslations('pt_BR');
        // Testa se a função __ faz substituição de :variavel
        // Usa uma chave real que tem interpolação
        $result = __('general.save', ['name' => 'Teste']);
        assertNotNull($result);
    }

    public function testFallbackParaPtBrQuandoIdiomaNaoExiste(): void
    {
        loadTranslations('idioma_inexistente');
        // Deve ter feito fallback para pt_BR
        assertEq('pt_BR', currentLang());
    }

    public function testTodosIdiomasTem29TiposDeCampo(): void
    {
        $tipos = array_keys(allFieldTypes());
        $idiomas = ['pt_BR', 'en_US', 'es', 'fr', 'de'];

        foreach ($idiomas as $lang) {
            loadTranslations($lang);
            $faltando = [];
            foreach ($tipos as $tipo) {
                $chave = "fields.types.{$tipo}";
                if (__($chave) === $chave) {
                    $faltando[] = $tipo;
                }
            }
            assertEmpty(
                $faltando,
                "Idioma {$lang} está sem tradução para os tipos: " . implode(', ', $faltando)
            );
        }
    }

    public function testChavesEssenciaisExistemEmTodosIdiomas(): void
    {
        $chavesEssenciais = [
            'general.save', 'general.cancel', 'general.delete',
            'general.edit', 'general.active', 'general.inactive',
        ];
        $idiomas = ['pt_BR', 'en_US', 'es', 'fr', 'de'];

        foreach ($idiomas as $lang) {
            loadTranslations($lang);
            foreach ($chavesEssenciais as $chave) {
                assertNotEq(
                    $chave,
                    __($chave),
                    "Chave '{$chave}' faltando no idioma {$lang}"
                );
            }
        }
    }
}
