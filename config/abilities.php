<?php

/**
 * Fine-grained action permissions assignable per-user (in addition to the
 * role-based module access in config/modules.php).
 *
 * For every module key in config/modules.php, a permission "{module}.{action}"
 * is created for each action below (e.g. products.view, products.edit). These
 * are granted DIRECTLY to a user via the Users page and checked with
 * $user->can('products.edit') / @can('products.edit').
 */
return [
    'actions' => [
        'view'   => 'View',
        'create' => 'Create',
        'edit'   => 'Edit',
        'delete' => 'Delete',
        'export' => 'Export',
    ],

    /*
     * Visibility scopes. These are NOT granted to roles by default — every
     * user only sees their own records (created_by / assigned). A super admin
     * grants "{module}.view_all" (per area) to let a user see everyone's data
     * in that module. Checked via User::canViewAll($module).
     */
    'scopes' => [
        'view_all' => 'View All',
    ],
];
