<?php
$page_title  = __('plugins.title');
$active_page = 'plugins';
$breadcrumbs = [['label' => __('plugins.title')]];
partial('layout/header');

// IDs dos plugins já instalados (para marcar no marketplace)
$installedIds = array_column($plugins, 'plugin_id');
?>

<div class="sec-head">
  <div>
    <div class="sec-title">🧩 <?= __('plugins.title') ?></div>
    <div class="sec-sub">Gerencie e descubra plugins para o FlexCore</div>
  </div>
  <div class="sec-actions">
    <a href="<?= admin_url('/plugins/docs') ?>" class="btn btn-ghost btn-sm"><?= __('plugins.how_to_create') ?></a>
    <button class="btn btn-primary" onclick="openUploadModal()">⬆️ <?= __('plugins.install') ?> via ZIP</button>
  </div>
</div>

<!-- ── TABS ── -->
<div style="display:flex;gap:2px;margin-bottom:20px;background:var(--sf);border:1px solid var(--bd);border-radius:var(--r2);padding:3px;width:fit-content">
  <button onclick="switchTab('instalados')" id="tab-instalados"
    style="padding:6px 18px;border:none;border-radius:calc(var(--r2) - 1px);cursor:pointer;font-family:var(--font);font-size:.78rem;font-weight:600;transition:all var(--tr);background:var(--ac);color:var(--ac-fg)">
    Instalados <?php if (!empty($plugins)): ?>
      <span style="background:rgba(255,255,255,.25);border-radius:20px;padding:1px 7px;font-size:.68rem"><?= count($plugins) ?></span>
    <?php endif; ?>
  </button>
  <button onclick="switchTab('marketplace')" id="tab-marketplace"
    style="padding:6px 18px;border:none;border-radius:calc(var(--r2) - 1px);cursor:pointer;font-family:var(--font);font-size:.78rem;font-weight:600;transition:all var(--tr);background:transparent;color:var(--mt)">
    🌐 Marketplace <?php if (!empty($registry)): ?>
      <span style="background:var(--ac-glow);color:var(--ac);border-radius:20px;padding:1px 7px;font-size:.68rem"><?= count($registry) ?></span>
    <?php endif; ?>
  </button>
</div>

<!-- ════════════════════════════════════════
     TAB: INSTALADOS
════════════════════════════════════════ -->
<div id="pane-instalados">
  <?php if (empty($plugins)): ?>
  <div class="card" style="text-align:center;padding:56px 24px">
    <div style="font-size:2.8rem;margin-bottom:14px">🧩</div>
    <div style="font-weight:700;margin-bottom:8px;font-size:.9rem"><?= __('plugins.no_plugins') ?></div>
    <div style="color:var(--mt);font-size:.8rem;margin-bottom:20px">Instale plugins via ZIP ou explore o Marketplace.</div>
    <div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap">
      <button class="btn btn-primary" onclick="openUploadModal()">⬆️ Instalar via ZIP</button>
      <button class="btn btn-ghost" onclick="switchTab('marketplace')">🌐 Ver Marketplace</button>
    </div>
  </div>
  <?php else: ?>
  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px">
    <?php foreach ($plugins as $p):
      $manifest = json_decode($p['manifest'], true) ?? [];
      $hooks    = $manifest['hooks'] ?? [];
    ?>
    <div class="card" style="margin:0;display:flex;flex-direction:column">
      <div style="display:flex;align-items:flex-start;gap:12px;margin-bottom:12px">
        <span style="font-size:1.8rem">🧩</span>
        <div style="flex:1">
          <div style="font-weight:700;font-size:.95rem"><?= h($p['name']) ?></div>
          <div style="font-size:.75rem;color:var(--mt)">v<?= h($p['version']) ?><?= $p['author'] ? ' · '.h($p['author']) : '' ?></div>
        </div>
        <span class="badge <?= $p['active'] ? 'bg' : 'br' ?>"><?= $p['active'] ? __('general.active') : __('general.inactive') ?></span>
      </div>

      <p style="color:var(--mt);font-size:.83rem;line-height:1.6;flex:1;margin-bottom:12px"><?= h($p['description'] ?? '') ?></p>

      <?php if (!empty($hooks)): ?>
      <div style="margin-bottom:12px">
        <div style="font-size:.7rem;color:var(--mt);font-weight:700;text-transform:uppercase;letter-spacing:.07em;margin-bottom:6px"><?= __('plugins.hooks') ?></div>
        <div style="display:flex;flex-wrap:wrap;gap:4px">
          <?php foreach ($hooks as $hk): ?>
          <code style="background:var(--sf2);padding:2px 7px;border-radius:4px;font-size:.7rem;color:var(--mt2)"><?= h($hk) ?></code>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <div style="display:flex;gap:8px;padding-top:12px;border-top:1px solid var(--bd);flex-wrap:wrap">
        <?php if (!empty($manifest['settings'])): ?>
        <button class="btn btn-ghost btn-sm" onclick='openSettings(<?= json_encode($p) ?>)'><?= __('plugins.configure') ?></button>
        <?php endif; ?>
        <form method="POST" action="<?= admin_url('/plugins/'.$p['plugin_id'].'/toggle') ?>" style="display:inline">
          <button class="btn btn-ghost btn-sm"><?= $p['active'] ? '⏸ '.__('general.disable') : '▶️ '.__('general.enable') ?></button>
        </form>
        <form method="POST" action="<?= admin_url('/plugins/'.$p['plugin_id'].'/uninstall') ?>" style="display:inline;margin-left:auto"
              onsubmit="return confirm('<?= __('plugins.uninstall_confirm') ?>')">
          <button class="btn btn-danger btn-sm"><?= __('plugins.uninstall') ?></button>
        </form>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<!-- ════════════════════════════════════════
     TAB: MARKETPLACE
════════════════════════════════════════ -->
<div id="pane-marketplace" style="display:none">

  <?php if (empty($registry)): ?>
  <div class="card" style="text-align:center;padding:56px 24px">
    <div style="font-size:2.8rem;margin-bottom:14px">🌐</div>
    <div style="font-weight:700;margin-bottom:8px;font-size:.9rem">Marketplace indisponível</div>
    <div style="color:var(--mt);font-size:.8rem;margin-bottom:20px">
      Não foi possível carregar o registry oficial.<br>
      Verifique sua conexão ou <a href="https://github.com/marcossancal/FlexCore-plugins" target="_blank" style="color:var(--ac)">acesse o repositório diretamente</a>.
    </div>
    <button class="btn btn-ghost btn-sm" onclick="location.reload()">↻ Tentar novamente</button>
  </div>

  <?php else: ?>

  <!-- Barra de busca -->
  <div style="margin-bottom:18px">
    <input type="text" id="mkt-search" placeholder="🔍  Buscar plugins..." oninput="filterMarketplace(this.value)"
      style="width:100%;max-width:360px;background:var(--bg);border:1px solid var(--bd2);border-radius:var(--r2);
             color:var(--tx);padding:8px 12px;font-family:var(--font);font-size:.82rem;outline:none;transition:all var(--tr)"
      onfocus="this.style.borderColor='var(--ac)'" onblur="this.style.borderColor='var(--bd2)'">
  </div>

  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px" id="mkt-grid">
    <?php foreach ($registry as $rp):
      $isInstalled = in_array($rp['id'], $installedIds);
      $tags = $rp['tags'] ?? [];
    ?>
    <div class="card mkt-card" style="margin:0;display:flex;flex-direction:column"
         data-name="<?= h(strtolower($rp['name'] ?? '')) ?>"
         data-tags="<?= h(strtolower(implode(' ', $tags))) ?>">

      <!-- Topo -->
      <div style="display:flex;align-items:flex-start;gap:12px;margin-bottom:10px">
        <span style="font-size:1.8rem">🧩</span>
        <div style="flex:1;min-width:0">
          <div style="font-weight:700;font-size:.93rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= h($rp['name'] ?? '') ?></div>
          <div style="font-size:.72rem;color:var(--mt)">
            v<?= h($rp['version'] ?? '?') ?>
            <?php if (!empty($rp['author'])): ?> · <?= h($rp['author']) ?><?php endif; ?>
          </div>
        </div>
        <?php if ($isInstalled): ?>
        <span style="background:var(--gn-bg);color:var(--gn);border:1px solid color-mix(in srgb,var(--gn) 25%,transparent);
                     border-radius:20px;padding:2px 9px;font-size:.67rem;font-weight:700;white-space:nowrap">✓ Instalado</span>
        <?php endif; ?>
      </div>

      <!-- Descrição -->
      <p style="color:var(--mt);font-size:.82rem;line-height:1.6;flex:1;margin-bottom:12px"><?= h($rp['description'] ?? '') ?></p>

      <!-- Tags -->
      <?php if (!empty($tags)): ?>
      <div style="display:flex;flex-wrap:wrap;gap:4px;margin-bottom:12px">
        <?php foreach ($tags as $tag): ?>
        <span style="background:var(--sf2);color:var(--mt2);border-radius:4px;padding:2px 7px;font-size:.68rem;font-weight:600"><?= h($tag) ?></span>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <!-- Info rodapé -->
      <div style="display:flex;gap:10px;font-size:.72rem;color:var(--mt2);margin-bottom:12px">
        <?php if (!empty($rp['min_flexcore'])): ?>
        <span>⚙️ FlexCore ≥ <?= h($rp['min_flexcore']) ?></span>
        <?php endif; ?>
        <?php if (!empty($rp['license'])): ?>
        <span>📄 <?= h($rp['license']) ?></span>
        <?php endif; ?>
      </div>

      <!-- Ações -->
      <div style="display:flex;gap:8px;padding-top:12px;border-top:1px solid var(--bd);flex-wrap:wrap">
        <?php if (!empty($rp['repository_url'])): ?>
        <a href="<?= h($rp['repository_url']) ?>" target="_blank" class="btn btn-ghost btn-sm">GitHub ↗</a>
        <?php endif; ?>

        <?php if ($isInstalled): ?>
        <button class="btn btn-ghost btn-sm" disabled style="opacity:.5;cursor:not-allowed;margin-left:auto">✓ Instalado</button>
        <?php else: ?>
        <form method="POST" action="<?= admin_url('/plugins/install-from-registry') ?>" style="margin-left:auto">
          <input type="hidden" name="plugin_id"    value="<?= h($rp['id']) ?>">
          <input type="hidden" name="download_url" value="<?= h($rp['download_url'] ?? '') ?>">
          <button type="submit" class="btn btn-primary btn-sm"
                  onclick="this.textContent='Instalando...';this.disabled=true;this.form.submit()">
            ⬇️ Instalar
          </button>
        </form>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Empty state da busca -->
  <div id="mkt-empty" style="display:none;text-align:center;padding:40px;color:var(--mt)">
    <div style="font-size:2rem;margin-bottom:8px">🔍</div>
    <div style="font-size:.85rem">Nenhum plugin encontrado para essa busca.</div>
  </div>

  <!-- Link para contribuir -->
  <div style="margin-top:24px;padding:14px 18px;background:var(--sf);border:1px solid var(--bd);border-radius:var(--r2);
              display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px">
    <div>
      <div style="font-size:.8rem;font-weight:700;margin-bottom:2px">Quer publicar seu plugin?</div>
      <div style="font-size:.75rem;color:var(--mt)">Abra um PR no repositório oficial do marketplace.</div>
    </div>
    <a href="https://github.com/marcossancal/FlexCore-plugins" target="_blank" class="btn btn-ghost btn-sm">Ver repositório ↗</a>
  </div>

  <?php endif; ?>
</div>

<!-- ════════════════════════════════════════
     MODAL: Upload ZIP
════════════════════════════════════════ -->
<div id="up-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.65);z-index:300;align-items:center;justify-content:center">
  <div style="backdrop-filter:blur(7px);background:var(--sf);border:1px solid var(--bd2);border-radius:var(--r);padding:28px;width:100%;max-width:440px;margin:20px">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
      <div style="font-family:var(--fd);font-weight:700">⬆️ Instalar Plugin via ZIP</div>
      <button onclick="document.getElementById('up-modal').style.display='none'"
              style="background:none;border:none;color:var(--mt);cursor:pointer;font-size:1.2rem">✕</button>
    </div>
    <p style="font-size:.78rem;color:var(--mt);margin-bottom:18px;line-height:1.6">
      O ZIP deve conter um <code style="background:var(--sf2);padding:1px 5px;border-radius:3px">plugin.json</code>
      e um <code style="background:var(--sf2);padding:1px 5px;border-radius:3px">Plugin.php</code> na raiz.
    </p>
    <form method="POST" action="<?= admin_url('/plugins/install') ?>" enctype="multipart/form-data">
      <div class="field">
        <label>Arquivo do plugin (.zip) *</label>
        <input type="file" name="plugin_zip" accept=".zip" required
               style="background:var(--sf2);border:1px solid var(--bd2);border-radius:var(--r2);color:var(--tx);padding:9px 12px">
      </div>
      <div class="form-actions">
        <button type="button" onclick="document.getElementById('up-modal').style.display='none'" class="btn btn-ghost"><?= __('general.cancel') ?></button>
        <button type="submit" class="btn btn-primary" onclick="this.textContent='Instalando...';this.disabled=true;this.form.submit()">
          ⬇️ Instalar
        </button>
      </div>
    </form>
  </div>
</div>

<!-- ════════════════════════════════════════
     MODAL: Settings
════════════════════════════════════════ -->
<div id="cfg-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.65);z-index:300;align-items:center;justify-content:center">
  <div style="background:var(--sf);border:1px solid var(--bd2);border-radius:var(--r);padding:28px;width:100%;max-width:480px;margin:20px">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
      <div style="font-family:var(--fd);font-weight:700" id="cfg-title"><?= __('plugins.configure') ?></div>
      <button onclick="document.getElementById('cfg-modal').style.display='none'"
              style="background:none;border:none;color:var(--mt);cursor:pointer;font-size:1.2rem">✕</button>
    </div>
    <form method="POST" id="cfg-form">
      <div id="cfg-fields"></div>
      <div class="form-actions">
        <button type="button" onclick="document.getElementById('cfg-modal').style.display='none'" class="btn btn-ghost"><?= __('general.cancel') ?></button>
        <button type="submit" class="btn btn-primary">💾 <?= __('general.save') ?></button>
      </div>
    </form>
  </div>
</div>

<script>
/* ── Tabs ── */
function switchTab(tab) {
  const panes = ['instalados', 'marketplace'];
  panes.forEach(p => {
    document.getElementById('pane-' + p).style.display = p === tab ? '' : 'none';
    const btn = document.getElementById('tab-' + p);
    if (p === tab) {
      btn.style.background = 'var(--ac)';
      btn.style.color      = 'var(--ac-fg)';
    } else {
      btn.style.background = 'transparent';
      btn.style.color      = 'var(--mt)';
    }
  });
  if (tab === 'marketplace') {
    const s = document.getElementById('mkt-search');
    if (s) s.focus();
  }
}

/* ── Busca no marketplace ── */
function filterMarketplace(q) {
  q = q.toLowerCase().trim();
  const cards = document.querySelectorAll('.mkt-card');
  let visible = 0;
  cards.forEach(card => {
    const match = !q
      || card.dataset.name.includes(q)
      || card.dataset.tags.includes(q);
    card.style.display = match ? '' : 'none';
    if (match) visible++;
  });
  const empty = document.getElementById('mkt-empty');
  if (empty) empty.style.display = visible === 0 ? '' : 'none';
}

/* ── Modal upload ── */
function openUploadModal() {
  document.getElementById('up-modal').style.display = 'flex';
}

/* ── Modal settings ── */
function openSettings(p) {
  const manifest = JSON.parse(p.manifest || '{}');
  const settings = JSON.parse(p.settings || '{}');
  const schema   = manifest.settings || [];
  document.getElementById('cfg-title').textContent = p.name + ' — <?= __('plugins.configure') ?>';
  document.getElementById('cfg-form').action = '<?= admin_url('/plugins/') ?>' + p.plugin_id + '/settings';
  const inp = s => s.type === 'textarea'
    ? `<textarea name="settings[${s.key}]" rows="3">${settings[s.key] || ''}</textarea>`
    : `<input type="${s.type || 'text'}" name="settings[${s.key}]" value="${settings[s.key] || ''}" ${s.required ? 'required' : ''}>`;
  document.getElementById('cfg-fields').innerHTML = schema.length
    ? schema.map(s => `<div class="field"><label>${s.label}${s.required ? ' *' : ''}</label>${inp(s)}${s.hint ? `<div class="hint">${s.hint}</div>` : ''}</div>`).join('')
    : '<p style="color:var(--mt);font-size:.82rem">Este plugin não tem configurações.</p>';
  document.getElementById('cfg-modal').style.display = 'flex';
}

/* ── Fecha modais com ESC ── */
document.addEventListener('keydown', e => {
  if (e.key !== 'Escape') return;
  ['up-modal', 'cfg-modal'].forEach(id => {
    document.getElementById(id).style.display = 'none';
  });
});
</script>

<?php partial('layout/footer'); ?>