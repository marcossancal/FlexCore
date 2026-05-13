<?php
require_once FC_BASE . '/lib/helpers.php';

// Define BASE para o helpers não quebrar no loadTranslations
if (!defined('BASE')) define('BASE', FC_BASE);

class HelpersTest extends TestCase
{
    public function testSlugSimples(): void
    {
        assertEq('hello-world', slug('Hello World'));
    }

    public function testSlugRemoveAcentos(): void
    {
        assertEq('configuracoes', slug('Configurações'));
        assertEq('preco', slug('Preço'));
        assertEq('educacao', slug('Educação'));
    }

    public function testSlugCaracteresEspeciais(): void
    {
        assertEq('nome-do-campo', slug('Nome do Campo!'));
        assertEq('test-123', slug('Test 123'));
    }

    public function testSlugVazio(): void
    {
        assertEq('', slug(''));
    }

    public function testHEscapeHtml(): void
    {
        assertEq('&lt;script&gt;', h('<script>'));
        assertEq('&quot;aspas&quot;', h('"aspas"'));
        assertEq("it&#039;s", h("it's"));
    }

    public function testHStringNormal(): void
    {
        assertEq('Texto normal', h('Texto normal'));
    }

    public function testMoneyFormata(): void
    {
        assertContains('R$', money(1500.5));
        assertContains('1.500', money(1500.5));
        assertContains('50', money(1500.5));
    }

    public function testDateBrFormatoBrasileiro(): void
    {
        assertEq('01/01/2024', dateBr('2024-01-01'));
        assertEq('31/12/2023', dateBr('2023-12-31'));
    }

    public function testDateBrVazio(): void
    {
        assertEq('—', dateBr(''));
    }

    public function testAllFieldTypesRetornaTodos(): void
    {
        $types = allFieldTypes();
        assertTrue(count($types) >= 29, 'Deve ter ao menos 29 tipos de campo');
        assertArrayHasKey('text',      $types);
        assertArrayHasKey('relation',  $types);
        assertArrayHasKey('image',     $types);
        assertArrayHasKey('uuid',      $types);
        assertArrayHasKey('currency',  $types);
    }

    public function testAllFieldTypesTemIconeEStorage(): void
    {
        foreach (allFieldTypes() as $type => $meta) {
            assertArrayHasKey('icon',    $meta, "Tipo '{$type}' sem icon");
            assertArrayHasKey('storage', $meta, "Tipo '{$type}' sem storage");
            assertTrue(
                in_array($meta['storage'], ['val_text', 'val_num', 'val_date']),
                "Storage inválido para '{$type}': {$meta['storage']}"
            );
        }
    }

    public function testIsNumericType(): void
    {
        assertTrue(isNumericType('number'));
        assertTrue(isNumericType('currency'));
        assertTrue(isNumericType('rating'));
        assertFalse(isNumericType('text'));
        assertFalse(isNumericType('relation'));
    }

    public function testIsDateType(): void
    {
        assertTrue(isDateType('date'));
        assertTrue(isDateType('datetime'));
        assertFalse(isDateType('time'));   // time usa val_text
        assertFalse(isDateType('number'));
    }

    public function testFieldTypeIcon(): void
    {
        assertNotEq('❓', fieldTypeIcon('text'));
        assertEq('❓', fieldTypeIcon('tipo_inexistente'));
    }

    public function testUrlBuilda(): void
    {
        if (!defined('BASE_PATH')) define('BASE_PATH', '');
        assertEq('/plugins', url('/plugins'));
        assertEq('/entidades', url('entidades'));
    }
}
