<?php

declare(strict_types=1);

namespace FlexCore\App\Controllers;
use Auth;
use DB;
/**
 * DashboardController — SRP: só monta a tela inicial.
 */
class DashboardController
{
    public function index(): void
    {
        $ents = DB::q(
            'SELECT e.*, COUNT(r.id) AS count
               FROM entities e
               LEFT JOIN entity_records r ON r.entity_id = e.id
              WHERE e.active = 1
              GROUP BY e.id
              ORDER BY e.position ASC, e.name ASC'
        );

        foreach ($ents as &$ent) {
            $fields = DB::q(
                'SELECT * FROM entity_fields
                  WHERE entity_id = ? AND show_in_list = 1
                  ORDER BY position ASC LIMIT 1',
                [$ent['id']]
            );
            $fieldId = $fields[0]['id'] ?? 0;
            $ent['recents'] = DB::q(
                "SELECT r.id, r.created_at, rv.val_text AS label
                   FROM entity_records r
                   LEFT JOIN record_values rv
                          ON rv.record_id = r.id AND rv.field_id = {$fieldId}
                  WHERE r.entity_id = ?
                  ORDER BY r.created_at DESC LIMIT 5",
                [$ent['id']]
            );
        }

        view('dashboard', ['entities' => $ents]);
    }
}
