<?php
// Path: src/Config/routes.php

// Format: 'page_slug' => [ControllerClass::class, 'methodName', 'capability']

return [
    // --- ADMIN ROUTES ---
    'umroh-dashboard' => [
        'controller' => \UmrahManagement\Controllers\Admin\DashboardController::class,
        'method'     => 'index',
        'capability' => 'read'
    ],
    'umroh-packages' => [
        'controller' => \UmrahManagement\Controllers\Admin\PackageController::class,
        'method'     => 'index',
        'capability' => 'manage_options'
    ],
    'umroh-packages-add' => [
        'controller' => \UmrahManagement\Controllers\Admin\PackageController::class,
        'method'     => 'create',
        'capability' => 'manage_options'
    ],
    'umroh-bookings' => [
        'controller' => \UmrahManagement\Controllers\Admin\BookingController::class,
        'method'     => 'index',
        'capability' => 'manage_options'
    ],
    
    // --- ACTION ROUTES (POST Handling via admin-post.php) ---
    // Key-nya adalah nama 'action' di input hidden form
    'actions' => [
        'umh_save_package' => [
            'controller' => \UmrahManagement\Controllers\Admin\PackageController::class,
            'method'     => 'save'
        ],
        'umh_delete_package' => [
            'controller' => \UmrahManagement\Controllers\Admin\PackageController::class,
            'method'     => 'delete'
        ],
        'umh_print_invoice' => [ // Contoh route invoice
            'controller' => \UmrahManagement\Controllers\Frontend\InvoiceController::class,
            'method'     => 'print'
        ]
    ]
];