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

/**
 * Redireciona para um caminho relativo à raiz do app (BASE_PATH).
 * Ex: redirect('/entities') → Location: /subpasta/entities
 */
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

/**
 * Gera URL absoluta relativa ao BASE_PATH.
 * Ex: url('logout') → /subpasta/logout
 *     url('/e/clientes') → /subpasta/e/clientes
 */
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
function fieldTypeLabel(string $t): string {
    return match($t) {
        'text'        => 'Texto curto',
        'textarea'    => 'Texto longo',
        'number'      => 'Número',
        'email'       => 'E-mail',
        'url'         => 'URL',
        'phone'       => 'Telefone',
        'date'        => 'Data',
        'datetime'    => 'Data e hora',
        'select'      => 'Lista (escolha única)',
        'multiselect' => 'Lista (múltipla escolha)',
        'checkbox'    => 'Caixa de seleção',
        'currency'    => 'Moeda (R$)',
        'relation'    => 'Relação com outra entidade',
        'file'        => 'Arquivo',
        default       => $t,
    };
}
function fieldTypeIcon(string $t): string {
    return match($t) {
        'text'        => '🔤',
        'textarea'    => '📝',
        'number'      => '🔢',
        'email'       => '✉️',
        'url'         => '🔗',
        'phone'       => '📞',
        'date'        => '📅',
        'datetime'    => '🕐',
        'select'      => '▼',
        'multiselect' => '☑️',
        'checkbox'    => '✅',
        'currency'    => '💰',
        'relation'    => '🔀',
        'file'        => '📎',
        default       => '❓',
    };
}
/**
 * Renderiza o valor de um campo para exibição na UI.
 * Movida de index.php para cá para permitir reuso e testes.
 */
function renderFieldValue(array $field, mixed $val, bool $full = false): string {
    if ($val === null || $val === '') return '<span style="color:var(--mt)">—</span>';
    return match($field['field_type']) {
        'checkbox'    => $val ? '✅ Sim' : '—',
        'currency'    => 'R$ '.number_format((float)$val, 2, ',', '.'),
        'url'         => '<a href="'.h($val).'" target="_blank" style="color:var(--ac)">'.h(parse_url($val, PHP_URL_HOST) ?: $val).'</a>',
        'email'       => '<a href="mailto:'.h($val).'" style="color:var(--ac)">'.h($val).'</a>',
        'phone'       => '<a href="tel:'.h(preg_replace('/\D/','',$val)).'" style="color:var(--ac)">'.h($val).'</a>',
        'date'        => dateBr($val),
        'datetime'    => $val ? date('d/m/Y H:i', strtotime($val)) : '—',
        'multiselect' => implode(', ', array_map(fn($v) => '<span class="badge bm" style="font-size:.7rem">'.h($v).'</span>', json_decode($val, true) ?: [])),
        'textarea'    => $full ? '<div style="white-space:pre-wrap">'.h($val).'</div>' : h(mb_substr($val,0,80)).(mb_strlen($val)>80?'…':''),
        'relation'    => '<span class="badge bc">#'.h($val).'</span>',
        default       => h($val),
    };
}

function audit(string $action, ?int $entityId, ?int $recordId, string $desc): void {
    DB::exec('INSERT INTO audit_log (user_id,action,entity_id,record_id,description,ip) VALUES (?,?,?,?,?,?)',
        [Auth::id()||null, $action, $entityId, $recordId, $desc, $_SERVER['REMOTE_ADDR']??null]);
}
