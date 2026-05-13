<?php
/**
 * FlexCore Test Runner — zero dependencies, PHP puro
 *
 * Uso:
 *   php tests/run.php                  → todos os testes
 *   php tests/run.php Unit             → só a pasta Unit/
 *   php tests/run.php Unit/HelpersTest → arquivo específico
 *   php tests/run.php --stop-on-fail   → para no primeiro erro
 */

define('FC_TEST_ROOT', __DIR__);
define('FC_BASE',      dirname(__DIR__));

require_once __DIR__ . '/Support/TestCase.php';
require_once __DIR__ . '/Support/Assert.php';
require_once __DIR__ . '/Support/MockDB.php';

// ── Opções de CLI ────────────────────────────────────────────────────
$args       = array_slice($argv, 1);
$stopOnFail = in_array('--stop-on-fail', $args);
$filter     = array_values(array_filter($args, fn($a) => $a[0] !== '-'))[0] ?? null;

// ── Descobre arquivos de teste ───────────────────────────────────────
function discoverTests(string $dir, ?string $filter): array
{
    $files = [];
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($it as $file) {
        if (!$file->isFile() || !str_ends_with($file->getFilename(), 'Test.php')) continue;
        $rel = str_replace($dir . DIRECTORY_SEPARATOR, '', $file->getPathname());
        if ($filter && !str_contains(str_replace('\\', '/', $rel), $filter)) continue;
        $files[] = $file->getPathname();
    }
    sort($files);
    return $files;
}

$testFiles = discoverTests(FC_TEST_ROOT, $filter);

if (empty($testFiles)) {
    echo "Nenhum arquivo de teste encontrado" . ($filter ? " para '$filter'" : '') . ".\n";
    exit(1);
}

// ── Execução ─────────────────────────────────────────────────────────
$stats  = ['tests' => 0, 'passed' => 0, 'failed' => 0, 'skipped' => 0];
$errors = [];
$start  = microtime(true);

echo "\n\033[1mFlexCore Test Runner\033[0m\n";
echo str_repeat('─', 50) . "\n\n";

foreach ($testFiles as $file) {
    require_once $file;

    // Descobre classes de teste no arquivo
    $before   = get_declared_classes();
    $classes  = array_filter(get_declared_classes(), function ($c) use ($before, $file) {
        if (!in_array($c, $before) && is_subclass_of($c, TestCase::class)) return true;
        // fallback: verifica se classe veio desse arquivo
        try {
            $ref = new ReflectionClass($c);
            return $ref->getFileName() === $file && is_subclass_of($c, TestCase::class);
        } catch (\Throwable $e) { return false; }
    });

    foreach ($classes as $class) {
        $instance = new $class();
        $methods  = array_filter(
            get_class_methods($instance),
            fn($m) => str_starts_with($m, 'test')
        );

        $shortClass = (new ReflectionClass($instance))->getShortName();
        echo "\033[33m{$shortClass}\033[0m\n";

        foreach ($methods as $method) {
            $stats['tests']++;
            $label = preg_replace('/([A-Z])/', ' $1', lcfirst(substr($method, 4)));
            $label = strtolower($label);

            // setUp antes de cada teste
            if (method_exists($instance, 'setUp')) $instance->setUp();

            try {
                $instance->$method();
                $stats['passed']++;
                echo "  \033[32m✓\033[0m {$label}\n";
            } catch (SkipException $e) {
                $stats['skipped']++;
                echo "  \033[33m→\033[0m {$label} \033[33m(pulado: {$e->getMessage()})\033[0m\n";
            } catch (AssertionError $e) {
                $stats['failed']++;
                $errors[] = [
                    'class'   => $shortClass,
                    'method'  => $method,
                    'label'   => $label,
                    'message' => $e->getMessage(),
                    'file'    => $e->getFile(),
                    'line'    => $e->getLine(),
                ];
                echo "  \033[31m✗\033[0m {$label}\n";
                if ($stopOnFail) break 3;
            } catch (\Throwable $e) {
                $stats['failed']++;
                $errors[] = [
                    'class'   => $shortClass,
                    'method'  => $method,
                    'label'   => $label,
                    'message' => get_class($e) . ': ' . $e->getMessage(),
                    'file'    => $e->getFile(),
                    'line'    => $e->getLine(),
                ];
                echo "  \033[31m✗\033[0m {$label} \033[31m[EXCEPTION]\033[0m\n";
                if ($stopOnFail) break 3;
            } finally {
                if (method_exists($instance, 'tearDown')) $instance->tearDown();
            }
        }
        echo "\n";
    }
}

// ── Relatório de erros ───────────────────────────────────────────────
if (!empty($errors)) {
    echo str_repeat('─', 50) . "\n";
    echo "\033[31mFALHAS:\033[0m\n\n";
    foreach ($errors as $i => $e) {
        echo "\033[31m" . ($i + 1) . ") {$e['class']}::{$e['method']}\033[0m\n";
        echo "   {$e['message']}\n";
        echo "   \033[2m{$e['file']}:{$e['line']}\033[0m\n\n";
    }
}

// ── Sumário ──────────────────────────────────────────────────────────
$elapsed = round(microtime(true) - $start, 3);
echo str_repeat('─', 50) . "\n";

$color  = $stats['failed'] > 0 ? '31' : '32';
$symbol = $stats['failed'] > 0 ? '✗' : '✓';

echo "\033[{$color}m{$symbol} {$stats['tests']} testes";
echo " | {$stats['passed']} passou";
if ($stats['failed'])  echo " | \033[31m{$stats['failed']} falhou\033[{$color}m";
if ($stats['skipped']) echo " | {$stats['skipped']} pulado";
echo " | {$elapsed}s\033[0m\n\n";

exit($stats['failed'] > 0 ? 1 : 0);
