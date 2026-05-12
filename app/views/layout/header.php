<?php
$flash   = getFlash();
$appName = DB::setting('app_name', 'FlexCore');
$appLogo = DB::setting('app_logo', '');
$favicon = DB::setting('app_favicon', '');
$accent  = DB::setting('color_accent', '#4f7ef8');
$accent2 = DB::setting('color_accent2', '#7c3aed');
$themeMode   = DB::setting('theme_mode', 'dark');
$themePreset = DB::setting('theme_preset', 'default');
$entities_menu = DB::q('SELECT id,name,slug,icon,color FROM entities WHERE active=1 ORDER BY position ASC, name ASC');

$presets = [
    'default' => ['ac' => '#4f7ef8', 'ac2' => '#7c3aed'],
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
    $vars = "--bg:#0b0f1a;--sb-bg:#0f1421;--sb-bd:rgba(255,255,255,.06);--sf:rgba(255,255,255,.04);--sf2:rgba(255,255,255,.03);--sf3:rgba(255,255,255,.025);--bd:rgba(255,255,255,.07);--bd2:rgba(255,255,255,.12);--tx:#e8edf5;--mt:#8b95a7;--mt2:#5a6478;--gn:#22c55e;--gn-bg:rgba(34,197,94,.1);--rd:#f87171;--rd-bg:rgba(248,113,113,.1);--am:#fbbf24;--am-bg:rgba(251,191,36,.1);--shd:0 1px 3px rgba(0,0,0,.4),0 4px 24px rgba(0,0,0,.3);--shd-sm:0 1px 2px rgba(0,0,0,.3);";
} else {
    $vars = "--bg:#f0f2f5;--sb-bg:#ffffff;--sb-bd:rgba(0,0,0,.07);--sf:rgba(255,255,255,.9);--sf2:rgba(255,255,255,.6);--sf3:rgba(0,0,0,.025);--bd:rgba(0,0,0,.07);--bd2:rgba(0,0,0,.11);--tx:#0d1117;--mt:#64748b;--mt2:#94a3b8;--gn:#16a34a;--gn-bg:rgba(22,163,74,.09);--rd:#dc2626;--rd-bg:rgba(220,38,38,.09);--am:#d97706;--am-bg:rgba(217,119,6,.09);--shd:0 1px 3px rgba(0,0,0,.06),0 4px 16px rgba(0,0,0,.05);--shd-sm:0 1px 2px rgba(0,0,0,.04);";
}
?><!DOCTYPE html>
<html lang="<?= h(currentLang()) ?>" data-theme="<?= $isDark ? 'dark' : 'light' ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= h($page_title ?? $appName) ?> — <?= h($appName) ?></title>
  <?php if ($favicon): ?><link rel="icon" href="<?= h($favicon) ?>"><?php endif; ?>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
  <style>
    :root {
      --ac:<?= h($ac) ?>;--ac2:<?= h($ac2) ?>;
      --ac-glow:color-mix(in srgb,var(--ac) 15%,transparent);
      --ac-fg:#fff;
      <?= $vars ?>
      --font:'Plus Jakarta Sans',sans-serif;
      --mono:'DM Mono',monospace;
      --r:10px;--r2:7px;--r3:5px;
      --sb:224px;--tbh:52px;--tr:.15s cubic-bezier(.4,0,.2,1);
    }
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
    html,body{height:100%}
    body{background:var(--bg);color:var(--tx);font-family:var(--font);font-size:14px;line-height:1.5;display:flex;-webkit-font-smoothing:antialiased}

    /* ── SIDEBAR ── */
    .sidebar{
      width:var(--sb);min-height:100vh;
      background:var(--sb-bg);border-right:1px solid var(--sb-bd);
      display:flex;flex-direction:column;
      position:fixed;top:0;left:0;z-index:200;overflow-y:auto;
      transition:transform var(--tr);
    }
    .sb-logo{
      height:var(--tbh);padding:0 16px;
      border-bottom:1px solid var(--sb-bd);
      display:flex;align-items:center;gap:10px;flex-shrink:0;
    }
    .sb-logo-mark{width:28px;height:28px;border-radius:8px;background:var(--ac);display:flex;align-items:center;justify-content:center;flex-shrink:0}
    .sb-logo-mark svg{width:14px;height:14px;fill:none;stroke:#fff;stroke-width:2.5;stroke-linecap:round}
    .sb-logo-name{font-size:.84rem;font-weight:700;letter-spacing:-.01em;color:var(--tx)}
    .sb-logo img{max-height:28px;max-width:150px;object-fit:contain}
    .sb-section{padding:14px 8px 4px}
    .sb-label{font-size:.58rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--mt2);padding:0 9px;margin-bottom:3px}
    .sb-link{
      display:flex;align-items:center;gap:9px;padding:7px 9px;
      border-radius:var(--r2);color:var(--mt);text-decoration:none;
      font-size:.79rem;font-weight:500;transition:all var(--tr);margin-bottom:1px;cursor:pointer;
    }
    .sb-link:hover{background:var(--sf3);color:var(--tx)}
    .sb-link.active{background:var(--ac-glow);color:var(--ac);font-weight:600}
    .sb-link .ico{width:16px;text-align:center;font-size:.9rem;flex-shrink:0}
    .sb-ent-dot{width:14px;height:14px;border-radius:4px;flex-shrink:0}
    .sb-pill{font-size:.58rem;font-weight:700;background:var(--ac-glow);color:var(--ac);border-radius:20px;padding:1px 6px;margin-left:auto}
    .sb-bottom{margin-top:auto;border-top:1px solid var(--sb-bd);padding:10px 8px}
    .sb-user{display:flex;align-items:center;gap:9px;padding:7px 9px;border-radius:var(--r2)}
    .sb-avatar{width:28px;height:28px;border-radius:50%;background:var(--ac-glow);border:1.5px solid var(--ac);display:flex;align-items:center;justify-content:center;font-size:.65rem;font-weight:700;color:var(--ac);flex-shrink:0}
    .sb-user-name{font-size:.76rem;font-weight:600;color:var(--tx);line-height:1.2}
    .sb-user-role{font-size:.62rem;color:var(--mt2)}

    /* ── OVERLAY ── */
    .sb-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:199;backdrop-filter:blur(2px)}
    .sb-overlay.open{display:block}

    /* ── MAIN ── */
    .main{margin-left:var(--sb);flex:1;min-height:100vh;display:flex;flex-direction:column}

    /* ── TOPBAR — mesma cor do sidebar, unidade visual ── */
    .topbar{
      height:var(--tbh);
      background:var(--sb-bg);border-bottom:1px solid var(--sb-bd);
      display:flex;align-items:center;justify-content:space-between;
      padding:0 22px;position:sticky;top:0;z-index:50;gap:12px;flex-shrink:0;
    }
    .topbar-left{display:flex;align-items:center;gap:10px;min-width:0}
    .breadcrumb{font-size:.74rem;color:var(--mt2);display:flex;align-items:center;gap:5px;flex-wrap:wrap}
    .breadcrumb a{color:var(--mt);text-decoration:none;transition:color var(--tr)}
    .breadcrumb a:hover{color:var(--tx)}
    .breadcrumb .sep{color:var(--mt2);opacity:.5}
    .topbar-right{display:flex;align-items:center;gap:6px;flex-shrink:0}

    /* ── HAMBURGER ── */
    .btn-menu{display:none;align-items:center;justify-content:center;width:32px;height:32px;border-radius:var(--r2);background:transparent;border:1px solid var(--bd2);cursor:pointer;color:var(--mt);font-size:1rem;flex-shrink:0;transition:all var(--tr)}
    .btn-menu:hover{background:var(--sf3);color:var(--tx)}

    /* ── CONTENT ── */
    .content{width:100%;padding:22px;flex:1;background:var(--bg)}

    /* ── FLASH ── */
    .flash{border-radius:var(--r2);padding:10px 14px;margin-bottom:18px;font-size:.8rem;display:flex;align-items:center;gap:9px}
    .flash-ok{background:var(--gn-bg);border:1px solid color-mix(in srgb,var(--gn) 25%,transparent);color:var(--gn)}
    .flash-err{background:var(--rd-bg);border:1px solid color-mix(in srgb,var(--rd) 25%,transparent);color:var(--rd)}
    .flash-info{background:var(--ac-glow);border:1px solid color-mix(in srgb,var(--ac) 20%,transparent);color:var(--ac)}

    /* ── CARDS ── */
    .card{background:var(--sf);border:1px solid var(--bd);border-radius:12px;padding:20px 22px;margin-bottom:16px;box-shadow:var(--shd-sm);transition:border-color var(--tr)}
    .card:hover{border-color:var(--bd2)}
    .card-title{font-size:.78rem;font-weight:700;color:var(--tx);margin-bottom:16px;padding-bottom:12px;border-bottom:1px solid var(--bd);letter-spacing:-.01em}

    /* ── BUTTONS ── */
    .btn{display:inline-flex;align-items:center;gap:6px;padding:7px 15px;border-radius:var(--r2);border:none;cursor:pointer;font-family:var(--font);font-size:.78rem;font-weight:600;transition:all var(--tr);text-decoration:none;white-space:nowrap;letter-spacing:-.01em}
    .btn-primary{background:var(--ac);color:var(--ac-fg)}
    .btn-primary:hover{filter:brightness(1.08);box-shadow:0 0 0 3px var(--ac-glow)}
    .btn-ghost{background:transparent;color:var(--mt);border:1px solid var(--bd2)}
    .btn-ghost:hover{background:var(--sf3);color:var(--tx)}
    .btn-danger{background:var(--rd-bg);color:var(--rd);border:1px solid color-mix(in srgb,var(--rd) 20%,transparent)}
    .btn-danger:hover{background:color-mix(in srgb,var(--rd) 20%,transparent)}
    .btn-sm{padding:5px 11px;font-size:.74rem}
    .btn-xs{padding:3px 8px;font-size:.68rem}

    /* ── FORMS ── */
    .field{margin-bottom:14px}
    .field label{display:block;font-size:.65rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:var(--mt2);margin-bottom:5px}
    .field input,.field select,.field textarea{width:100%;background:var(--bg);border:1px solid var(--bd2);border-radius:var(--r2);color:var(--tx);padding:8px 11px;font-family:var(--font);font-size:.82rem;transition:all var(--tr);outline:none}
    .field input:focus,.field select:focus,.field textarea:focus{border-color:var(--ac);box-shadow:0 0 0 3px var(--ac-glow)}
    .field textarea{resize:vertical;min-height:80px}
    .field select option{background:var(--sb-bg)}
    .field .hint{font-size:.7rem;color:var(--mt2);margin-top:4px}
    .form-actions{display:flex;justify-content:flex-end;gap:8px;margin-top:18px;padding-top:16px;border-top:1px solid var(--bd);flex-wrap:wrap}
    .row2{display:grid;grid-template-columns:1fr 1fr;gap:12px}
    .row3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px}

    /* ── TABLES ── */
    .tbl-wrap{overflow-x:auto;-webkit-overflow-scrolling:touch}
    table{width:100%;border-collapse:collapse;min-width:480px}
    th{font-size:.62rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--mt2);padding:9px 12px;border-bottom:1px solid var(--bd2);text-align:left;white-space:nowrap}
    td{padding:10px 12px;border-bottom:1px solid var(--bd);font-size:.8rem;color:var(--mt);vertical-align:middle}
    tr:last-child td{border-bottom:none}
    tr:hover td{background:var(--sf3)}
    td a{color:var(--tx);text-decoration:none;font-weight:600}
    td a:hover{color:var(--ac)}
    .empty-row td{text-align:center;color:var(--mt2);padding:40px;min-width:unset}
    .td-actions{display:flex;gap:5px;align-items:center}

    /* ── SECTION HEAD ── */
    .sec-head{display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:20px;gap:12px;flex-wrap:wrap}
    .sec-title{font-size:1.1rem;font-weight:700;letter-spacing:-.02em;color:var(--tx)}
    .sec-sub{color:var(--mt2);font-size:.74rem;margin-top:3px}
    .sec-actions{display:flex;gap:7px;align-items:center;flex-wrap:wrap}

    /* ── BADGES ── */
    .badge{display:inline-flex;align-items:center;gap:4px;padding:2px 8px;border-radius:20px;font-size:.64rem;font-weight:700;letter-spacing:.03em}
    .badge-dot{width:5px;height:5px;border-radius:50%;flex-shrink:0}
    .bg{background:var(--gn-bg);color:var(--gn)}.bg .badge-dot{background:var(--gn)}
    .br{background:var(--rd-bg);color:var(--rd)}.br .badge-dot{background:var(--rd)}
    .bm{background:var(--sf3);color:var(--mt);border:1px solid var(--bd)}
    .bc{background:var(--ac-glow);color:var(--ac)}
    .ba{background:var(--am-bg);color:var(--am)}.ba .badge-dot{background:var(--am)}

    /* ── STATS ── */
    .stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:12px;margin-bottom:22px}
    .stat{background:var(--sf);border:1px solid var(--bd);border-radius:10px;padding:16px 18px;transition:all var(--tr);cursor:pointer}
    .stat:hover{border-color:var(--ac);box-shadow:0 0 0 3px var(--ac-glow)}
    .stat-val{font-size:1.5rem;font-weight:700;letter-spacing:-.03em;color:var(--tx);margin:5px 0 3px}
    .stat-lbl{font-size:.64rem;font-weight:600;text-transform:uppercase;letter-spacing:.07em;color:var(--mt2)}
    .stat-ico{font-size:1rem}

    /* ── MISC ── */
    .ent-chip{display:inline-flex;align-items:center;gap:7px}
    .ent-dot{width:8px;height:8px;border-radius:50%;flex-shrink:0}
    .tag{display:inline-block;padding:1px 8px;border-radius:20px;font-size:.64rem;font-weight:600;background:var(--sf3);border:1px solid var(--bd2);color:var(--mt)}
    .kbd{display:inline-block;padding:1px 6px;border-radius:4px;font-size:.62rem;font-family:var(--mono);background:var(--sf3);border:1px solid var(--bd2);color:var(--mt2)}
    .drag-handle{cursor:grab;color:var(--mt2);font-size:1rem;padding:4px}
    .drag-handle:active{cursor:grabbing}
    ::-webkit-scrollbar{width:4px;height:4px}
    ::-webkit-scrollbar-track{background:transparent}
    ::-webkit-scrollbar-thumb{background:var(--bd2);border-radius:4px}

    /* ── RESPONSIVE ── */
    @media(max-width:900px){.row3{grid-template-columns:1fr 1fr}}
    @media(max-width:768px){
      .sidebar{transform:translateX(-100%);box-shadow:4px 0 24px rgba(0,0,0,.3)}
      .sidebar.open{transform:translateX(0)}
      .main{margin-left:0}
      .btn-menu{display:flex}
      .content{padding:16px}
      .topbar{padding:0 16px}
      .stats{grid-template-columns:repeat(auto-fit,minmax(120px,1fr))}
      .sec-head{flex-direction:column;align-items:stretch}
      .sec-actions{justify-content:flex-start}
      .row2,.row3{grid-template-columns:1fr}
      .form-actions{justify-content:stretch}
      .form-actions .btn{justify-content:center;flex:1}
      .card{padding:16px}
    }
    @media(max-width:480px){
      .stats{grid-template-columns:1fr 1fr}
      .sec-title{font-size:.95rem}
      .breadcrumb{font-size:.68rem}
      .td-actions{flex-wrap:wrap}
    }
  </style>
</head>
<body>

<div class="sb-overlay" id="sbOverlay" onclick="closeSidebar()"></div>

<nav class="sidebar" id="sidebar">
  <div class="sb-logo">
    <?php if ($appLogo): ?>
      <img src="<?= h($appLogo) ?>" alt="<?= h($appName) ?>">
    <?php else: ?>
      <div class="sb-logo-mark">
        <svg viewBox="0 0 14 14"><path d="M2 2h4v4H2zM8 2h4v4H8zM2 8h4v4H2z"/><circle cx="10" cy="10" r="2"/></svg>
      </div>
      <span class="sb-logo-name"><?= h($appName) ?></span>
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
      <span class="sb-ent-dot" style="background:<?= h($em['color']) ?>"></span>
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
    <a href="<?= url('logout'); ?>" class="sb-link" style="margin-top:4px">
      <span class="ico">🚪</span> <?= __('nav.logout') ?>
    </a>
  </div>
</nav>

<div class="main">
  <div class="topbar">
    <div class="topbar-left">
      <button class="btn-menu" id="btnMenu" onclick="openSidebar()" aria-label="<?= __('layout.header.open_menu') ?>">☰</button>
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
      <span style="font-size:.72rem;color:var(--mt2)"><?= date('d/m/Y') ?></span>
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
function openSidebar(){document.getElementById('sidebar').classList.add('open');document.getElementById('sbOverlay').classList.add('open')}
function closeSidebar(){document.getElementById('sidebar').classList.remove('open');document.getElementById('sbOverlay').classList.remove('open')}
document.addEventListener('keydown',function(e){if(e.key==='Escape')closeSidebar()});
</script>
