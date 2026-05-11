<?php
$flash   = getFlash();
$appName = DB::setting('app_name', 'FlexCore');
$appLogo = DB::setting('app_logo', '');
$favicon = DB::setting('app_favicon', '');
$accent  = DB::setting('color_accent', '#00d4ff');
$accent2 = DB::setting('color_accent2', '#6c5ce7');
$themeMode   = DB::setting('theme_mode', 'dark');
$themePreset = DB::setting('theme_preset', 'default');
$entities_menu = DB::q('SELECT id,name,slug,icon,color FROM entities WHERE active=1 ORDER BY position ASC, name ASC');

// Theme preset palettes
$presets = [
    'default' => ['ac' => '#00d4ff', 'ac2' => '#6c5ce7'],
    'ocean'   => ['ac' => '#3b82f6', 'ac2' => '#06b6d4'],
    'forest'  => ['ac' => '#22c55e', 'ac2' => '#10b981'],
    'sunset'  => ['ac' => '#f97316', 'ac2' => '#ef4444'],
    'violet'  => ['ac' => '#a855f7', 'ac2' => '#ec4899'],
    'rose'    => ['ac' => '#f43f5e', 'ac2' => '#fb923c'],
    'mono'    => ['ac' => '#94a3b8', 'ac2' => '#64748b'],
];
$pa = $presets[$themePreset] ?? $presets['default'];
$ac  = $accent  ?: $pa['ac'];
$ac2 = $accent2 ?: $pa['ac2'];

$isDark = $themeMode !== 'light';
if ($isDark) {
    $vars = "--bg:#07090e;--bg2:#0b0e18;--sf:#111622;--sf2:#161c2e;--sf3:#1c2340;--bd:rgba(255,255,255,.06);--bd2:rgba(255,255,255,.11);--tx:#eef0f8;--mt:#68718f;--mt2:#a0a8c0;";
} else {
    $vars = "--bg:#f1f5f9;--bg2:#ffffff;--sf:#ffffff;--sf2:#f8fafc;--sf3:#e2e8f0;--bd:rgba(0,0,0,.08);--bd2:rgba(0,0,0,.13);--tx:#0f172a;--mt:#94a3b8;--mt2:#475569;";
}
?><!DOCTYPE html>
<html lang="<?= h(currentLang()) ?>" data-theme="<?= $isDark ? 'dark' : 'light' ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= h($page_title ?? $appName) ?> — <?= h($appName) ?></title>
  <?php if ($favicon): ?><link rel="icon" href="<?= h($favicon) ?>"><?php endif; ?>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,700&display=swap" rel="stylesheet">
  <style>
    :root {
      --ac: <?= h($ac) ?>;
      --ac2: <?= h($ac2) ?>;
      <?= $vars ?>
      --gn: #22c55e; --rd: #ef4444; --am: #f59e0b;
      --fd: 'Syne',sans-serif; --fb: 'DM Sans',sans-serif;
      --r: 12px; --r2: 8px; --sb: 248px; --tr: .18s ease;
    }
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    html, body { height: 100%; }
    body { background: var(--bg); color: var(--tx); font-family: var(--fb); font-size: 14.5px; display: flex; }

    /* ── SIDEBAR ── */
    .sidebar {
      width: var(--sb); min-height: 100vh; background: var(--bg2);
      border-right: 1px solid var(--bd); display: flex; flex-direction: column;
      position: fixed; top: 0; left: 0; z-index: 200; overflow-y: auto;
      transition: transform var(--tr);
    }
    .sb-logo {
      padding: 22px 20px 18px;
      border-bottom: 1px solid var(--bd);
      font-family: var(--fd); font-weight: 800; font-size: 1.15rem;
      color: var(--ac); letter-spacing: -.01em;
      display: flex; align-items: center; gap: 10px;
    }
    .sb-logo .logo-dot { width: 8px; height: 8px; border-radius: 50%; background: var(--ac); flex-shrink: 0; }
    .sb-section { padding: 18px 12px 6px; }
    .sb-label {
      font-size: .65rem; font-weight: 700; letter-spacing: .1em;
      text-transform: uppercase; color: var(--mt); padding: 0 8px; margin-bottom: 4px;
    }
    .sb-link {
      display: flex; align-items: center; gap: 10px; padding: 8px 10px;
      border-radius: var(--r2); color: var(--mt2); text-decoration: none;
      font-size: .875rem; transition: all var(--tr); margin-bottom: 1px;
    }
    .sb-link:hover { background: var(--sf); color: var(--tx); }
    .sb-link.active { background: color-mix(in srgb, var(--ac) 12%, transparent); color: var(--ac); font-weight: 600; }
    .sb-link .ico { width: 20px; text-align: center; font-size: 1rem; flex-shrink: 0; }
    .sb-bottom { margin-top: auto; border-top: 1px solid var(--bd); padding: 12px; }
    .sb-user { display: flex; align-items: center; gap: 10px; padding: 8px; border-radius: var(--r2); }
    .sb-avatar {
      width: 32px; height: 32px; border-radius: 50%;
      background: color-mix(in srgb, var(--ac) 20%, var(--sf));
      display: flex; align-items: center; justify-content: center;
      font-family: var(--fd); font-weight: 700; font-size: .8rem; color: var(--ac); flex-shrink: 0;
    }
    .sb-user-name { font-size: .82rem; font-weight: 600; color: var(--tx); line-height: 1.2; }
    .sb-user-role { font-size: .7rem; color: var(--mt); }

    /* ── OVERLAY (mobile) ── */
    .sb-overlay {
      display: none; position: fixed; inset: 0; background: rgba(0,0,0,.55);
      z-index: 199; backdrop-filter: blur(2px);
    }
    .sb-overlay.open { display: block; }

    /* ── MAIN ── */
    .main { margin-left: var(--sb); flex: 1; min-height: 100vh; display: flex; flex-direction: column; }
    .topbar {
      background: var(--bg2); border-bottom: 1px solid var(--bd);
      display: flex; align-items: center; justify-content: space-between;
      padding: 15px 24px; position: sticky; top: 0; z-index: 50; gap: 12px;
    }
    .topbar-left { display: flex; align-items: center; gap: 12px; min-width: 0; }
    .breadcrumb { font-size: .82rem; color: var(--mt); display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
    .breadcrumb a { color: var(--mt2); text-decoration: none; white-space: nowrap; }
    .breadcrumb a:hover { color: var(--tx); }
    .breadcrumb .sep { color: var(--bd2); }
    .topbar-right { display: flex; align-items: center; gap: 8px; flex-shrink: 0; }

    /* ── HAMBURGUER ── */
    .btn-menu {
      display: none; align-items: center; justify-content: center;
      width: 36px; height: 36px; border-radius: var(--r2);
      background: var(--sf2); border: 1px solid var(--bd);
      cursor: pointer; color: var(--mt2); font-size: 1.2rem;
      flex-shrink: 0; transition: all var(--tr);
    }
    .btn-menu:hover { background: var(--sf3); color: var(--tx); }

    .content { width:100%; padding: 24px; flex: 1; box-sizing:border-box!important;}

    /* ── FLASH ── */
    .flash { border-radius: var(--r2); padding: 12px 16px; margin-bottom: 20px; font-size: .875rem; display: flex; align-items: center; gap: 10px; }
    .flash-ok  { background: rgba(34,197,94,.1);  border: 1px solid rgba(34,197,94,.25);  color: #86efac; }
    .flash-err { background: rgba(239,68,68,.1);  border: 1px solid rgba(239,68,68,.25);  color: #fca5a5; }
    .flash-info{ background: rgba(0,212,255,.08); border: 1px solid rgba(0,212,255,.2);   color: var(--ac); }

    /* ── CARDS ── */
    .card { background: var(--sf); border: 1px solid var(--bd); border-radius: var(--r); padding: 22px 24px; margin-bottom: 18px; }
    .card-title { font-family: var(--fd); font-size: .92rem; font-weight: 700; margin-bottom: 18px; padding-bottom: 14px; border-bottom: 1px solid var(--bd); color: var(--tx); }

    /* ── BUTTONS ── */
    .btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; border-radius: var(--r2); border: none; cursor: pointer; font-family: var(--fb); font-size: .855rem; font-weight: 500; transition: all var(--tr); text-decoration: none; white-space: nowrap; }
    .btn-primary { background: var(--ac); color: #000; font-weight: 700; }
    .btn-primary:hover { filter: brightness(1.1); }
    .btn-ghost { background: var(--sf2); color: var(--mt2); border: 1px solid var(--bd); }
    .btn-ghost:hover { background: var(--sf3); color: var(--tx); }
    .btn-danger { background: rgba(239,68,68,.12); color: var(--rd); border: 1px solid rgba(239,68,68,.2); }
    .btn-danger:hover { background: rgba(239,68,68,.22); }
    .btn-sm { padding: 5px 12px; font-size: .8rem; }
    .btn-xs { padding: 3px 9px; font-size: .75rem; }

    /* ── FORMS ── */
    .field { margin-bottom: 16px; }
    .field label { display: block; font-size: .78rem; font-weight: 700; color: var(--mt2); margin-bottom: 6px; letter-spacing: .03em; }
    .field input, .field select, .field textarea {
      width: 100%; background: var(--sf2); border: 1px solid var(--bd2); border-radius: var(--r2);
      color: var(--tx); padding: 9px 12px; font-family: var(--fb); font-size: .88rem;
      transition: border-color var(--tr); outline: none;
    }
    .field input:focus, .field select:focus, .field textarea:focus { border-color: var(--ac); }
    .field textarea { resize: vertical; min-height: 80px; }
    .field select option { background: var(--sf2); }
    .field .hint { font-size: .72rem; color: var(--mt); margin-top: 5px; }
    .form-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; padding-top: 18px; border-top: 1px solid var(--bd); flex-wrap: wrap; }
    .row2 { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
    .row3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 14px; }

    /* ── TABLES ── */
    .tbl-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; }
    table { width: 100%; border-collapse: collapse; min-width: 480px; }
    th { font-size: .72rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: var(--mt); padding: 10px 12px 10px; border-bottom: 1px solid var(--bd2); text-align: left; white-space: nowrap; }
    td { padding: 12px; border-bottom: 1px solid var(--bd); font-size: .86rem; color: var(--mt2); vertical-align: middle; }
    tr:last-child td { border-bottom: none; }
    tr:hover td { background: rgba(255,255,255,.015); }
    td a { color: var(--tx); text-decoration: none; font-weight: 600; }
    td a:hover { color: var(--ac); }
    .empty-row td { text-align: center; color: var(--mt); padding: 36px; min-width: unset; }
    .td-actions { display: flex; gap: 6px; align-items: center; }

    /* ── SECTION HEAD ── */
    .sec-head { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 22px; gap: 12px; flex-wrap: wrap; }
    .sec-title { font-family: var(--fd); font-size: 1.3rem; font-weight: 800; color: var(--tx); }
    .sec-sub { color: var(--mt); font-size: .83rem; margin-top: 3px; }
    .sec-actions { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }

    /* ── BADGES ── */
    .badge { display: inline-block; padding: 2px 9px; border-radius: 20px; font-size: .72rem; font-weight: 700; letter-spacing: .04em; }
    .bg { background: rgba(34,197,94,.12); color: #86efac; }
    .br { background: rgba(239,68,68,.12); color: #fca5a5; }
    .bm { background: rgba(100,116,139,.12); color: #94a3b8; }
    .bc { background: rgba(0,212,255,.1); color: var(--ac); }
    .ba { background: rgba(245,158,11,.12); color: #fcd34d; }

    /* ── STATS ── */
    .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 14px; margin-bottom: 24px; }
    .stat { background: var(--sf); border: 1px solid var(--bd); border-radius: var(--r); padding: 18px 20px; }
    .stat-val { font-family: var(--fd); font-size: 1.6rem; font-weight: 800; color: var(--tx); line-height: 1; margin: 6px 0 4px; }
    .stat-lbl { font-size: .75rem; color: var(--mt); }
    .stat-ico { font-size: 1.1rem; }

    /* ── ENTITY COLOR DOT ── */
    .ent-chip { display: inline-flex; align-items: center; gap: 7px; }
    .ent-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }

    /* ── DRAG HANDLE ── */
    .drag-handle { cursor: grab; color: var(--mt); font-size: 1rem; padding: 4px; }
    .drag-handle:active { cursor: grabbing; }

    /* ── RESPONSIVE ── */
    @media (max-width: 900px) {
      .row3 { grid-template-columns: 1fr 1fr; }
    }

    @media (max-width: 768px) {
      /* Sidebar vira drawer */
      .sidebar {
        transform: translateX(-100%);
        box-shadow: 4px 0 24px rgba(0,0,0,.4);
      }
      .sidebar.open { transform: translateX(0); }
      .main { margin-left: 0; }
      .btn-menu { display: flex; }

      /* Content padding menor */
      .content { padding: 16px; }
      .topbar { padding: 10px 16px; }

      /* Stats em 2 colunas */
      .stats { grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); }

      /* Section head empilha */
      .sec-head { flex-direction: column; align-items: stretch; }
      .sec-actions { justify-content: flex-start; }

      /* Form rows viram 1 coluna */
      .row2, .row3 { grid-template-columns: 1fr; }

      /* Form actions estica botões */
      .form-actions { justify-content: stretch; }
      .form-actions .btn { justify-content: center; flex: 1; }

      /* Card padding menor */
      .card { padding: 16px; }
    }

    @media (max-width: 480px) {
      .stats { grid-template-columns: 1fr 1fr; }
      .sec-title { font-size: 1.1rem; }
      .breadcrumb { font-size: .75rem; }
      .td-actions { flex-wrap: wrap; }
    }
  </style>
</head>
<body>

<!-- OVERLAY mobile -->
<div class="sb-overlay" id="sbOverlay" onclick="closeSidebar()"></div>

<!-- SIDEBAR -->
<nav class="sidebar" id="sidebar">
  <div class="sb-logo">
    <?php if ($appLogo): ?>
      <img src="<?= h($appLogo) ?>" alt="<?= h($appName) ?>"
           style="max-height:32px;max-width:160px;object-fit:contain">
    <?php else: ?>
      <div class="logo-dot"></div>
      <?= h($appName) ?>
    <?php endif; ?>
  </div>

  <div class="sb-section">
    <div class="sb-label"><?= __('nav.general') ?></div>
    <a href="<?= url('/') ?>" class="sb-link <?= ($active_page??'')==='home'?'active':'' ?>" onclick="closeSidebar()">
      <span class="ico">🏠</span> <?= __('nav.dashboard') ?>
    </a>
  </div>

  <?php if (!empty($entities_menu)): ?>
  <div class="sb-section">
    <div class="sb-label"><?= __('nav.entities_menu') ?></div>
    <?php foreach ($entities_menu as $em): ?>
    <a href="<?= url('/e/' . h($em['slug'])) ?>" class="sb-link <?= ($active_entity??'')===$em['slug']?'active':'' ?>" onclick="closeSidebar()">
      <span class="ico"><?= h($em['icon']) ?></span>
      <?= h($em['name']) ?>
    </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <?php if (Auth::user()['role'] === 'admin'): ?>
  <div class="sb-section">
    <div class="sb-label"><?= __('nav.integrations') ?></div>
    <a href="<?= url('/api') ?>" class="sb-link <?= in_array($active_page??'',['api','api-docs'])?'active':'' ?>" onclick="closeSidebar()">
      <span class="ico">🔑</span> <?= __('nav.api_keys') ?>
    </a>
    <a href="<?= url('/automations') ?>" class="sb-link <?= ($active_page??'')==='automations'?'active':'' ?>" onclick="closeSidebar()">
      <span class="ico">⚡</span> <?= __('nav.automations') ?>
    </a>
    <a href="<?= url('/plugins') ?>" class="sb-link <?= ($active_page??'')==='plugins'?'active':'' ?>" onclick="closeSidebar()">
      <span class="ico">🧩</span> <?= __('nav.plugins') ?>
    </a>
    <?php if (DB::one("SELECT id FROM plugins WHERE plugin_id = 'flexcore-data-importer' AND active = 1")): ?>
    <a href="<?= url('/importer') ?>" class="sb-link <?= ($active_page??'')==='importer'?'active':'' ?>" onclick="closeSidebar()">
      <span class="ico">📥</span> Data Importer
    </a>
    <?php endif; ?>
  </div>
  <div class="sb-section">
    <div class="sb-label"><?= __('nav.admin') ?></div>
    <a href="<?= url('/entities') ?>" class="sb-link <?= ($active_page??'')==='entities'?'active':'' ?>" onclick="closeSidebar()">
      <span class="ico">⚙️</span> <?= __('nav.entities') ?>
    </a>
    <a href="<?= url('/settings') ?>" class="sb-link <?= ($active_page??'')==='settings'?'active':'' ?>" onclick="closeSidebar()">
      <span class="ico">🔧</span> <?= __('nav.settings') ?>
    </a>
    <a href="<?= url('/settings?tab=usuarios') ?>" class="sb-link <?= ($active_page??'')==='users'?'active':'' ?>" onclick="closeSidebar()">
      <span class="ico">👥</span> <?= __('nav.users') ?>
    </a>
  </div>
  <?php endif; ?>

  <div class="sb-bottom">
    <div class="sb-user">
      <div class="sb-avatar"><?= mb_strtoupper(mb_substr(Auth::user()['name']??'?', 0, 1)) ?></div>
      <div>
        <div class="sb-user-name"><?= h(explode(' ', Auth::user()['name']??'')[0]) ?></div>
        <div class="sb-user-role"><?= h(Auth::user()['role']??'') ?></div>
      </div>
    </div>
    <a href="<?= url('logout');?>" class="sb-link" style="margin-top:4px">
      <span class="ico">🚪</span> <?= __('nav.logout') ?>
    </a>
  </div>
</nav>

<!-- MAIN -->
<div class="main">
  <div class="topbar">
    <div class="topbar-left">
      <button class="btn-menu" id="btnMenu" onclick="openSidebar()" aria-label="<?= __('layout.header.open_menu') ?>">
        ☰
      </button>
      <div class="breadcrumb">
        <?php if (!empty($breadcrumbs)): ?>
          <?php foreach ($breadcrumbs as $i => $b): ?>
            <?php if ($i > 0): ?><span class="sep">›</span><?php endif; ?>
            <?php if (isset($b['url'])): ?><a href="<?= h($b['url']) ?>"><?= h($b['label']) ?></a>
            <?php else: ?><span><?= h($b['label']) ?></span><?php endif; ?>
          <?php endforeach; ?>
        <?php else: ?>
          <span><?= h($page_title ?? $appName) ?></span>
        <?php endif; ?>
      </div>
    </div>
    <div class="topbar-right">
      <span style="font-size:.75rem;color:var(--mt)"><?= date('d/m/Y') ?></span>
    </div>
  </div>

  <div class="content">
    <?php if ($flash): ?>
    <div class="flash flash-<?= h($flash['type']) ?>">
      <?= $flash['type']==='ok'?'✅':($flash['type']==='err'?'❌':'ℹ️') ?>
      <?= h($flash['msg']) ?>
    </div>
    <?php endif; ?>

<script>
function openSidebar() {
  document.getElementById('sidebar').classList.add('open');
  document.getElementById('sbOverlay').classList.add('open');
  document.getElementById('btnMenu').setAttribute('aria-label', '<?= __('layout.header.close_menu') ?>');
}
function closeSidebar() {
  document.getElementById('sidebar').classList.remove('open');
  document.getElementById('sbOverlay').classList.remove('open');
  document.getElementById('btnMenu').setAttribute('aria-label', '<?= __('layout.header.open_menu') ?>');
}
// Fecha com ESC
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') closeSidebar();
});
</script>
