<?php partial('layout/header', [
  'page_title'  => __('fields.page_title').' — '.$entity['name'],
  'active_page' => 'entities',
  'breadcrumbs' => [
    ['label' => __('fields.breadcrumb_entities'), 'url' => '/entities'],
    ['label' => $entity['icon'].' '.$entity['name'], 'url' => '/entities/'.$entity['id'].'/edit'],
    ['label' => __('fields.title')],
  ],
]) ?>

<div class="sec-head">
  <div>
    <div class="sec-title"><?= h($entity['icon']) ?> <?= h($entity['name']) ?></div>
    <div class="sec-sub"><?= __('fields.api_responses') ?></div>
  </div>
  <div class="sec-actions">
    <a href="<?= url('/e/' . h($entity['slug'])) ?>" class="btn btn-ghost btn-sm">👁 <?= __('records.view') ?></a>
    <a href="<?= url('/entities/' . $entity['id'] . '/edit') ?>" class="btn btn-ghost btn-sm">✏️ <?= __('general.edit') ?></a>
  </div>
</div>

<?php $activeTab = $_GET['tab'] ?? 'fields'; ?>
<div style="display:flex;gap:0;border-bottom:1px solid var(--bd);margin-bottom:24px">
  <a href="<?= url('/entities/'.$entity['id'].'/fields') ?>?tab=fields"
     style="padding:10px 20px;font-size:.875rem;font-weight:600;text-decoration:none;border-bottom:2px solid <?= $activeTab==='fields'?'var(--ac)':'transparent' ?>;color:<?= $activeTab==='fields'?'var(--ac)':'var(--mt)' ?>;transition:all .15s">
    <?= __('fields.tab_fields') ?> <span style="background:var(--sf2);border-radius:10px;padding:1px 7px;font-size:.72rem;margin-left:4px"><?= count($fields) ?></span>
  </a>
  <a href="<?= url('/entities/'.$entity['id'].'/fields') ?>?tab=api"
     style="padding:10px 20px;font-size:.875rem;font-weight:600;text-decoration:none;border-bottom:2px solid <?= $activeTab==='api'?'var(--ac)':'transparent' ?>;color:<?= $activeTab==='api'?'var(--ac)':'var(--mt)' ?>;transition:all .15s">
    <?= __('fields.tab_api') ?>
  </a>
</div>

<?php if ($activeTab === 'api'): ?>
<?php include BASE . '/app/views/entities/api_responses.php'; ?>
<?php else: ?>

<div class="row2" style="gap:18px;align-items:start">
  <div style="flex:2">
    <div class="card">
      <div class="card-title"><?= __('fields.title') ?> (<?= count($fields) ?>)</div>

      <?php if (empty($fields)): ?>
      <div style="text-align:center;padding:32px;color:var(--mt)">
        <div style="font-size:2rem;margin-bottom:10px">🔧</div>
        <?= __('fields.no_fields') ?>
      </div>
      <?php else: ?>
      <div id="fields-list">
        <?php foreach ($fields as $f): ?>
        <div class="field-row" data-id="<?= $f['id'] ?>" style="display:flex;align-items:center;gap:12px;padding:12px;background:var(--sf2);border:1px solid var(--bd);border-radius:var(--r2);margin-bottom:8px">
          <span class="drag-handle" title="<?= __('fields.drag_to_reorder') ?>">⠿</span>
          <span style="font-size:1.1rem;width:24px;text-align:center"><?= fieldTypeIcon($f['field_type']) ?></span>
          <div style="flex:1;min-width:0">
            <div style="font-weight:600;color:var(--tx)"><?= h($f['name']) ?>
              <?php if ($f['required']): ?><span style="color:var(--rd);font-size:.8rem">*</span><?php endif; ?>
            </div>
            <div style="font-size:.74rem;color:var(--mt)">
              <code style="color:var(--ac)"><?= h($f['slug']) ?></code>
              · <?= h(__('fields.types.'.$f['field_type'])) ?>
              <?php if ($f['field_type']==='relation' && $f['relation_name']): ?>
              → <?= h($f['relation_name']) ?>
              <?php endif; ?>
              <?php if (in_array($f['field_type'],['select','multiselect']) && $f['options_json']): ?>
              · <?= count(json_decode($f['options_json'],true)) ?> opções
              <?php endif; ?>
              <?php if ($f['field_type']==='formula' && $f['options_json']): ?>
              <?php $fm = json_decode($f['options_json'],true); ?>
              · <code style="color:var(--ac2);font-size:.7rem"><?= h(mb_substr($fm['expression']??'',0,40)) ?></code>
              [<?= h($fm['output']??'number') ?>]
              <?php endif; ?>
            </div>
          </div>
          <div style="display:flex;gap:6px;align-items:center">
            <?= $f['show_in_list'] ? '<span class="badge bc" style="font-size:.65rem">lista</span>' : '' ?>
            <button type="button" onclick="editarCampo(<?= h(json_encode($f)) ?>)" class="btn btn-ghost btn-xs">✏️</button>
            <form method="POST" action="<?= url('entities/'. $entity['id'].'/fields/'. $f['id']. '/delete')?>" style="display:inline"
                  onsubmit="return confirm('<?= __('fields.delete_confirm') ?>')">
              <button type="submit" class="btn btn-danger btn-xs">✕</button>
            </form>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>

    <div class="card" style="background:rgba(0,212,255,.03);border-color:rgba(0,212,255,.1)">
      <div class="card-title" style="font-size:.82rem">💡 <?= __('fields.available_types') ?></div>
      <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:8px">
        <?php
        $types = array_keys(allFieldTypes());
        foreach ($types as $t): ?>
        <div style="display:flex;align-items:center;gap:8px;font-size:.78rem;color:var(--mt2)">
          <span><?= fieldTypeIcon($t) ?></span>
          <span><?= h(__('fields.types.'.$t)) ?></span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <div>
    <div class="card" id="field-form-card" style="position:sticky;top:70px">
      <div class="card-title" id="field-form-title">➕ <?= __('fields.new') ?></div>

      <form method="POST" id="field-form" action="<?= url('entities/'. $entity['id'].'/fields/create')?>">
        <input type="hidden" name="field_id" id="fld-id" value="">

        <div class="field">
          <label><?= __('fields.name') ?> *</label>
          <input type="text" name="name" id="fld-name" required placeholder="Ex: Nome Completo" oninput="atualizarFldSlug(this.value)">
        </div>

        <div class="field">
          <label><?= __('fields.slug') ?></label>
          <input type="text" name="slug" id="fld-slug" required pattern="[a-z0-9_]+" placeholder="nome_completo" style="font-family:monospace">
        </div>

        <div class="field">
          <label><?= __('fields.type') ?> *</label>
          <select name="field_type" id="fld-type" onchange="onTipoChange(this.value)" required>
            <option value="">— <?= __('general.none') ?> —</option>
            <optgroup label="Texto e comunicação">
              <option value="text">🔤 <?= __('fields.types.text') ?></option>
              <option value="textarea">📝 <?= __('fields.types.textarea') ?></option>
              <option value="richtext">✍️ <?= __('fields.types.richtext') ?></option>
              <option value="email">✉️ <?= __('fields.types.email') ?></option>
              <option value="url">🔗 <?= __('fields.types.url') ?></option>
              <option value="phone">📞 <?= __('fields.types.phone') ?></option>
              <option value="password">🔒 <?= __('fields.types.password') ?></option>
            </optgroup>
            <optgroup label="Números e valores">
              <option value="number">🔢 <?= __('fields.types.number') ?></option>
              <option value="currency">💰 <?= __('fields.types.currency') ?></option>
              <option value="percent">% <?= __('fields.types.percent') ?></option>
              <option value="rating">⭐ <?= __('fields.types.rating') ?></option>
              <option value="progress">🎚 <?= __('fields.types.progress') ?></option>
              <option value="duration">⏳ <?= __('fields.types.duration') ?></option>
            </optgroup>
            <optgroup label="Data e tempo">
              <option value="date">📅 <?= __('fields.types.date') ?></option>
              <option value="datetime">🕐 <?= __('fields.types.datetime') ?></option>
              <option value="time">⏱ <?= __('fields.types.time') ?></option>
              <option value="daterange">📆 <?= __('fields.types.daterange') ?></option>
            </optgroup>
            <optgroup label="Seleção e listas">
              <option value="select">▼ <?= __('fields.types.select') ?></option>
              <option value="multiselect">☑️ <?= __('fields.types.multiselect') ?></option>
              <option value="checkbox">✅ <?= __('fields.types.checkbox') ?></option>
              <option value="tags">🏷 <?= __('fields.types.tags') ?></option>
              <option value="user">👤 <?= __('fields.types.user') ?></option>
              <option value="color">🎨 <?= __('fields.types.color') ?></option>
            </optgroup>
            <optgroup label="Relacionamentos">
              <option value="relation">🔀 <?= __('fields.types.relation') ?></option>
            </optgroup>
            <optgroup label="Dados especiais">
              <option value="uuid">🆔 <?= __('fields.types.uuid') ?></option>
              <option value="json">🔣 <?= __('fields.types.json') ?></option>
              <option value="ip">📡 <?= __('fields.types.ip') ?></option>
            </optgroup>
            <optgroup label="Mídia e arquivos">
              <option value="image">🖼 <?= __('fields.types.image') ?></option>
              <option value="file">📎 <?= __('fields.types.file') ?></option>
            </optgroup>
            <optgroup label="Campos calculados">
              <option value="formula">∑ <?= __('fields.types.formula') ?></option>
            </optgroup>
            <?php
            // -- Hook: field.types (select options) --
            // Exibe os tipos registrados por plugins no <select> de tipo de campo.
            $pluginTypes = array_diff_key(allFieldTypes(), array_flip([
                'text','textarea','richtext','email','url','phone','password',
                'number','currency','percent','rating','progress','duration',
                'date','datetime','time','daterange',
                'select','multiselect','checkbox','tags','user','color',
                'relation','uuid','json','ip','image','file','formula',
            ]));
            if (!empty($pluginTypes)): ?>
            <optgroup label="Plugins">
              <?php foreach ($pluginTypes as $typeKey => $typeDef): ?>
              <option value="<?= h($typeKey) ?>">
                <?= h($typeDef['icon'] ?? '') ?> <?= h(__('fields.types.' . $typeKey)) ?>
              </option>
              <?php endforeach; ?>
            </optgroup>
            <?php endif; ?>
          </select>
        </div>

        <div id="fld-options-wrap" style="display:none">
          <div class="field">
            <label><?= __('fields.options') ?></label>
            <textarea name="options" id="fld-options" style="min-height:100px;font-family:monospace;font-size:.82rem" placeholder="Opção 1&#10;Opção 2&#10;Opção 3"></textarea>
            <div class="hint"><?= __('fields.options_hint') ?></div>
          </div>
        </div>

        <div id="fld-relation-wrap" style="display:none">
          <div class="field">
            <label><?= __('fields.relation_to') ?></label>
            <select name="relation_entity_id" id="fld-relation">
              <option value="">— <?= __('general.none') ?> —</option>
              <?php foreach ($all_entities as $ae): ?>
              <?php if ($ae['id'] !== $entity['id']): ?>
              <option value="<?= $ae['id'] ?>"><?= h($ae['icon'].' '.$ae['name']) ?></option>
              <?php endif; ?>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div id="fld-maxsize-wrap" style="display:none">
          <div class="field">
            <label>Tamanho máximo (MB)</label>
            <input type="number" name="max_size_mb" id="fld-maxsize" value="5" min="1" max="15" step="1">
            <div class="hint" style="font-size:.72rem;color:var(--mt);margin-top:4px">Imagem: máx. recomendado 2MB. Arquivo: máx. recomendado 5MB. Limite do MEDIUMTEXT: ~16MB.</div>
          </div>
        </div>

        <div id="fld-formula-wrap" style="display:none">
          <div class="field">
            <label>∑ Expressão da Fórmula</label>
            <textarea name="formula_expression" id="fld-formula-expr"
                      style="min-height:80px;font-family:monospace;font-size:.83rem"
                      placeholder="Ex: {preco} * {quantidade}"></textarea>
            <div class="hint" style="font-size:.71rem;color:var(--mt);margin-top:6px;line-height:1.5">
              Use <code>{slug_do_campo}</code> para referenciar outros campos.<br>
              Funções: <code>SUM(a,b)</code> <code>AVG(a,b)</code> <code>MIN(a,b)</code> <code>MAX(a,b)</code>
              <code>ROUND(v,2)</code> <code>ABS(v)</code> <code>IF(cond, sim, nao)</code><br>
              Operadores: <code>+ - * / ( )</code><br>
              Exemplos:<br>
              • Margem: <code>({preco} - {custo}) / {preco} * 100</code><br>
              • Média: <code>AVG(nota1, nota2, nota3)</code><br>
              • Desconto condicional: <code>IF({quantidade} >= 10, {preco} * 0.9, {preco})</code>
            </div>
          </div>
          <div class="field">
            <label>Tipo de saída</label>
            <select name="formula_output" id="fld-formula-output">
              <option value="number">🔢 Número</option>
              <option value="currency">💰 Moeda (R$)</option>
              <option value="percent">% Percentual</option>
              <option value="text">🔤 Texto</option>
            </select>
            <div class="hint" style="font-size:.71rem;color:var(--mt);margin-top:4px">Define como o resultado será formatado na exibição.</div>
          </div>
        </div>

        <?php
        // -- Hook: field.render_config --
        // Plugins injetam HTML de configuracao para seus tipos de campo.
        // O HTML e inserido abaixo dos paineis nativos (select, formula, etc.).
        // O plugin deve renderizar apenas quando field_type bater com o seu tipo.
        //
        // Exemplo:
        //   Hooks::filter('field.render_config', function(string $html, array $ctx): string {
        //       ob_start();
        //       include __DIR__ . '/views/_meu_config.php';
        //       return $html . ob_get_clean();
        //   });
        echo \FlexCore\Core\Hooks\Hooks::applyFilter('field.render_config', '', [
            'entity'   => $entity,
            'field'    => null, // preenchido via JS ao editar; null ao criar
            'entities' => $all_entities,
        ]);
        ?>

        <div style="display:flex;gap:14px;margin-bottom:16px;flex-wrap:wrap">
          <label style="display:flex;align-items:center;gap:7px;cursor:pointer;font-size:.84rem">
            <input type="checkbox" name="required" id="fld-required" value="1" style="accent-color:var(--ac);width:auto">
            <?= __('fields.required') ?>
          </label>
          <label style="display:flex;align-items:center;gap:7px;cursor:pointer;font-size:.84rem">
            <input type="checkbox" name="show_in_list" id="fld-show-list" value="1" checked style="accent-color:var(--ac);width:auto">
            <?= __('fields.show_in_list') ?>
          </label>
        </div>

        <div class="field">
          <label><?= __('fields.position') ?></label>
          <input type="number" name="position" id="fld-position" value="<?= count($fields) ?>" min="0">
        </div>

        <div class="form-actions" style="margin-top:12px">
          <button type="button" onclick="resetFieldForm()" id="btn-fld-cancel" class="btn btn-ghost" style="display:none"><?= __('general.cancel') ?></button>
          <button type="submit" id="btn-fld-submit" class="btn btn-primary"> <?= __('fields.add_field') ?></button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function slugifyField(s) {
  return s.toLowerCase()
    .replace(/[àáâãä]/g,'a').replace(/[èéêë]/g,'e').replace(/[ìíî]/g,'i')
    .replace(/[òóôõö]/g,'o').replace(/[ùúûü]/g,'u').replace(/[ç]/g,'c')
    .replace(/[^a-z0-9]+/g,'_').replace(/^_+|_+$/g,'');
}
function atualizarFldSlug(v) {
  if (!document.getElementById('fld-id').value) {
    document.getElementById('fld-slug').value = slugifyField(v);
  }
}
function onTipoChange(t) {
  document.getElementById('fld-options-wrap').style.display  = ['select','multiselect'].includes(t) ? 'block' : 'none';
  document.getElementById('fld-relation-wrap').style.display = t === 'relation' ? 'block' : 'none';
  document.getElementById('fld-maxsize-wrap').style.display  = ['image','file'].includes(t) ? 'block' : 'none';
  document.getElementById('fld-formula-wrap').style.display  = t === 'formula' ? 'block' : 'none';
  // Notifica plugins registrados via field.render_config.
  // Cada plugin escuta este evento e exibe/oculta seu painel.
  document.dispatchEvent(new CustomEvent('flexcore:fieldTypeChange', { detail: { type: t } }));
}
function editarCampo(f) {
  document.getElementById('field-form-title').textContent = '✏️ <?= __('fields.edit') ?>';
  document.getElementById('field-form').action = '<?= url('/entities/' . $entity['id'] . '/fields/') ?>' + f.id + '/update';
  document.getElementById('fld-id').value       = f.id;
  document.getElementById('fld-name').value     = f.name;
  document.getElementById('fld-slug').value     = f.slug;
  document.getElementById('fld-type').value     = f.field_type;
  document.getElementById('fld-position').value = f.position;
  document.getElementById('fld-required').checked    = f.required == 1;
  document.getElementById('fld-show-list').checked   = f.show_in_list == 1;
  onTipoChange(f.field_type);
  if (f.options_json) {
    var opts = JSON.parse(f.options_json);
    if (f.field_type === 'formula') {
      document.getElementById('fld-formula-expr').value   = opts.expression || '';
      document.getElementById('fld-formula-output').value = opts.output     || 'number';
    } else if (Array.isArray(opts)) {
      document.getElementById('fld-options').value = opts.join('\n');
    }
  }
  if (f.relation_entity_id) document.getElementById('fld-relation').value = f.relation_entity_id;
  document.getElementById('btn-fld-submit').textContent = '💾 <?= __('general.save') ?>';
  document.getElementById('btn-fld-cancel').style.display = '';
  document.getElementById('field-form-card').scrollIntoView({behavior:'smooth'});
}
function resetFieldForm() {
  document.getElementById('field-form-title').textContent = '➕ <?= __('fields.new') ?>';
  document.getElementById('field-form').action = '<?= url('/entities/' . $entity['id'] . '/fields/create') ?>';
  document.getElementById('field-form').reset();
  document.getElementById('fld-id').value = '';
  document.getElementById('fld-options-wrap').style.display = 'none';
  document.getElementById('fld-relation-wrap').style.display = 'none';
  document.getElementById('fld-formula-wrap').style.display = 'none';
  document.getElementById('btn-fld-submit').textContent = '+ <?= __('fields.add_field') ?>';
  document.getElementById('btn-fld-cancel').style.display = 'none';
}
</script>

<?php endif; ?>
<?php partial('layout/footer') ?>
