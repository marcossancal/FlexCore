<?php

declare(strict_types=1);

namespace FlexCore\App\Controllers;
use Auth;
use DB;

/**
 * EntityController — SRP: gerencia entidades e seus campos.
 */
class EntityController
{
    // ── Entities ─────────────────────────────────────────────────────

    public function index(): void
    {
        Auth::require(['admin']);
        $entities = DB::q(
            'SELECT e.*,
                    (SELECT COUNT(*) FROM entity_fields  WHERE entity_id = e.id) AS field_count,
                    (SELECT COUNT(*) FROM entity_records WHERE entity_id = e.id) AS record_count
               FROM entities e
              ORDER BY e.position ASC, e.name ASC'
        );
        view('entities/index', compact('entities'));
    }

    public function create(): void
    {
        Auth::require(['admin']);
        view('entities/form');
    }

    public function store(): void
    {
        Auth::require(['admin']);
        $name = trim(post('name'));
        if (!$name) {
            flash('err', 'Nome obrigatório.');
            admin_redirect('/entities/new');
        }

        $sl = slug(post('slug') ?: $name);
        $id = DB::exec(
            'INSERT INTO entities (name, slug, icon, color, description, position, active, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [$name, $sl, post('icon', '📋'), post('color', '#00d4ff'),
             post('description'), (int) post('position', 0), (int) post('active', 1), Auth::id()]
        );

        $after = DB::one('SELECT * FROM entities WHERE id = ?', [$id]) ?: [];
        audit('create_entity', $id, null, "Entidade '{$name}' criada", [], $after);

        flash('ok', "Entidade '{$name}' criada! Agora configure os campos.");
        admin_redirect("/entities/{$id}/fields");
    }

    public function edit(int $id): void
    {
        Auth::require(['admin']);
        $entity = DB::one('SELECT * FROM entities WHERE id = ?', [$id]);
        if (!$entity) { http_response_code(404); view('errors/404'); return; }
        view('entities/form', compact('entity'));
    }

    public function update(int $id): void
    {
        Auth::require(['admin']);

        $before = DB::one('SELECT * FROM entities WHERE id = ?', [$id]) ?: [];

        DB::run(
            'UPDATE entities
                SET name = ?, slug = ?, icon = ?, color = ?, description = ?,
                    position = ?, active = ?, updated_at = NOW()
              WHERE id = ?',
            [trim(post('name')), slug(post('slug') ?: post('name')),
             post('icon', '📋'), post('color', '#00d4ff'), post('description'),
             (int) post('position', 0), (int) post('active', 1), $id]
        );

        $after = DB::one('SELECT * FROM entities WHERE id = ?', [$id]) ?: [];
        audit('update_entity', $id, null, 'Entidade atualizada', $before, $after);

        flash('ok', 'Entidade atualizada!');
        admin_redirect('/entities');
    }

    public function destroy(int $id): void
    {
        Auth::require(['admin']);

        $ent    = DB::one('SELECT * FROM entities WHERE id = ?', [$id]);
        $fields = DB::q('SELECT * FROM entity_fields WHERE entity_id = ? ORDER BY position ASC', [$id]);

        // Snapshot completo: entidade + campos (necessário para restaurar)
        $before = ['entity' => $ent ?: [], 'fields' => $fields ?: []];

        DB::run('DELETE FROM entities WHERE id = ?', [$id]);

        audit('delete_entity', $id, null, "Entidade '{$ent['name']}' excluída", $before, []);

        flash('ok', "Entidade '{$ent['name']}' excluída.");
        admin_redirect('/entities');
    }

    public function bulkDestroy(): void
    {
        Auth::require(['admin']);
        $raw = trim(post('ids', ''));
        if (!$raw) { flash('err', 'Nenhuma entidade selecionada.'); admin_redirect('/entities'); }

        $ids = array_filter(array_map('intval', explode(',', $raw)));
        if (empty($ids)) { flash('err', 'Seleção inválida.'); admin_redirect('/entities'); }

        $count = 0;
        foreach ($ids as $id) {
            $ent    = DB::one('SELECT * FROM entities WHERE id = ?', [$id]);
            if (!$ent) continue;
            $fields = DB::q('SELECT * FROM entity_fields WHERE entity_id = ? ORDER BY position ASC', [$id]);

            $before = ['entity' => (array)$ent, 'fields' => $fields ?: []];

            DB::run('DELETE FROM entities WHERE id = ?', [$id]);
            audit('delete_entity', $id, null, "Entidade '{$ent['name']}' excluída em lote", $before, []);
            $count++;
        }

        flash('ok', "{$count} entidade(s) excluída(s) com sucesso.");
        admin_redirect('/entities');
    }

    public function saveApiResponses(int $id): void
    {
        Auth::require(['admin']);
        $entity = DB::one('SELECT id, slug FROM entities WHERE id = ?', [$id]);
        if (!$entity) { http_response_code(404); view('errors/404'); return; }

        $allowed = [
            'select_all', 'select_one', 'select_not_found',
            'insert', 'insert_validation',
            'update', 'update_not_found',
            'delete', 'delete_not_found',
            'unauthorized', 'forbidden', 'rate_limit',
        ];

        $raw   = $_POST['responses'] ?? [];
        $clean = [];
        foreach ($allowed as $op) {
            if (!isset($raw[$op])) continue;
            $code  = (int) ($raw[$op]['code'] ?? 200);
            if ($code < 100 || $code > 599) $code = 200;
            $extra = trim($raw[$op]['extra'] ?? '');
            if ($extra !== '' && json_decode($extra) === null) $extra = '';
            $clean[$op] = [
                'code'    => $code,
                'message' => trim($raw[$op]['message'] ?? ''),
                'extra'   => $extra,
            ];
        }

        $before = DB::one('SELECT * FROM entities WHERE id = ?', [$id]) ?: [];
        DB::run(
            'UPDATE entities SET api_responses = ?, updated_at = NOW() WHERE id = ?',
            [json_encode($clean, JSON_UNESCAPED_UNICODE), $id]
        );
        $after = DB::one('SELECT * FROM entities WHERE id = ?', [$id]) ?: [];
        audit('update_entity', $id, null, "Respostas API da entidade #{$id} atualizadas", $before, $after);

        flash('ok', 'Respostas de API salvas!');
        admin_redirect("/entities/{$id}/edit?tab=api");
    }

    // ── Fields ───────────────────────────────────────────────────────

    public function fields(int $id): void
    {
        Auth::require(['admin']);
        $entity = DB::one('SELECT * FROM entities WHERE id = ?', [$id]);
        if (!$entity) { http_response_code(404); view('errors/404'); return; }

        $fields = DB::q(
            'SELECT ef.*, e2.name AS relation_name
               FROM entity_fields ef
               LEFT JOIN entities e2 ON e2.id = ef.relation_entity_id
              WHERE ef.entity_id = ?
              ORDER BY ef.position ASC',
            [$id]
        );
        $all_entities = DB::q('SELECT id, name, icon FROM entities WHERE active = 1 ORDER BY name ASC');
        view('entities/fields', compact('entity', 'fields', 'all_entities'));
    }

    public function storeField(int $eid): void
    {
        Auth::require(['admin']);
        $name = trim(post('name'));
        if (!$name) {
            flash('err', 'Nome do campo obrigatório.');
            admin_redirect("/entities/{$eid}/fields");
        }

        $sl   = str_replace('-', '_', slug(post('slug') ?: $name));
        $opts = $this->buildOptionsJson(post('field_type', 'text'));

        DB::exec(
            'INSERT INTO entity_fields
                (entity_id, name, slug, field_type, options_json, relation_entity_id,
                 required, show_in_list, position)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$eid, $name, $sl, post('field_type', 'text'), $opts,
             (int) post('relation_entity_id', 0) ?: null,
             (int) post('required', 0), (int) post('show_in_list', 0),
             (int) post('position', 0)]
        );
        flash('ok', "Campo '{$name}' adicionado!");
        admin_redirect("/entities/{$eid}/fields");
    }

    public function updateField(int $eid, int $fid): void
    {
        Auth::require(['admin']);
        $opts = $this->buildOptionsJson(post('field_type', 'text'));

        DB::run(
            'UPDATE entity_fields
                SET name = ?, slug = ?, field_type = ?, options_json = ?,
                    relation_entity_id = ?, required = ?, show_in_list = ?, position = ?
              WHERE id = ? AND entity_id = ?',
            [trim(post('name')), str_replace('-', '_', slug(post('slug'))),
             post('field_type', 'text'), $opts,
             (int) post('relation_entity_id', 0) ?: null,
             (int) post('required', 0), (int) post('show_in_list', 0),
             (int) post('position', 0), $fid, $eid]
        );
        flash('ok', 'Campo atualizado!');
        admin_redirect("/entities/{$eid}/fields");
    }

    public function destroyField(int $eid, int $fid): void
    {
        Auth::require(['admin']);
        $f = DB::one('SELECT name FROM entity_fields WHERE id = ?', [$fid]);
        DB::run('DELETE FROM entity_fields WHERE id = ? AND entity_id = ?', [$fid, $eid]);
        flash('ok', "Campo '{$f['name']}' excluído.");
        admin_redirect("/entities/{$eid}/fields");
    }

    // ── Permissions ──────────────────────────────────────────────────

    public function savePermissions(int $id): void
    {
        Auth::require(['admin']);
        $entity = DB::one('SELECT id, name FROM entities WHERE id = ?', [$id]);
        if (!$entity) { http_response_code(404); view('errors/404'); return; }

        $roles = ['admin', 'editor', 'viewer'];
        foreach ($roles as $role) {
            $canCreate = (int) !empty($_POST["can_create_{$role}"]);
            $canEdit   = (int) !empty($_POST["can_edit_{$role}"]);
            $canDelete = (int) !empty($_POST["can_delete_{$role}"]);

            DB::exec(
                'INSERT INTO entity_permissions (entity_id, role, can_create, can_edit, can_delete)
                 VALUES (?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE
                     can_create = VALUES(can_create),
                     can_edit   = VALUES(can_edit),
                     can_delete = VALUES(can_delete)',
                [$id, $role, $canCreate, $canEdit, $canDelete]
            );
        }

        audit('update_entity', $id, null, "Permissões da entidade '{$entity['name']}' atualizadas");
        flash('ok', 'Permissões salvas!');
        admin_redirect("/entities/{$id}/edit?tab=permissoes");
    }

    // ── Helpers ──────────────────────────────────────────────────────

    private function buildOptionsJson(string $fieldType): ?string
    {
        if (in_array($fieldType, ['select', 'multiselect'])) {
            $lines = array_filter(array_map('trim', explode("\n", post('options', ''))));
            return json_encode(array_values($lines));
        }

        if ($fieldType === 'formula') {
            return json_encode([
                'expression' => trim(post('formula_expression', '')),
                'output'     => in_array(post('formula_output', 'number'), ['number', 'currency', 'percent', 'text'])
                                    ? post('formula_output', 'number')
                                    : 'number',
            ]);
        }

        if (in_array($fieldType, ['image', 'file'])) {
            $maxMb = max(1, min(15, (int) post('max_size_mb', 5)));
            return json_encode(['max_size_mb' => $maxMb]);
        }

        $pluginOptions = \FlexCore\Core\Hooks\Hooks::applyFilter('field.options_build', null, [
            'field_type' => $fieldType,
            'post'       => $_POST,
        ]);
        if ($pluginOptions !== null) return $pluginOptions;

        return null;
    }
}