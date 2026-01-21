<?php
// Path: src/Providers/BackendServiceProvider.php

namespace UmrahManagement\Providers;

use UmrahManagement\Core\Container;
use UmrahManagement\Controllers\Admin\DashboardController;
use UmrahManagement\Controllers\Admin\PackageController;
use UmrahManagement\Controllers\Admin\BookingController;
use UmrahManagement\Controllers\Admin\JemaahController;
use UmrahManagement\Controllers\Admin\FinanceController;
use UmrahManagement\Controllers\Admin\MasterDataController;

class BackendServiceProvider {
    private $container;

    public function __construct(Container $container) {
        $this->container = $container;
    }

    public function register() {
        // Hook ke admin_menu WordPress
        add_action('admin_menu', [$this, 'registerMenus']);
        
        // Hook lain untuk backend (misal: admin_enqueue_scripts) bisa ditambah di sini
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    public function registerMenus() {
        // Menu Utama: Dashboard
        add_menu_page(
            'Umrah Management', 
            'Umrah Travel', 
            'manage_options', 
            'umroh-dashboard', 
            [$this->resolve(DashboardController::class), 'index'],
            'dashicons-airplane', 
            6
        );

        // Submenu: Paket Umrah
        add_submenu_page(
            'umroh-dashboard',
            'Paket Umrah',
            'Paket',
            'manage_options',
            'umroh-packages',
            [$this->resolve(PackageController::class), 'index']
        );

        // Submenu: Bookings
        add_submenu_page(
            'umroh-dashboard',
            'Data Booking',
            'Bookings',
            'manage_options',
            'umroh-bookings',
            [$this->resolve(BookingController::class), 'index']
        );

        // Submenu: Data Jemaah (CRM)
        add_submenu_page(
            'umroh-dashboard',
            'Data Jemaah',
            'Jemaah',
            'manage_options',
            'umroh-jemaah',
            [$this->resolve(\UmrahManagement\Controllers\Admin\CRMController::class), 'index']
        );

        // Submenu: Keuangan
        add_submenu_page(
            'umroh-dashboard',
            'Keuangan',
            'Keuangan',
            'manage_options',
            'umroh-finance',
            [$this->resolve(FinanceController::class), 'index']
        );
        
        // Submenu: Master Data
        add_submenu_page(
            'umroh-dashboard',
            'Master Data',
            'Master Data',
            'manage_options',
            'umroh-master-data',
            [$this->resolve(MasterDataController::class), 'index']
        );
    }
    
    public function enqueueAssets() {
        // Load CSS/JS Admin
        wp_enqueue_style('umroh-admin-css', plugins_url('../../assets/css/admin.css', __DIR__));
    }

    /**
     * Helper untuk mengambil instance controller dari container
     */
    private function resolve($class) {
        return $this->container->get($class);
    }
}