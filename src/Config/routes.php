<?php
// Path: src/Config/routes.php

return [
    // --- ADMIN ROUTES ---
    'travel-sys-dashboard' => [
        'controller' => \App\Controllers\Admin\DashboardController::class,
        'method'     => 'index',
        'capability' => 'read'
    ],
    'travel-sys-sales' => [
        'controller' => \App\Controllers\Admin\PackageController::class,
        'method'     => 'index',
        'capability' => 'manage_options'
    ],
    'travel-sys-ops' => [
        'controller' => \App\Controllers\Admin\OperationalController::class,
        'method'     => 'index',
        'capability' => 'manage_options'
    ],
    'travel-sys-finance-group' => [
        'controller' => \App\Controllers\Admin\FinanceController::class,
        'method'     => 'index',
        'capability' => 'manage_options'
    ],
    'travel-sys-settings-group' => [
        'controller' => \App\Controllers\Admin\SettingsController::class,
        'method'     => 'index',
        'capability' => 'manage_options'
    ],
    
    // --- ACTION ROUTES ---
    'actions' => [
        'umh_save_package' => [
            'controller' => \App\Controllers\Admin\PackageController::class,
            'method'     => 'save'
        ],
        'umh_delete_package' => [
            'controller' => \App\Controllers\Admin\PackageController::class,
            'method'     => 'delete'
        ]
    ]
];
