<?php

// ── Translation engine ────────────────────────────────────────────────

$GLOBALS['_fc_trans'] = null;
$GLOBALS['_fc_lang']  = null;

function loadTranslations(string $lang): void
{
    $file = BASE . '/translates/' . $lang . '.json';
    if (!file_exists($file)) {
        $file = BASE . '/translates/pt_BR.json';
        $lang = 'pt_BR';
    }
    $GLOBALS['_fc_trans'] = json_decode(file_get_contents($file), true) ?? [];
    $GLOBALS['_fc_lang']  = $lang;

    // -- Hook: translations.loaded --
    // Plugins mesclam suas proprias traducoes sem editar os JSONs do core.
    // O filtro recebe o array de traducoes atual e o lang ativo, e retorna
    // o array com as chaves do plugin mescladas.
    //
    // Exemplo de uso no Plugin.php:
    //   Hooks::filter('translations.loaded', function(array $trans, string $lang): array {
    //       $file = __DIR__ . '/translates/' . $lang . '.json';
    //       if (!file_exists($file)) $file = __DIR__ . '/translates/pt_BR.json';
    //       $plugin = json_decode(file_get_contents($file), true) ?? [];
    //       return array_replace_recursive($trans, $plugin);
    //   });
    $GLOBALS['_fc_trans'] = \FlexCore\Core\Hooks\Hooks::applyFilter(
        'translations.loaded',
        $GLOBALS['_fc_trans'],
        [$lang]
    );
}

function __(string $key, array $replace = []): string
{
    if ($GLOBALS['_fc_trans'] === null) {
        loadTranslations('pt_BR');
    }
    $parts = explode('.', $key);
    $val   = $GLOBALS['_fc_trans'];
    foreach ($parts as $part) {
        if (!is_array($val) || !isset($val[$part])) {
            return $key;
        }
        $val = $val[$part];
    }
    if (!is_string($val)) return $key;
    foreach ($replace as $k => $v) {
        $val = str_replace(':' . $k, (string) $v, $val);
    }
    return $val;
}

function availableLanguages(): array
{
    $langs = [];
    $dir   = BASE . '/translates/';
    if (!is_dir($dir)) return $langs;
    foreach (glob($dir . '*.json') as $file) {
        $data = json_decode(file_get_contents($file), true);
        $meta = $data['_meta'] ?? [];
        if (!empty($meta['lang'])) {
            $langs[$meta['lang']] = $meta;
        }
    }
    return $langs;
}

function currentLang(): string
{
    return $GLOBALS['_fc_lang'] ?? 'pt_BR';
}

function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

function slug(string $s): string {
    $s = mb_strtolower(trim($s));
    $s = preg_replace('/[àáâãäå]/u','a',$s);
    $s = preg_replace('/[èéêë]/u','e',$s);
    $s = preg_replace('/[ìíîï]/u','i',$s);
    $s = preg_replace('/[òóôõö]/u','o',$s);
    $s = preg_replace('/[ùúûü]/u','u',$s);
    $s = preg_replace('/[ç]/u','c',$s);
    $s = preg_replace('/[ñ]/u','n',$s);
    $s = preg_replace('/[^a-z0-9_]+/','-',$s);
    return trim($s, '-');
}
function post(string $k, mixed $d = ''): mixed { return $_POST[$k] ?? $d; }
function get(string $k, mixed $d = ''): mixed  { return $_GET[$k]  ?? $d; }

function redirect(string $path): void {
    header('Location: ' . BASE_PATH . '/' . ltrim($path, '/'));
    exit;
}

function flash(string $type, string $msg): void { $_SESSION['flash'] = compact('type','msg'); }
function getFlash(): ?array { $f=$_SESSION['flash']??null; unset($_SESSION['flash']); return $f; }
function view(string $tpl, array $data = []): void {
    extract($data);
    include BASE.'/app/views/'.$tpl.'.php';
}
function partial(string $tpl, array $data = []): void {
    extract($data);
    include BASE.'/app/views/'.$tpl.'.php';
}

function url(string $path = ''): string {
    return BASE_PATH . '/' . ltrim($path, '/');
}

function dateBr(string $dt): string {
    if (!$dt) return '—';
    return date('d/m/Y', strtotime($dt));
}
function money(float $v): string {
    return 'R$ '.number_format($v, 2, ',', '.');
}

/**
 * All fieldtypes supported by FlexCore
 * Organized by : storage column, ícon and label.
 *
 * storage:
 *   val_text  — string, JSON or base64
 *   val_num   — DECIMAL(18,4): number/currency/percentage/rating/time/progress
 *   val_date  — DATETIME: date/datetime/hour
 */
function allFieldTypes(): array
{
    $coreTypes = [
        // ── Texto e comunicação ──────────────────────────────────────
        'text'        => ['icon' => '🔤', 'storage' => 'val_text'],
        'textarea'    => ['icon' => '📝', 'storage' => 'val_text'],
        'richtext'    => ['icon' => '✍️',  'storage' => 'val_text'],
        'email'       => ['icon' => '✉️',  'storage' => 'val_text'],
        'url'         => ['icon' => '🔗', 'storage' => 'val_text'],
        'phone'       => ['icon' => '📞', 'storage' => 'val_text'],
        'password'    => ['icon' => '🔒', 'storage' => 'val_text'],

        // ── Números e valores ────────────────────────────────────────
        'number'      => ['icon' => '🔢', 'storage' => 'val_num'],
        'currency'    => ['icon' => '💰', 'storage' => 'val_num'],
        'percent'     => ['icon' => '%',  'storage' => 'val_num'],
        'rating'      => ['icon' => '⭐', 'storage' => 'val_num'],
        'progress'    => ['icon' => '🎚', 'storage' => 'val_num'],
        'duration'    => ['icon' => '⏳', 'storage' => 'val_num'],

        // ── Data e tempo ─────────────────────────────────────────────
        'date'        => ['icon' => '📅', 'storage' => 'val_date'],
        'datetime'    => ['icon' => '🕐', 'storage' => 'val_date'],
        'time'        => ['icon' => '⏱',  'storage' => 'val_text'],
        'daterange'   => ['icon' => '📆', 'storage' => 'val_text'],

        // ── Seleção e listas ─────────────────────────────────────────
        'select'      => ['icon' => '▼',  'storage' => 'val_text'],
        'multiselect' => ['icon' => '☑️', 'storage' => 'val_text'],
        'checkbox'    => ['icon' => '✅', 'storage' => 'val_text'],
        'tags'        => ['icon' => '🏷',  'storage' => 'val_text'],
        'user'        => ['icon' => '👤', 'storage' => 'val_text'],
        'color'       => ['icon' => '🎨', 'storage' => 'val_text'],

        // ── Relacionamentos ──────────────────────────────────────────
        'relation'    => ['icon' => '🔀', 'storage' => 'val_text'],

        // ── Dados especiais ──────────────────────────────────────────
        'uuid'        => ['icon' => '🆔', 'storage' => 'val_text'],
        'json'        => ['icon' => '🔣', 'storage' => 'val_text'],
        'ip'          => ['icon' => '📡', 'storage' => 'val_text'],

        // ── Mídia e arquivos ─────────────────────────────────────────
        'image'       => ['icon' => '🖼',  'storage' => 'val_text'],  // base64 val_text (MEDIUMTEXT ~16MB)
        'file'        => ['icon' => '📎', 'storage' => 'val_text'],   // base64 val_text

        // ── Fórmula ──────────────────────────────────────────────────
        // options_json: {"expression":"...", "output":"number|text|currency|percent"}
        'formula'     => ['icon' => '∑',  'storage' => 'val_num'],    // resultado numérico padrão; text vai para val_text
    ];

    // ── Hook: field.types ─────────────────────────────────────────────
    // Plugins registram tipos de campo adicionais via este filtro.
    //
    // Exemplo de uso no Plugin.php:
    //   Hooks::filter('field.types', function(array $types): array {
    //       $types['repeater'] = ['icon' => '🔁', 'storage' => 'repeater_values'];
    //       return $types;
    //   });
    return \FlexCore\Core\Hooks\Hooks::applyFilter('field.types', $coreTypes);
}

function fieldTypeIcon(string $t): string
{
    return allFieldTypes()[$t]['icon'] ?? '❓';
}

function fieldTypeLabel(string $t): string
{
    // Tries to translate; fallback to slug type
    $label = __('fields.types.' . $t);
    return ($label === 'fields.types.' . $t) ? $t : $label;
}

/**
 * Types that uses val_num as main storage column.
 */
function isNumericType(string $t): bool
{
    return in_array($t, ['number', 'currency', 'percent', 'rating', 'progress', 'duration', 'formula'], true);
}

/**
 * Types that uses val_date as main storage column
 */
function isDateType(string $t): bool
{
    return in_array($t, ['date', 'datetime'], true);
}

/**
 * Renders the value of a field to UI exibition 
*/
function renderFieldValue(array $field, mixed $val, bool $full = false): string
{
    if ($val === null || $val === '') return '<span style="color:var(--mt)">—</span>';

    $t = $field['field_type'];

    // ── Simple types ────────────────────────────────────────────────
    if ($t === 'checkbox')    return $val ? '✅ Sim' : '❌ Não';
    if ($t === 'currency')    return 'R$ '.number_format((float)$val, 2, ',', '.');
    if ($t === 'percent')     return number_format((float)$val, 2, ',', '.').'%';
    if ($t === 'date')        return dateBr($val);
    if ($t === 'datetime')    return $val ? date('d/m/Y H:i', strtotime($val)) : '—';
    if ($t === 'time')        return h($val);
    if ($t === 'email')       return '<a href="mailto:'.h($val).'" style="color:var(--ac)">'.h($val).'</a>';
    if ($t === 'phone')       return '<a href="tel:'.h(preg_replace('/\D/','',$val)).'" style="color:var(--ac)">'.h($val).'</a>';
    if ($t === 'url')         return '<a href="'.h($val).'" target="_blank" style="color:var(--ac)">'.h(parse_url($val, PHP_URL_HOST) ?: $val).'</a>';
    if ($t === 'relation')    return '<span class="badge bc">#'.h($val).'</span>';

    // ── Rating (stars) ────────────────────────────────────────────
    if ($t === 'rating') {
        $n = (int)$val;
        return str_repeat('⭐', $n) . str_repeat('☆', max(0, 5 - $n));
    }

    // ── Progress (bar) ────────────────────────────────────────────
    if ($t === 'progress') {
        $pct = min(100, max(0, (int)$val));
        return '<div style="display:flex;align-items:center;gap:6px">
                  <div style="flex:1;background:var(--sf2);border-radius:4px;height:8px;overflow:hidden">
                    <div style="width:'.$pct.'%;height:100%;background:var(--ac);border-radius:4px"></div>
                  </div>
                  <span style="font-size:.78rem;color:var(--mt2);min-width:32px">'.$pct.'%</span>
                </div>';
    }

    // ── Duration (seconds → legible) ────────────────────────────────
    if ($t === 'duration') {
        $sec = (int)$val;
        $h   = intdiv($sec, 3600);
        $m   = intdiv($sec % 3600, 60);
        $s   = $sec % 60;
        $parts = [];
        if ($h) $parts[] = "{$h}h";
        if ($m) $parts[] = "{$m}min";
        if ($s || empty($parts)) $parts[] = "{$s}s";
        return h(implode(' ', $parts));
    }

    // ── Daterange ───────────────────────────────────────────────────
    if ($t === 'daterange') {
        $range = json_decode($val, true);
        if ($range && isset($range['start'])) {
            $end = isset($range['end']) ? ' → '.dateBr($range['end']) : '';
            return dateBr($range['start']).$end;
        }
        return h($val);
    }

    // ── Multiselect ─────────────────────────────────────────────────
    if ($t === 'multiselect' || $t === 'tags') {
        $items = json_decode($val, true) ?: [];
        return implode(' ', array_map(
            fn($v) => '<span class="badge bm" style="font-size:.7rem">'.h($v).'</span>',
            $items
        ));
    }

    // ── Color ───────────────────────────────────────────────────────
    if ($t === 'color') {
        return '<span style="display:inline-flex;align-items:center;gap:6px">
                  <span style="width:16px;height:16px;border-radius:3px;background:'.h($val).';border:1px solid var(--bd2);display:inline-block"></span>
                  <code style="font-size:.78rem">'.h($val).'</code>
                </span>';
    }

    // ── UUID ────────────────────────────────────────────────────────
    if ($t === 'uuid') {
        return '<code style="font-size:.78rem;color:var(--mt2)">'.h($val).'</code>';
    }

    // ── IP ──────────────────────────────────────────────────────────
    if ($t === 'ip') {
        return '<code style="font-size:.78rem">'.h($val).'</code>';
    }

    // ── JSON ────────────────────────────────────────────────────────
    if ($t === 'json') {
        if (!$full) return '<code style="font-size:.75rem;color:var(--mt2)">'.h(mb_substr($val,0,60)).(mb_strlen($val)>60?'…':'').'</code>';
        $pretty = json_encode(json_decode($val), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        return '<pre style="background:var(--sf2);border:1px solid var(--bd);border-radius:6px;padding:10px;font-size:.78rem;overflow:auto;max-height:300px">'.h($pretty ?: $val).'</pre>';
    }

    // ── Password (masked) ─────────────────────────────────────────
    if ($t === 'password') {
        return '<span style="letter-spacing:.1em;color:var(--mt)">••••••••</span>';
    }

    // ── User ────────────────────────────────────────────────────────
    if ($t === 'user') {
        $user = DB::one('SELECT name FROM users WHERE id = ?', [(int)$val]);
        return $user ? '<span class="badge bc">'.h($user['name']).'</span>' : '<span class="badge bm">#'.h($val).'</span>';
    }

    // ── Image (base64) ──────────────────────────────────────────────
    if ($t === 'image') {
        if (!str_starts_with($val, 'data:image/')) return h(mb_substr($val,0,40)).'…';
        if (!$full) {
            return '<img src="'.h($val).'" style="max-height:40px;max-width:80px;border-radius:4px;object-fit:cover;vertical-align:middle" loading="lazy">';
        }
        return '<img src="'.h($val).'" style="max-width:100%;max-height:400px;border-radius:6px;object-fit:contain" loading="lazy">';
    }

    // ── File (base64) ────────────────────────────────────────────
    if ($t === 'file') {
        if (!str_starts_with($val, 'data:')) return h(mb_substr($val,0,60));
        // Extrai nome do options_json se disponível — fallback genérico
        $meta = [];
        if (!empty($field['options_json'])) $meta = json_decode($field['options_json'], true) ?: [];
        $label = $meta['filename'] ?? 'arquivo';
        return '<a href="'.h($val).'" download="'.h($label).'" style="color:var(--ac)">📎 '.h($label).'</a>';
    }

    // ── Rich text ────────────────────────────────────────────────────
    if ($t === 'richtext') {
        if (!$full) return h(strip_tags(mb_substr($val,0,80))).(mb_strlen($val)>80?'…':'');
        // Exibe o HTML armazenado dentro de um sandbox
        return '<div style="background:var(--sf2);border:1px solid var(--bd);border-radius:6px;padding:12px;line-height:1.6;font-size:.88rem">'.
               $val. // confiamos que o richtext editor já sanitizou
               '</div>';
    }

    // ── Textarea ─────────────────────────────────────────────────────
    if ($t === 'textarea') {
        return $full
            ? '<div style="white-space:pre-wrap">'.h($val).'</div>'
            : h(mb_substr($val,0,80)).(mb_strlen($val)>80?'…':'');
    }

    // ── Formula (computed, read-only) ─────────────────────────────
    if ($t === 'formula') {
        if ($val === null || $val === '') return '<span style="color:var(--mt)">—</span>';
        $meta   = [];
        if (!empty($field['options_json'])) $meta = json_decode($field['options_json'], true) ?: [];
        $output = $meta['output'] ?? 'number';
        switch ($output) {
            case 'currency': return 'R$ '.number_format((float)$val, 2, ',', '.');
            case 'percent':  return number_format((float)$val, 2, ',', '.').'%';
            case 'text':     return h((string)$val);
            default:         return h(number_format((float)$val, 4, ',', '.') * 1);  // strip trailing zeros via multiplication trick
        }
    }

    // ── Hook: field.render_value ──────────────────────────────────────
    // Permite que plugins renderizem seus próprios tipos de campo.
    // Retorna null para indicar que o tipo não é tratado pelo plugin
    // (o core continua com o fallback h($val)).
    //
    // Exemplo de uso no Plugin.php:
    //   Hooks::filter('field.render_value', function(?string $html, array $field, mixed $val, bool $full): ?string {
    //       if ($field['field_type'] !== 'meu_tipo') return $html;
    //       return '<span>' . h($val) . '</span>';
    //   });
    $pluginHtml = \FlexCore\Core\Hooks\Hooks::applyFilter('field.render_value', null, [$field, $val, $full]);
    if ($pluginHtml !== null) {
        return $pluginHtml;
    }

    return h($val);
}

// ── Formula Evaluator ─────────────────────────────────────────────────────
/**
 * Avalia uma expressão de fórmula substituindo referências de campo {slug}
 * pelos valores numéricos do registro atual.
 *
 * Funções suportadas:
 *   SUM(slug1, slug2, ...)    → soma
 *   AVG(slug1, slug2, ...)    → média
 *   MIN(slug1, slug2, ...)    → mínimo
 *   MAX(slug1, slug2, ...)    → máximo
 *   IF(condition, then, else) → condicional
 *   ROUND(val, decimals)      → arredondamento
 *   ABS(val)                  → valor absoluto
 *   CONCAT(a, b, ...)         → concatenação de strings (saída text)
 *   {slug}                    → referência direta ao valor do campo
 *
 * @param string $expression   Expressão armazenada em options_json.expression
 * @param array  $fieldValues  [slug => valor_numerico_ou_texto] do registro
 * @param string $outputType   'number'|'currency'|'percent'|'text'
 * @return mixed               float para numérico, string para text, null em erro
 */
function evaluateFormula(string $expression, array $fieldValues, string $outputType = 'number'): mixed
{
    try {
        // Step 1: Replace all {slug} references with numeric values
        $expr = preg_replace_callback('/\{([a-z0-9_]+)\}/', function ($m) use ($fieldValues) {
            $val = $fieldValues[$m[1]] ?? 0;
            return is_numeric($val) ? (float) $val : 0;
        }, $expression);

        // Step 2: Evaluate high-level functions (slugs are already resolved to numbers)
        $expr = formulaResolveFunctions($expr, $fieldValues);

        // Step 3: Sanitize — allow only safe math characters
        $safe = preg_replace('/[^0-9+\-*\/.() \t\nE]/', '', $expr);

        if ($safe === '' || $safe === null) return null;

        // Step 4: Evaluate the arithmetic expression
        $result = eval("return ({$safe});");

        if (!is_numeric($result)) return null;

        return $outputType === 'text' ? (string) $result : (float) $result;

    } catch (\Throwable $e) {
        return null;
    }
}

/**
 * Resolve funções de alto nível (SUM, AVG, MIN, MAX, IF, ROUND, ABS, CONCAT)
 * numa expressão de fórmula antes do eval() matemático.
 *
 * @internal Chamado por evaluateFormula()
 */
function formulaResolveFunctions(string $expr, array $fieldValues): string
{
    // At this point {slug} references have already been replaced by numeric values
    // by evaluateFormula(). So we work only with numbers and operators here.

    // Helper: safely eval a numeric expression string
    $num = function (string $s): float {
        $clean = preg_replace('/[^0-9+\-*\/.()E ]/', '', trim($s));
        if ($clean === '') return 0.0;
        try { return (float) eval("return ({$clean});"); }
        catch (\Throwable $e) { return 0.0; }
    };

    // Helper: resolve a single arg — could be a number or a bare slug name
    $resolveArg = function (string $a) use ($fieldValues, $num): float {
        $a = trim($a);
        // Bare slug (only word chars, no operators)
        if (preg_match('/^[a-z][a-z0-9_]*$/i', $a) && isset($fieldValues[$a])) {
            return (float) $fieldValues[$a];
        }
        return $num($a);
    };

    // ── SUM(a, b, ...) ──────────────────────────────────────────────
    $expr = preg_replace_callback('/\bSUM\s*\(([^)]+)\)/i', function ($m) use ($resolveArg) {
        return (string) array_sum(array_map($resolveArg, explode(',', $m[1])));
    }, $expr);

    // ── AVG(a, b, ...) ──────────────────────────────────────────────
    $expr = preg_replace_callback('/\bAVG\s*\(([^)]+)\)/i', function ($m) use ($resolveArg) {
        $args = array_filter(array_map('trim', explode(',', $m[1])));
        if (!$args) return '0';
        return (string) (array_sum(array_map($resolveArg, $args)) / count($args));
    }, $expr);

    // ── MIN(a, b, ...) ──────────────────────────────────────────────
    $expr = preg_replace_callback('/\bMIN\s*\(([^)]+)\)/i', function ($m) use ($resolveArg) {
        return (string) min(array_map($resolveArg, explode(',', $m[1])));
    }, $expr);

    // ── MAX(a, b, ...) ──────────────────────────────────────────────
    $expr = preg_replace_callback('/\bMAX\s*\(([^)]+)\)/i', function ($m) use ($resolveArg) {
        return (string) max(array_map($resolveArg, explode(',', $m[1])));
    }, $expr);

    // ── ABS(expr) — simple: no nested parens expected after slug resolution ──
    $expr = preg_replace_callback('/\bABS\s*\(([^)]+)\)/i', function ($m) use ($num) {
        return (string) abs($num($m[1]));
    }, $expr);

    // ── ROUND(expr, decimals) — handles nested parens via balanced scan ──────
    // We scan character by character to find balanced ROUND(...) tokens
    $out = '';
    $i   = 0;
    $len = strlen($expr);
    while ($i < $len) {
        // Look for ROUND keyword
        if (substr($expr, $i, 5) === 'ROUND' || substr($expr, $i, 5) === 'round') {
            $j = $i + 5;
            while ($j < $len && $expr[$j] === ' ') $j++; // skip spaces
            if ($j < $len && $expr[$j] === '(') {
                // Find balanced closing paren
                $depth = 1; $k = $j + 1;
                while ($k < $len && $depth > 0) {
                    if ($expr[$k] === '(') $depth++;
                    elseif ($expr[$k] === ')') $depth--;
                    $k++;
                }
                // $expr[$j+1 .. $k-2] is inner content
                $inner    = substr($expr, $j + 1, $k - $j - 2);
                $lastComma = strrpos($inner, ',');
                if ($lastComma !== false) {
                    $valPart  = substr($inner, 0, $lastComma);
                    $decPart  = (int) trim(substr($inner, $lastComma + 1));
                    $out .= (string) round($num($valPart), $decPart);
                } else {
                    $out .= (string) $num($inner);
                }
                $i = $k;
                continue;
            }
        }
        $out .= $expr[$i];
        $i++;
    }
    $expr = $out;

    // ── IF(condition, then_val, else_val) ────────────────────────────
    // After slug resolution condition contains only numbers and operators
    $expr = preg_replace_callback('/\bIF\s*\((.+),([^,]+),([^)]+)\)/iU', function ($m) use ($num) {
        $cond  = preg_replace('/[^0-9+\-*\/.()<>=!&| ]/', '', $m[1]);
        $thenV = $num($m[2]);
        $elseV = $num($m[3]);
        try {
            $result = eval("return (bool)({$cond}) ? {$thenV} : {$elseV};");
            return (string)(float)$result;
        } catch (\Throwable $e) {
            return '0';
        }
    }, $expr);

    return $expr;
}

function audit(string $action, ?int $entityId, ?int $recordId, string $desc): void {
    DB::exec('INSERT INTO audit_log (user_id,action,entity_id,record_id,description,ip) VALUES (?,?,?,?,?,?)',
        [Auth::id()||null, $action, $entityId, $recordId, $desc, $_SERVER['REMOTE_ADDR']??null]);
}
