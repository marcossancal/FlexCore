<?php
$isEdit = isset($record);
$title  = $isEdit ? __('records.page_title_edit') : __('records.page_title_new');
partial('layout/header', [
  'page_title'    => $title.' '.$entity['name'],
  'active_entity' => $entity['slug'],
  'breadcrumbs'   => [
    ['label' => $entity['icon'].' '.$entity['name'], 'url' => '/e/'.$entity['slug']],
    ['label' => $title],
  ],
])
?>

<style>
  /* rating stars */
  .star-row { display:flex; gap:4px; }
  .star-row input[type=radio] { display:none; }
  .star-row label { font-size:1.6rem; cursor:pointer; color:var(--mt); transition:color .1s; }
  .star-row input[type=radio]:checked ~ label,
  .star-row label:hover,
  .star-row label:hover ~ label { color: #f59e0b; }
  .star-row { flex-direction: row-reverse; }
  .star-row input[type=radio]:checked ~ label { color:#f59e0b; }

  /* progress slider */
  .progress-wrap { display:flex; align-items:center; gap:12px; }
  .progress-wrap input[type=range] { flex:1; }
  .progress-val { min-width:36px; text-align:right; font-size:.85rem; color:var(--mt2); }

  /* color picker */
  .color-wrap { display:flex; align-items:center; gap:10px; }
  .color-wrap input[type=color] { width:44px; height:36px; padding:2px; border:1px solid var(--bd2); border-radius:var(--r2); background:none; cursor:pointer; }
  .color-wrap .color-hex { font-family:monospace; font-size:.85rem; width:90px; }

  /* image/file upload */
  .upload-area { border:2px dashed var(--bd2); border-radius:var(--r); padding:20px; text-align:center; cursor:pointer; transition:border-color .2s; position:relative; }
  .upload-area:hover { border-color:var(--ac); }
  .upload-area input[type=file] { position:absolute; inset:0; opacity:0; cursor:pointer; width:100%; height:100%; }
  .upload-preview { max-width:100%; max-height:200px; border-radius:6px; margin-top:10px; object-fit:contain; }
  .upload-file-info { font-size:.78rem; color:var(--mt); margin-top:8px; }

  /* tags input */
  .tags-wrap { background:var(--sf2); border:1px solid var(--bd2); border-radius:var(--r2); padding:6px 8px; display:flex; flex-wrap:wrap; gap:6px; min-height:40px; cursor:text; }
  .tags-wrap .tag-chip { display:inline-flex; align-items:center; gap:4px; background:var(--ac); color:#000; font-size:.75rem; font-weight:700; border-radius:20px; padding:2px 9px; }
  .tags-wrap .tag-chip button { background:none; border:none; cursor:pointer; font-size:.9rem; color:#000; padding:0; line-height:1; }
  .tags-input { border:none; background:none; outline:none; font-size:.85rem; color:var(--tx); flex:1; min-width:80px; font-family:var(--fb); }

  /* richtext toolbar */
  .rt-toolbar { display:flex; gap:4px; background:var(--sf2); border:1px solid var(--bd2); border-radius:var(--r2) var(--r2) 0 0; padding:6px 8px; flex-wrap:wrap; }
  .rt-btn { background:none; border:1px solid transparent; border-radius:4px; cursor:pointer; font-size:.82rem; color:var(--mt2); padding:3px 7px; font-family:var(--fb); }
  .rt-btn:hover { background:var(--bd); color:var(--tx); }
  .rt-area { border:1px solid var(--bd2); border-top:none; border-radius:0 0 var(--r2) var(--r2); padding:10px; min-height:120px; outline:none; font-size:.88rem; line-height:1.6; color:var(--tx); }
  .rt-area:empty:before { content:attr(data-placeholder); color:var(--mt); }

  /* duration */
  .dur-wrap { display:flex; align-items:center; gap:6px; }
  .dur-wrap input[type=number] { width:70px; }
  .dur-label { font-size:.8rem; color:var(--mt); }

  /* daterange */
  .daterange-wrap { display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
  .daterange-wrap input[type=date] { flex:1; min-width:130px; }
  .daterange-sep { color:var(--mt); font-size:.85rem; }
</style>

<div class="sec-head">
  <div class="sec-title">
    <?= $isEdit ? '✏️ '.__('records.edit') : '+' ?> <?= h($entity['name']) ?>
  </div>
  <a href="<?= url('/e/' . h($entity['slug'])) ?>" class="btn btn-ghost btn-sm">← <?= __('general.back') ?></a>
</div>

<form method="POST" id="record-form"
      action="<?= url($isEdit ? '/e/'.$entity['slug'].'/'.$record['id'].'/update' : '/e/'.$entity['slug'].'/create'); ?>"
      enctype="multipart/form-data">

  <div class="row2" style="gap:18px;align-items:start">
    <div style="flex:2">
      <div class="card">
        <div class="card-title"><?= __('records.title') ?></div>

        <?php if (empty($fields)): ?>
        <p style="color:var(--mt)"><?= __('records.no_fields') ?> <a href="<?= url('/entities/' . $entity['id'] . '/fields') ?>" style="color:var(--ac)"><?= __('records.configure_first') ?></a></p>
        <?php else: ?>
        <?php foreach ($fields as $f):
          $currentVal = $record['values'][$f['id']] ?? null;
          $name = 'field_'.$f['id'];
          $required = $f['required'] ? 'required' : '';
          $t = $f['field_type'];
        ?>
        <div class="field">
          <label>
            <?= h($f['name']) ?>
            <?php if ($f['required']): ?><span style="color:var(--rd)">*</span><?php endif; ?>
            <span style="color:var(--mt);font-weight:400;font-size:.7rem;margin-left:6px"><?= fieldTypeIcon($t) ?> <?= h(__('fields.types.'.$t)) ?></span>
          </label>

          <?php
          // ─────────────────────────────────────────────────────────────
          // Main SWITCH renderization by field type
          // ─────────────────────────────────────────────────────────────
          switch ($t):

            // ── Texto simples ───────────────────────────────────────
            case 'text':
            case 'email':
            case 'url':
            case 'phone':
            case 'ip':
              $itype = ['email'=>'email','url'=>'url','phone'=>'tel'][$t] ?? 'text';
              $ph    = ['url'=>'https://','phone'=>'(11) 99999-9999','ip'=>'192.168.0.1'][$t] ?? '';
              ?>
              <input type="<?= $itype ?>" name="<?= h($name) ?>" value="<?= h($currentVal ?? '') ?>" placeholder="<?= $ph ?>" <?= $required ?>>
              <?php break;

            // ── Textarea ─────────────────────────────────────────────
            case 'textarea': ?>
              <textarea name="<?= h($name) ?>" style="min-height:100px" <?= $required ?>><?= h($currentVal ?? '') ?></textarea>
              <?php break;

            // ── Rich text ────────────────────────────────────────────
            case 'richtext': ?>
              <div class="rt-toolbar" aria-label="Formatação">
                <button type="button" class="rt-btn" onclick="rtCmd('bold')"><b>N</b></button>
                <button type="button" class="rt-btn" onclick="rtCmd('italic')"><i>I</i></button>
                <button type="button" class="rt-btn" onclick="rtCmd('underline')"><u>S</u></button>
                <button type="button" class="rt-btn" onclick="rtCmd('insertUnorderedList')">• lista</button>
                <button type="button" class="rt-btn" onclick="rtCmd('insertOrderedList')">1. lista</button>
                <button type="button" class="rt-btn" onclick="rtCmd('formatBlock','h3')">H3</button>
                <button type="button" class="rt-btn" onclick="rtCmd('removeFormat')">✕ fmt</button>
              </div>
              <div id="rt-<?= $f['id'] ?>" class="rt-area" contenteditable="true"
                   data-placeholder="Digite o conteúdo com formatação…"
                   oninput="syncRichText(<?= $f['id'] ?>)"><?= $currentVal ?? '' ?></div>
              <input type="hidden" name="<?= h($name) ?>" id="rthidden-<?= $f['id'] ?>" value="<?= h($currentVal ?? '') ?>">
              <?php break;

            // ── Number ───────────────────────────────────────────────
            case 'number': ?>
              <input type="number" name="<?= h($name) ?>" value="<?= h($currentVal ?? '') ?>" step="any" <?= $required ?>>
              <?php break;

            // ── Currency ────────────────────────────────────────────────
            case 'currency': ?>
              <div style="position:relative">
                <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--mt);font-size:.85rem">R$</span>
                <input type="number" name="<?= h($name) ?>" value="<?= h($currentVal ?? '') ?>" step="0.01" min="0" style="padding-left:36px" <?= $required ?>>
              </div>
              <?php break;

            // ── Percentage ───────────────────────────────────────────
            case 'percent': ?>
              <div style="position:relative">
                <input type="number" name="<?= h($name) ?>" value="<?= h($currentVal ?? '') ?>" step="0.01" min="0" max="100" style="padding-right:32px" <?= $required ?>>
                <span style="position:absolute;right:12px;top:50%;transform:translateY(-50%);color:var(--mt);font-size:.85rem">%</span>
              </div>
              <?php break;

            // ── Rating (stars 1–5) ─────────────────────────────────
            case 'rating': ?>
              <div class="star-row" id="stars-<?= $f['id'] ?>">
                <?php for ($star = 5; $star >= 1; $star--): ?>
                <input type="radio" name="<?= h($name) ?>" id="star-<?= $f['id'] ?>-<?= $star ?>"
                       value="<?= $star ?>" <?= (int)$currentVal === $star ? 'checked' : '' ?>>
                <label for="star-<?= $f['id'] ?>-<?= $star ?>" title="<?= $star ?> estrela(s)">★</label>
                <?php endfor; ?>
              </div>
              <?php break;

            // ── Progress (0–100) ──────────────────────────────────────
            case 'progress': ?>
              <div class="progress-wrap">
                <input type="range" name="<?= h($name) ?>" min="0" max="100" step="1"
                       value="<?= h($currentVal ?? '0') ?>"
                       oninput="this.nextElementSibling.textContent = this.value + '%'">
                <span class="progress-val"><?= h($currentVal ?? '0') ?>%</span>
              </div>
              <?php break;

            // ── Duration (H:M:S) ──────────────────────────────────────
            case 'duration':
              $sec = (int)($currentVal ?? 0);
              $dh  = intdiv($sec, 3600);
              $dm  = intdiv($sec % 3600, 60);
              $ds  = $sec % 60;
              ?>
              <div class="dur-wrap">
                <input type="number" id="dur-h-<?= $f['id'] ?>" min="0" value="<?= $dh ?>" placeholder="0" oninput="syncDur(<?= $f['id'] ?>)">
                <span class="dur-label">h</span>
                <input type="number" id="dur-m-<?= $f['id'] ?>" min="0" max="59" value="<?= $dm ?>" placeholder="0" oninput="syncDur(<?= $f['id'] ?>)">
                <span class="dur-label">min</span>
                <input type="number" id="dur-s-<?= $f['id'] ?>" min="0" max="59" value="<?= $ds ?>" placeholder="0" oninput="syncDur(<?= $f['id'] ?>)">
                <span class="dur-label">seg</span>
              </div>
              <input type="hidden" name="<?= h($name) ?>" id="dur-val-<?= $f['id'] ?>"
                     value="<?= h($currentVal ?? '0') ?>">
              <?php break;

            // ── Date ──────────────────────────────────────────────────
            case 'date': ?>
              <input type="date" name="<?= h($name) ?>" value="<?= h($currentVal ? date('Y-m-d', strtotime($currentVal)) : '') ?>" <?= $required ?>>
              <?php break;

            // ── Date time ───────────────────────────────────────────
            case 'datetime': ?>
              <input type="datetime-local" name="<?= h($name) ?>" value="<?= h($currentVal ? date('Y-m-d\TH:i', strtotime($currentVal)) : '') ?>" <?= $required ?>>
              <?php break;

            // ── Hour ──────────────────────────────────────────────────
            case 'time': ?>
              <input type="time" name="<?= h($name) ?>" value="<?= h($currentVal ?? '') ?>" <?= $required ?>>
              <?php break;

            // ── Date range ────────────────────────────────────
            case 'daterange':
              $range  = $currentVal ? (json_decode($currentVal, true) ?: []) : [];
              $drStart = $range['start'] ?? '';
              $drEnd   = $range['end']   ?? '';
              ?>
              <div class="daterange-wrap">
                <input type="date" name="<?= h($name) ?>_start" value="<?= h($drStart) ?>" placeholder="Início">
                <span class="daterange-sep">→</span>
                <input type="date" name="<?= h($name) ?>_end" value="<?= h($drEnd) ?>" placeholder="Fim">
              </div>
              <?php break;

            // ── Select ───────────────────────────────────────────
            case 'select':
              $opts = json_decode($f['options_json'] ?? '[]', true) ?: [];
              ?>
              <select name="<?= h($name) ?>" <?= $required ?>>
                <option value="">— <?= __('general.none') ?> —</option>
                <?php foreach ($opts as $o): ?>
                <option value="<?= h($o) ?>" <?= $currentVal===$o?'selected':'' ?>><?= h($o) ?></option>
                <?php endforeach; ?>
              </select>
              <?php break;

            // ── Checkboxes ────────────────────────────────────────
            case 'multiselect':
              $opts     = json_decode($f['options_json'] ?? '[]', true) ?: [];
              $selected = $currentVal ? json_decode($currentVal, true) : [];
              ?>
              <div style="background:var(--sf2);border:1px solid var(--bd2);border-radius:var(--r2);padding:10px;display:flex;flex-wrap:wrap;gap:8px">
                <?php foreach ($opts as $o): ?>
                <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:.85rem;font-weight:400">
                  <input type="checkbox" name="<?= h($name) ?>[]" value="<?= h($o) ?>"
                         <?= in_array($o, $selected)?'checked':'' ?> style="accent-color:var(--ac);width:auto">
                  <?= h($o) ?>
                </label>
                <?php endforeach; ?>
              </div>
              <?php break;

            // ── Checkbox ──────────────────────────────────────────────
            case 'checkbox': ?>
              <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:.9rem;font-weight:400;margin-top:4px">
                <input type="checkbox" name="<?= h($name) ?>" value="1"
                       <?= $currentVal?'checked':'' ?> style="accent-color:var(--ac);width:auto;transform:scale(1.2)">
                <?= __('general.yes') ?>
              </label>
              <?php break;

            // ── Tags ──────────────────────────────────────────────────
            case 'tags':
              $tagsList = $currentVal ? (json_decode($currentVal, true) ?: []) : [];
              ?>
              <div class="tags-wrap" id="tags-wrap-<?= $f['id'] ?>" onclick="document.getElementById('tags-input-<?= $f['id'] ?>').focus()">
                <?php foreach ($tagsList as $tag): ?>
                <span class="tag-chip">
                  <?= h($tag) ?>
                  <button type="button" onclick="removeTag(<?= $f['id'] ?>, this)">×</button>
                </span>
                <?php endforeach; ?>
                <input class="tags-input" id="tags-input-<?= $f['id'] ?>" type="text"
                       placeholder="Digite e pressione Enter…"
                       onkeydown="tagsKeydown(event, <?= $f['id'] ?>)">
              </div>
              <input type="hidden" name="<?= h($name) ?>" id="tags-val-<?= $f['id'] ?>"
                     value="<?= h($currentVal ?? '[]') ?>">
              <?php break;

            // ── Color ───────────────────────────────────────────────────
            case 'color': ?>
              <div class="color-wrap">
                <input type="color" id="cpicker-<?= $f['id'] ?>"
                       value="<?= h($currentVal ?: '#00d4ff') ?>"
                       oninput="document.getElementById('chex-<?= $f['id'] ?>').value=this.value;document.getElementById('cval-<?= $f['id'] ?>').value=this.value">
                <input type="text" class="color-hex" id="chex-<?= $f['id'] ?>"
                       value="<?= h($currentVal ?? '#00d4ff') ?>" maxlength="7"
                       oninput="if(/^#[0-9a-fA-F]{6}$/.test(this.value)){document.getElementById('cpicker-<?= $f['id'] ?>').value=this.value;document.getElementById('cval-<?= $f['id'] ?>').value=this.value}">
                <input type="hidden" name="<?= h($name) ?>" id="cval-<?= $f['id'] ?>" value="<?= h($currentVal ?? '#00d4ff') ?>">
              </div>
              <?php break;

            // ── Password / sensitive data ─────────────────────────────────
            case 'password': ?>
              <input type="password" name="<?= h($name) ?>" value="" autocomplete="new-password"
                     placeholder="<?= $isEdit ? '(deixe em branco para manter)' : '' ?>">
              <?php if ($isEdit): ?>
              <input type="hidden" name="<?= h($name) ?>_keep" value="<?= h($currentVal ?? '') ?>">
              <?php endif; ?>
              <?php break;

            // ── UUID ─────────────────────────────────────────────────
            case 'uuid': ?>
              <div style="display:flex;align-items:center;gap:8px">
                <input type="text" name="<?= h($name) ?>" id="uuid-<?= $f['id'] ?>"
                       value="<?= h($currentVal ?? '') ?>" placeholder="Gerado automaticamente ao salvar"
                       style="font-family:monospace;font-size:.82rem" readonly>
              </div>
              <div class="hint" style="color:var(--mt);font-size:.72rem;margin-top:4px">UUID v4 gerado automaticamente na criação</div>
              <?php break;

            // ── JSON ──────────────────────────────────────────────────
            case 'json': ?>
              <textarea name="<?= h($name) ?>" style="min-height:80px;font-family:monospace;font-size:.8rem"
                        placeholder='{"chave": "valor"}' <?= $required ?>><?= h($currentVal ?? '') ?></textarea>
              <div class="hint" style="color:var(--mt);font-size:.72rem;margin-top:4px">Deve ser um JSON válido</div>
              <?php break;


            // ── Image (base64) ───────────────────────────────────────
            case 'image':
              $hasImg = $currentVal && str_starts_with($currentVal, 'data:image/');
              ?>
              <div class="upload-area" id="img-area-<?= $f['id'] ?>">
                <input type="file" accept="image/*" onchange="handleImageUpload(this, <?= $f['id'] ?>)">
                <?php if ($hasImg): ?>
                <img src="<?= h($currentVal) ?>" class="upload-preview" id="img-preview-<?= $f['id'] ?>">
                <?php else: ?>
                <div id="img-preview-<?= $f['id'] ?>">
                  <div style="font-size:1.5rem;margin-bottom:6px">🖼</div>
                  <div style="font-size:.82rem;color:var(--mt)">Clique ou arraste uma imagem (PNG, JPG, WEBP, GIF)</div>
                  <div style="font-size:.72rem;color:var(--mt);margin-top:4px">Máx. recomendado: 2MB</div>
                </div>
                <?php endif; ?>
              </div>
              <input type="hidden" name="<?= h($name) ?>_file_data" id="imgdata-<?= $f['id'] ?>" value="">
              <input type="hidden" name="<?= h($name) ?>_keep"      id="imgkeep-<?= $f['id'] ?>" value="<?= h($currentVal ?? '') ?>">
              <?php if ($hasImg): ?>
              <button type="button" class="btn btn-ghost btn-xs" style="margin-top:6px"
                      onclick="clearImage(<?= $f['id'] ?>)">✕ Remover imagem</button>
              <?php endif; ?>
              <?php break;

            // ── File (base64) ──────────────────────────────────────
            case 'file':
              $hasFile = $currentVal && str_starts_with($currentVal, 'data:');
              // Tenta recuperar nome do arquivo do options_json
              $fileMeta = !empty($f['options_json']) ? (json_decode($f['options_json'], true) ?: []) : [];
              $fileName = $fileMeta['filename'] ?? 'arquivo';
              ?>
              <?php if ($hasFile): ?>
              <div style="background:var(--sf2);border:1px solid var(--bd2);border-radius:var(--r2);padding:10px;display:flex;align-items:center;gap:10px;margin-bottom:8px">
                <span style="font-size:1.2rem">📎</span>
                <a href="<?= h($currentVal) ?>" download="<?= h($fileName) ?>" style="color:var(--ac);font-size:.85rem"><?= h($fileName) ?></a>
                <button type="button" class="btn btn-ghost btn-xs" onclick="clearFile(<?= $f['id'] ?>)">✕</button>
              </div>
              <?php endif; ?>
              <div class="upload-area" id="file-area-<?= $f['id'] ?>">
                <input type="file" onchange="handleFileUpload(this, <?= $f['id'] ?>)">
                <div>
                  <div style="font-size:1.2rem;margin-bottom:4px">📎</div>
                  <div style="font-size:.82rem;color:var(--mt)" id="file-label-<?= $f['id'] ?>">Clique ou arraste um arquivo</div>
                  <div style="font-size:.72rem;color:var(--mt);margin-top:4px">Máx. recomendado: 5MB</div>
                </div>
              </div>
              <input type="hidden" name="<?= h($name) ?>_file_data" id="filedata-<?= $f['id'] ?>" value="">
              <input type="hidden" name="<?= h($name) ?>_keep"      id="filekeep-<?= $f['id'] ?>" value="<?= h($currentVal ?? '') ?>">
              <?php break;

            // ── Relation between another entity ────────────────────────────
            case 'relation': ?>
              <?php if ($f['relation_entity_id'] && !empty($f['relation_records'])): ?>
              <select name="<?= h($name) ?>" <?= $required ?>>
                <option value="">— <?= __('general.none') ?> —</option>
                <?php foreach ($f['relation_records'] as $rr): ?>
                <option value="<?= $rr['id'] ?>" <?= $currentVal==$rr['id']?'selected':'' ?>>
                  #<?= $rr['id'] ?> <?= h($rr['label'] ?? '') ?>
                </option>
                <?php endforeach; ?>
              </select>
              <?php else: ?><p style="color:var(--mt);font-size:.82rem">—</p><?php endif; ?>
              <?php break;

            // ── User (system users) ──────────────────────────────
            case 'user': ?>
              <?php
              $users = DB::q('SELECT id, name FROM users WHERE active = 1 ORDER BY name ASC');
              ?>
              <select name="<?= h($name) ?>" <?= $required ?>>
                <option value="">— <?= __('general.none') ?> —</option>
                <?php foreach ($users as $u): ?>
                <option value="<?= $u['id'] ?>" <?= $currentVal==$u['id']?'selected':'' ?>><?= h($u['name']) ?></option>
                <?php endforeach; ?>
              </select>
              <?php break;

            // ── Formula (read-only, computed on save) ─────────────
            case 'formula':
              $fMeta   = !empty($f['options_json']) ? (json_decode($f['options_json'], true) ?? []) : [];
              $fOutput = $fMeta['output'] ?? 'number';
              $fExpr   = $fMeta['expression'] ?? '';
              $fDisplay = renderFieldValue($f, $currentVal);
              ?>
              <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
                <div style="background:var(--sf2);border:1px solid var(--bd2);border-radius:var(--r2);padding:8px 14px;font-size:1rem;font-weight:700;color:var(--ac);min-width:80px;text-align:right">
                  <?= $currentVal !== null && $currentVal !== '' ? $fDisplay : '<span style="color:var(--mt);font-weight:400">calculado ao salvar</span>' ?>
                </div>
                <div style="font-size:.72rem;color:var(--mt);font-family:monospace;flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="<?= h($fExpr) ?>">
                  ∑ <?= h(mb_substr($fExpr, 0, 60)).(mb_strlen($fExpr)>60?'…':'') ?>
                </div>
              </div>
              <div class="hint" style="margin-top:4px">Campo calculado automaticamente. Não editável.</div>
              <?php break;

            // ── Default (fallback) ────────────────────────────────────
            default: ?>
              <input type="text" name="<?= h($name) ?>" value="<?= h($currentVal ?? '') ?>" <?= $required ?>>
          <?php endswitch; ?>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>

        <?php if (!empty($fields)): ?>
        <div class="form-actions">
          <a href="<?= url('/e/' . h($entity['slug'])) ?>" class="btn btn-ghost"><?= __('general.cancel') ?></a>
          <button type="submit" class="btn btn-primary" style="background:<?= h($entity['color']) ?>">
            💾 <?= $isEdit ? __('general.save') : __('general.create') ?>
          </button>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <?php if ($isEdit): ?>
    <div>
      <div class="card">
        <div class="card-title" style="font-size:.82rem"><?= __('general.actions') ?></div>
        <div style="font-size:.82rem;color:var(--mt2);line-height:2.2">
          <div><span style="color:var(--mt)">ID:</span> #<?= $record['id'] ?></div>
          <div><span style="color:var(--mt)"><?= __('general.created_at') ?>:</span> <?= dateBr($record['created_at']) ?></div>
          <div><span style="color:var(--mt)"><?= __('general.updated_at') ?>:</span> <?= dateBr($record['updated_at']) ?></div>
        </div>
        <div style="margin-top:14px;padding-top:14px;border-top:1px solid var(--bd)">
          <form method="POST" action="<?= url('e/'. h($entity['slug']).'/'. $record['id'].'/delete');?>"
                onsubmit="return confirm('<?= __('records.delete_confirm') ?>')">
            <button type="submit" class="btn btn-danger" style="width:100%;justify-content:center">🗑️ <?= __('general.delete') ?></button>
          </form>
        </div>
      </div>
    </div>
    <?php endif; ?>
  </div>
</form>

<script>
// ── Rich text ─────────────────────────────────────────────────────────
function rtCmd(cmd, val) {
  document.execCommand(cmd, false, val || null);
}
function syncRichText(id) {
  document.getElementById('rthidden-' + id).value =
    document.getElementById('rt-' + id).innerHTML;
}
// Garante que o hidden fica sincronizado antes do submit
document.getElementById('record-form').addEventListener('submit', function() {
  document.querySelectorAll('[id^="rt-"]').forEach(function(el) {
    var id = el.id.replace('rt-', '');
    var hidden = document.getElementById('rthidden-' + id);
    if (hidden) hidden.value = el.innerHTML;
  });
});

// ── Duration ─────────────────────────────────────────────────────────
function syncDur(id) {
  var h = parseInt(document.getElementById('dur-h-' + id).value) || 0;
  var m = parseInt(document.getElementById('dur-m-' + id).value) || 0;
  var s = parseInt(document.getElementById('dur-s-' + id).value) || 0;
  document.getElementById('dur-val-' + id).value = (h * 3600) + (m * 60) + s;
}

// ── Tags ──────────────────────────────────────────────────────────────
function tagsKeydown(e, id) {
  if (e.key === 'Enter' || e.key === ',') {
    e.preventDefault();
    var input = document.getElementById('tags-input-' + id);
    var val   = input.value.trim().replace(/,$/, '');
    if (!val) return;
    addTag(id, val);
    input.value = '';
  } else if (e.key === 'Backspace' && !e.target.value) {
    var chips = document.getElementById('tags-wrap-' + id).querySelectorAll('.tag-chip');
    if (chips.length) chips[chips.length - 1].remove();
    syncTags(id);
  }
}
function addTag(id, val) {
  var wrap = document.getElementById('tags-wrap-' + id);
  var chip = document.createElement('span');
  chip.className = 'tag-chip';
  chip.innerHTML = val.replace(/</g,'&lt;') + ' <button type="button" onclick="removeTag(' + id + ', this)">×</button>';
  wrap.insertBefore(chip, document.getElementById('tags-input-' + id));
  syncTags(id);
}
function removeTag(id, btn) {
  btn.closest('.tag-chip').remove();
  syncTags(id);
}
function syncTags(id) {
  var chips = document.getElementById('tags-wrap-' + id).querySelectorAll('.tag-chip');
  var vals  = [];
  chips.forEach(function(c) {
    vals.push(c.textContent.replace('×','').trim());
  });
  document.getElementById('tags-val-' + id).value = JSON.stringify(vals);
}

// ── Image upload ──────────────────────────────────────────────────────
function handleImageUpload(input, id) {
  if (!input.files || !input.files[0]) return;
  var file = input.files[0];
  var reader = new FileReader();
  reader.onload = function(e) {
    document.getElementById('imgdata-' + id).value = e.target.result;
    document.getElementById('imgkeep-' + id).value = '';
    var preview = document.getElementById('img-preview-' + id);
    if (preview.tagName === 'IMG') {
      preview.src = e.target.result;
    } else {
      preview.innerHTML = '<img src="' + e.target.result + '" class="upload-preview">';
    }
  };
  reader.readAsDataURL(file);
}
function clearImage(id) {
  document.getElementById('imgdata-' + id).value = '';
  document.getElementById('imgkeep-' + id).value = '';
  var preview = document.getElementById('img-preview-' + id);
  preview.innerHTML = '<div style="font-size:1.5rem;margin-bottom:6px">🖼</div><div style="font-size:.82rem;color:var(--mt)">Clique ou arraste uma imagem</div>';
}

// ── File upload ───────────────────────────────────────────────────────
function handleFileUpload(input, id) {
  if (!input.files || !input.files[0]) return;
  var file = input.files[0];
  var reader = new FileReader();
  reader.onload = function(e) {
    document.getElementById('filedata-' + id).value = e.target.result;
    document.getElementById('filekeep-' + id).value = '';
    document.getElementById('file-label-' + id).textContent = file.name + ' (' + (file.size / 1024).toFixed(1) + ' KB)';
  };
  reader.readAsDataURL(file);
}
function clearFile(id) {
  document.getElementById('filedata-' + id).value = '';
  document.getElementById('filekeep-' + id).value = '';
}
</script>

<?php partial('layout/footer') ?>
