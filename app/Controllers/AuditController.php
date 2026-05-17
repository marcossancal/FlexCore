<?php

declare(strict_types=1);

namespace FlexCore\App\Controllers;

use DB;
use Auth;
use FlexCore\App\Services\AuditService;

class AuditController
{
    private AuditService $auditService;

    public function __construct()
    {
        $this->auditService = new AuditService();
        // Garante que as colunas novas existam antes de qualquer query
        $this->auditService->ensureColumns();
    }

    // ── Lista ─────────────────────────────────────────────────────────

    public function index(): void
    {
        $page   = max(1, (int)($_GET['page'] ?? 1));
        $per    = 50;
        $offset = ($page - 1) * $per;

        $filterAction   = trim($_GET['action']    ?? '');
        $filterEntity   = (int)($_GET['entity_id'] ?? 0);
        $filterUser     = (int)($_GET['user_id']   ?? 0);
        $filterDateFrom = trim($_GET['date_from']  ?? '');
        $filterDateTo   = trim($_GET['date_to']    ?? '');
        $filterRecord   = (int)($_GET['record_id'] ?? 0);

        [$where, $params] = $this->buildWhere(
            $filterAction, $filterEntity, $filterUser,
            $filterDateFrom, $filterDateTo, $filterRecord
        );

        $total = (int)(DB::one(
            "SELECT COUNT(*) AS n FROM audit_log al {$where}",
            $params
        )['n'] ?? 0);

        // Verifica se coluna reverted_by já existe antes de usá-la no JOIN
        $auditCols       = array_column(DB::q("SHOW COLUMNS FROM audit_log"), 'Field');
        $hasRevertedBy   = in_array('reverted_by', $auditCols, true);
        $revertedByField = $hasRevertedBy ? ", ru.name AS reverted_by_name" : ", NULL AS reverted_by_name";
        $revertedByJoin  = $hasRevertedBy ? "LEFT JOIN users ru ON ru.id = al.reverted_by" : "";

        $entries = DB::q(
            "SELECT al.*, u.name AS user_name, u.email AS user_email,
                    e.name AS entity_name, e.icon AS entity_icon, e.slug AS entity_slug
                    {$revertedByField}
               FROM audit_log al
               LEFT JOIN users    u ON u.id = al.user_id
               LEFT JOIN entities e ON e.id = al.entity_id
               {$revertedByJoin}
              {$where}
              ORDER BY al.id DESC
              LIMIT {$per} OFFSET {$offset}",
            $params
        );

        $pages = max(1, (int)ceil($total / $per));

        $allEntities  = DB::q('SELECT id, name, icon FROM entities ORDER BY name ASC');
        $allUsers     = DB::q('SELECT id, name FROM users ORDER BY name ASC');
        $actionLabels = $this->actionLabels();

        $page_title  = 'Auditoria';
        $active_page = 'audit';
        $breadcrumbs = [['label' => 'Auditoria']];

        partial('layout/header');
        require BASE . '/app/views/audit/index.php';
        partial('layout/footer');
    }

    // ── Detalhe ───────────────────────────────────────────────────────

    public function show(int $id): void
    {
        $auditCols2      = array_column(DB::q("SHOW COLUMNS FROM audit_log"), 'Field');
        $hasRevertedBy2  = in_array('reverted_by', $auditCols2, true);
        $rvField2        = $hasRevertedBy2 ? ", ru.name AS reverted_by_name" : ", NULL AS reverted_by_name";
        $rvJoin2         = $hasRevertedBy2 ? "LEFT JOIN users ru ON ru.id = al.reverted_by" : "";

        $entry = DB::one(
            "SELECT al.*, u.name AS user_name, u.email AS user_email,
                    e.name AS entity_name, e.icon AS entity_icon, e.slug AS entity_slug
                    {$rvField2}
               FROM audit_log al
               LEFT JOIN users    u ON u.id = al.user_id
               LEFT JOIN entities e ON e.id = al.entity_id
               {$rvJoin2}
              WHERE al.id = ?",
            [$id]
        );

        if (!$entry) {
            http_response_code(404);
            die('Entrada não encontrada.');
        }

        $fieldLabels = [];
        if ($entry['entity_id']) {
            $fields      = DB::q('SELECT id, name, field_type FROM entity_fields WHERE entity_id = ?', [$entry['entity_id']]);
            $fieldLabels = array_column($fields, null, 'id');
        }

        $before = !empty($entry['before_json']) ? json_decode($entry['before_json'], true) : [];
        $after  = !empty($entry['after_json'])  ? json_decode($entry['after_json'],  true) : [];

        $actionLabels = $this->actionLabels();

        $page_title  = 'Auditoria — Detalhe';
        $active_page = 'audit';
        $breadcrumbs = [
            ['label' => 'Auditoria', 'url' => admin_url('/audit')],
            ['label' => "Entrada #{$id}"],
        ];

        partial('layout/header');
        require BASE . '/app/views/audit/show.php';
        partial('layout/footer');
    }

    // ── Rollback ──────────────────────────────────────────────────────

    public function revert(int $id): void
    {
        if ((Auth::user()['role'] ?? '') !== 'admin') {
            $_SESSION['flash_error'] = 'Apenas administradores podem desfazer ações.';
            admin_redirect('/audit');
            return;
        }

        try {
            $this->auditService->revert($id);
            $_SESSION['flash_success'] = "Ação #{$id} desfeita com sucesso.";
        } catch (\RuntimeException $e) {
            $_SESSION['flash_error'] = $e->getMessage();
        }

        $ref = $_SERVER['HTTP_REFERER'] ?? '';
        if ($ref && strpos($ref, '/audit/' . $id) !== false) {
            header('Location: ' . admin_url('/audit/' . $id));
        } else {
            admin_redirect('/audit');
        }
        exit;
    }

    // ── Helpers ───────────────────────────────────────────────────────

    private function buildWhere(
        string $action, int $entity, int $user,
        string $dateFrom, string $dateTo, int $record
    ): array {
        $conds  = [];
        $params = [];

        if ($action !== '') {
            $conds[]  = 'al.action = ?';
            $params[] = $action;
        }
        if ($entity > 0) {
            $conds[]  = 'al.entity_id = ?';
            $params[] = $entity;
        }
        if ($user > 0) {
            $conds[]  = 'al.user_id = ?';
            $params[] = $user;
        }
        if ($dateFrom !== '') {
            $conds[]  = 'DATE(al.created_at) >= ?';
            $params[] = $dateFrom;
        }
        if ($dateTo !== '') {
            $conds[]  = 'DATE(al.created_at) <= ?';
            $params[] = $dateTo;
        }
        if ($record > 0) {
            $conds[]  = 'al.record_id = ?';
            $params[] = $record;
        }

        $where = empty($conds) ? '' : 'WHERE ' . implode(' AND ', $conds);
        return [$where, $params];
    }

    private function actionLabels(): array
    {
        return [
            'create_record'    => ['label' => 'Criação',        'icon' => '✅', 'color' => '#22c55e'],
            'update_record'    => ['label' => 'Edição',         'icon' => '✏️',  'color' => '#f59e0b'],
            'delete_record'    => ['label' => 'Exclusão',       'icon' => '🗑️',  'color' => '#ef4444'],
            'revert'           => ['label' => 'Rollback',       'icon' => '↩️',  'color' => '#6c5ce7'],
            'login'            => ['label' => 'Login',          'icon' => '🔐', 'color' => '#00d4ff'],
            'logout'           => ['label' => 'Logout',         'icon' => '🚪', 'color' => '#94a3b8'],
            'create_entity'    => ['label' => 'Nova entidade',  'icon' => '📋', 'color' => '#22c55e'],
            'update_entity'    => ['label' => 'Edit. entidade', 'icon' => '⚙️',  'color' => '#f59e0b'],
            'delete_entity'    => ['label' => 'Del. entidade',  'icon' => '🗑️',  'color' => '#ef4444'],
            'settings_save'    => ['label' => 'Configurações',  'icon' => '🔧', 'color' => '#94a3b8'],
            'plugin_install'   => ['label' => 'Plugin instal.', 'icon' => '🧩', 'color' => '#22c55e'],
            'plugin_toggle'    => ['label' => 'Plugin toggle',  'icon' => '🧩', 'color' => '#f59e0b'],
            'plugin_uninstall' => ['label' => 'Plugin remov.',  'icon' => '🧩', 'color' => '#ef4444'],
        ];
    }
}