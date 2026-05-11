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

<div class="sec-head">
  <div class="sec-title">
    <?= $isEdit ? '✏️ '.__('records.edit') : '+' ?> <?= h($entity['name']) ?>
  </div>
  <a href="<?= url('/e/' . h($entity['slug'])) ?>" class="btn btn-ghost btn-sm">← <?= __('general.back') ?></a>
</div>

<form method="POST" action="<?= url($isEdit ? '/e/'.$entity['slug'].'/'.$record['id'].'/update' : '/e/'.$entity['slug'].'/create'); ?>" enctype="multipart/form-data">

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
        ?>
        <div class="field">
          <label>
            <?= h($f['name']) ?>
            <?php if ($f['required']): ?><span style="color:var(--rd)">*</span><?php endif; ?>
            <span style="color:var(--mt);font-weight:400;font-size:.7rem;margin-left:6px"><?= fieldTypeIcon($f['field_type']) ?> <?= h(__('fields.types.'.$f['field_type'])) ?></span>
          </label>

          <?php switch ($f['field_type']):
            case 'textarea': ?>
            <textarea name="<?= h($name) ?>" style="min-height:100px" <?= $required ?>><?= h($currentVal ?? '') ?></textarea>
            <?php break; case 'select': ?>
            <?php $opts = json_decode($f['options_json'] ?? '[]', true) ?: []; ?>
            <select name="<?= h($name) ?>" <?= $required ?>>
              <option value="">— <?= __('general.none') ?> —</option>
              <?php foreach ($opts as $o): ?>
              <option value="<?= h($o) ?>" <?= $currentVal===$o?'selected':'' ?>><?= h($o) ?></option>
              <?php endforeach; ?>
            </select>
            <?php break; case 'multiselect': ?>
            <?php $opts = json_decode($f['options_json'] ?? '[]', true) ?: [];
                  $selected = $currentVal ? json_decode($currentVal, true) : []; ?>
            <div style="background:var(--sf2);border:1px solid var(--bd2);border-radius:var(--r2);padding:10px;display:flex;flex-wrap:wrap;gap:8px">
              <?php foreach ($opts as $o): ?>
              <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:.85rem;font-weight:400">
                <input type="checkbox" name="<?= h($name) ?>[]" value="<?= h($o) ?>"
                       <?= in_array($o, $selected)?'checked':'' ?> style="accent-color:var(--ac);width:auto">
                <?= h($o) ?>
              </label>
              <?php endforeach; ?>
            </div>
            <?php break; case 'checkbox': ?>
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:.9rem;font-weight:400;margin-top:4px">
              <input type="checkbox" name="<?= h($name) ?>" value="1"
                     <?= $currentVal?'checked':'' ?> style="accent-color:var(--ac);width:auto;transform:scale(1.2)">
              <?= __('general.yes') ?>
            </label>
            <?php break; case 'date': ?>
            <input type="date" name="<?= h($name) ?>" value="<?= h($currentVal ? date('Y-m-d', strtotime($currentVal)) : '') ?>" <?= $required ?>>
            <?php break; case 'datetime': ?>
            <input type="datetime-local" name="<?= h($name) ?>" value="<?= h($currentVal ? date('Y-m-d\TH:i', strtotime($currentVal)) : '') ?>" <?= $required ?>>
            <?php break; case 'number': ?>
            <input type="number" name="<?= h($name) ?>" value="<?= h($currentVal ?? '') ?>" step="any" <?= $required ?>>
            <?php break; case 'currency': ?>
            <div style="position:relative">
              <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--mt);font-size:.85rem">R$</span>
              <input type="number" name="<?= h($name) ?>" value="<?= h($currentVal ?? '') ?>" step="0.01" min="0" style="padding-left:36px" <?= $required ?>>
            </div>
            <?php break; case 'email': ?>
            <input type="email" name="<?= h($name) ?>" value="<?= h($currentVal ?? '') ?>" <?= $required ?>>
            <?php break; case 'url': ?>
            <input type="url" name="<?= h($name) ?>" value="<?= h($currentVal ?? '') ?>" placeholder="https://" <?= $required ?>>
            <?php break; case 'phone': ?>
            <input type="tel" name="<?= h($name) ?>" value="<?= h($currentVal ?? '') ?>" placeholder="(11) 99999-9999" <?= $required ?>>
            <?php break; case 'relation': ?>
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
            <?php break; default: ?>
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

<?php partial('layout/footer') ?>
