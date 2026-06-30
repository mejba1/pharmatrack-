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
];
