<?php

/**
 * Module registry for per-user access control.
 *
 * Each module: a label (shown on the Users & Roles permission grid) and the
 * route-name prefixes that belong to it. EnsureModuleAccess matches the current
 * route name against these prefixes; User::canModule() checks the user's
 * permissions array. Routes not matching any module are always allowed for a
 * logged-in user (dashboard, profile, logout, public scans).
 *
 * Order matters: more specific prefixes (e.g. master.countries.) are listed on
 * their own module so they win over a broader bare name like "countries".
 */
return [
    'products'         => ['label' => 'Products',            'prefixes' => ['products.']],
    'batches'          => ['label' => 'Batches',             'prefixes' => ['batches.', 'batches', 'partial-batches', 'batch-downloads']],
    'master_cartons'   => ['label' => 'Master Cartons',      'prefixes' => ['master-cartons.', 'master-cartons']],
    'master_data'      => ['label' => 'Master Data',         'prefixes' => ['master.countries.', 'master.tclasses.']],
    'orders'           => ['label' => 'Purchase & Sales Orders', 'prefixes' => ['orders.po', 'orders.so']],
    'invoices'         => ['label' => 'Invoices (PI / CI)',      'prefixes' => ['orders.pi', 'orders.ci']],
    'logistics'        => ['label' => 'Logistics & Shipments', 'prefixes' => ['shipments.', 'shipments', 'distribution']],
    'compliance'       => ['label' => 'Compliance (Country Reg.)', 'prefixes' => ['countries']],
    'anti_counterfeit' => ['label' => 'Anti-Counterfeit',    'prefixes' => ['anticounterfeit.']],
    'vault'            => ['label' => 'Document Vault',       'prefixes' => ['vault']],
    'customers'        => ['label' => 'Customers & Sales',   'prefixes' => ['customers.']],
    'country_managers' => ['label' => 'Country Managers',     'prefixes' => ['country-managers.']],
    'patients'         => ['label' => 'Patient Portal',       'prefixes' => ['patients']],
    'reports'          => ['label' => 'Reports',             'prefixes' => ['reports']],
    'notifications'    => ['label' => 'Notifications',        'prefixes' => ['notifications']],
    'users'            => ['label' => 'Users & Roles',       'prefixes' => ['users.', 'users']],
];
