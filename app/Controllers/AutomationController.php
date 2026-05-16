<?php

declare(strict_types=1);

namespace FlexCore\App\Controllers;
use Auth;
use DB;
/**
 * AutomationController — SRP: CRUD de automações e logs.
 *
 * Rotas cobertas:
 *   GET  /automations
 *   POST /automations/create
 *   POST /automations/{id}/update
 *   POST /automations/{id}/toggle
 *   POST /automations/{id}/delete
 *   GET  /automations/{id}/logs
 */
class AutomationController
{
    public function index(): void
    {
        Auth::require(['admin']);
        $automations = DB::q('SELECT * FROM automations ORDER BY created_at DESC');
        $entities    = DB::q('SELECT id, name, slug, icon FROM entities WHERE active = 1 ORDER BY name ASC');
        $entitiesMap = array_column($entities, null, 'id');
        view('automations/index', compact('automations', 'entities', 'entitiesMap'));
    }

    public function store(): void
    {
        Auth::require(['admin']);
        $name = trim(post('name'));
        if (!$name) { flash('err', 'Nome obrigatório.'); admin_redirect('/automations'); }

        $conditions = [];
        foreach ($_POST['cond'] ?? [] as $c) {
            if (!empty($c['field'])) {
                $conditions[] = ['field' => $c['field'], 'op' => $c['op'] ?? 'eq', 'value' => $c['value'] ?? ''];
            }
        }

        DB::exec(
            'INSERT INTO automations
                (name, description, trigger_entity_id, trigger_event,
                 trigger_conditions, action_type, action_config, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $name, trim(post('description')),
                (int) post('trigger_entity_id') ?: null,
                post('trigger_event', 'on_create'),
                json_encode(array_values($conditions)),
                post('action_type', 'webhook'),
                json_encode($_POST['action_config'] ?? []),
                Auth::id(),
            ]
        );
        flash('ok', "Automação \"{$name}\" criada!");
        admin_redirect('/automations');
    }

    public function update(int $id): void
    {
        Auth::require(['admin']);
        $conditions = [];
        foreach ($_POST['cond'] ?? [] as $c) {
            if (!empty($c['field'])) $conditions[] = $c;
        }
        DB::run(
            'UPDATE automations
                SET name = ?, description = ?, trigger_entity_id = ?,
                    trigger_event = ?, trigger_conditions = ?,
                    action_type = ?, action_config = ?, updated_at = NOW()
              WHERE id = ?',
            [
                trim(post('name')), trim(post('description')),
                (int) post('trigger_entity_id') ?: null,
                post('trigger_event'), json_encode(array_values($conditions)),
                post('action_type'), json_encode($_POST['action_config'] ?? []), $id,
            ]
        );
        flash('ok', 'Automação atualizada!');
        admin_redirect('/automations');
    }

    public function toggle(int $id): void
    {
        Auth::require(['admin']);
        $row = DB::one('SELECT active FROM automations WHERE id = ?', [$id]);
        DB::run('UPDATE automations SET active = ? WHERE id = ?', [$row['active'] ? 0 : 1, $id]);
        flash('ok', 'Status alterado.');
        admin_redirect('/automations');
    }

    public function destroy(int $id): void
    {
        Auth::require(['admin']);
        DB::run('DELETE FROM automations WHERE id = ?', [$id]);
        flash('ok', 'Automação excluída.');
        admin_redirect('/automations');
    }

    public function logs(int $id): void
    {
        Auth::require(['admin']);
        $automation = DB::one('SELECT * FROM automations WHERE id = ?', [$id]);
        if (!$automation) { http_response_code(404); view('errors/404'); return; }
        $logs = DB::q(
            'SELECT * FROM automation_logs WHERE automation_id = ? ORDER BY created_at DESC LIMIT 200',
            [$id]
        );
        view('automations/logs', compact('automation', 'logs'));
    }
}
