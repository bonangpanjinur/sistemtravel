<?php
// Path: src/Providers/BackendServiceProvider.php

namespace UmrahManagement\Providers;

use UmrahManagement\Core\Container;
use UmrahManagement\Core\Router;

class BackendServiceProvider {
    private $container;
    private $router;

    public function __construct(Container $container) {
        $this->container = $container;
        // Router butuh container untuk resolve controller nanti
        $this->router = new Router($container);
    }

    public function register() {
        add_action('admin_menu', [$this, 'registerMenus']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
        
        // Register hook untuk setiap action di routes.php
        // Menggunakan file_exists check untuk menghindari error jika file belum dibuat
        $routesPath = plugin_dir_path(dirname(__DIR__)) . 'src/Config/routes.php';
        if (file_exists($routesPath)) {
            $routes = require $routesPath;
            if (isset($routes['actions'])) {
                foreach ($routes['actions'] as $action => $config) {
                    // Handle admin_post_{action}
                    add_action('admin_post_' . $action, function() use ($action) {
                        $this->router->dispatchAction($action);
                    });
                    // Handle admin_post_nopriv_{action} jika perlu guest access (bisa di config)
                }
            }
        }
    }

    public function registerMenus() {
        // Menu Utama: Dashboard
        // Callbacknya sekarang diarahkan ke Router::dispatch('slug')
        add_menu_page(
            'Umrah Management', 
            'Umrah Travel', 
            'read', // Capability minimal
            'umroh-dashboard', 
            function() { $this->router->dispatch('umroh-dashboard'); },
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
            function() { $this->router->dispatch('umroh-packages'); }
        );
        
        // Submenu hidden: Add Package
        add_submenu_page(
            null,
            'Tambah Paket',
            'Tambah Paket',
            'manage_options',
            'umroh-packages-add',
            function() { $this->router->dispatch('umroh-packages-add'); }
        );

        // Submenu: Bookings
        add_submenu_page(
            'umroh-dashboard',
            'Data Booking',
            'Bookings',
            'manage_options',
            'umroh-bookings',
            function() { $this->router->dispatch('umroh-bookings'); }
        );

        // Submenu: Data Jemaah (CRM) - Restored
        add_submenu_page(
            'umroh-dashboard',
            'Data Jemaah',
            'Jemaah',
            'manage_options',
            'umroh-jemaah',
            function() { $this->router->dispatch('umroh-jemaah'); }
        );

        // Submenu: Keuangan - Restored
        add_submenu_page(
            'umroh-dashboard',
            'Keuangan',
            'Keuangan',
            'manage_options',
            'umroh-finance',
            function() { $this->router->dispatch('umroh-finance'); }
        );
        
        // Submenu: Master Data - Restored
        add_submenu_page(
            'umroh-dashboard',
            'Master Data',
            'Master Data',
            'manage_options',
            'umroh-master-data',
            function() { $this->router->dispatch('umroh-master-data'); }
        );
    }
    
    public function enqueueAssets() {
        wp_enqueue_style('umroh-admin-css', plugins_url('../../assets/css/admin.css', __DIR__));
    }
}