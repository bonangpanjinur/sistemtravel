<?php
/*
Plugin Name: Sistem Manajemen Travel Umrah
Description: Sistem manajemen travel umrah lengkap dengan CRM, Keuangan, dan Operasional.
Version: 2.0.0
Author: Bonang Panji Nur
*/

// Cegah akses langsung
if (!defined('ABSPATH')) {
    exit;
}

// 1. Autoload (Jika pakai composer)
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
} else {
    // Fallback: Manual include jika belum setup composer autoloader
    // (Sebaiknya gunakan `composer dump-autoload` nanti)
    require_once __DIR__ . '/src/Core/Container.php';
    require_once __DIR__ . '/src/Interfaces/DatabaseInterface.php';
    require_once __DIR__ . '/src/Core/WordPressDatabaseAdapter.php';
    // ... Anda mungkin perlu include file lain manual di sini jika belum pakai composer
}

use UmrahManagement\Core\Container;
use UmrahManagement\Core\WordPressDatabaseAdapter;
use UmrahManagement\Interfaces\DatabaseInterface;
use UmrahManagement\Providers\BackendServiceProvider;
use UmrahManagement\Providers\FrontendServiceProvider;

class UmrohManagementPlugin {
    private $container;

    public function __construct() {
        $this->initContainer();
        $this->initProviders();
    }

    private function initContainer() {
        $this->container = new Container();

        // Binding Interface Database ke Adapter WordPress
        // Ini adalah kunci agar semua Repository otomatis dapat DB tanpa global $wpdb
        $this->container->singleton(DatabaseInterface::class, function() {
            return new WordPressDatabaseAdapter();
        });

        // Binding Class lain jika perlu (biasanya auto-wiring sudah cukup)
    }

    private function initProviders() {
        // Inisialisasi Backend (Admin Area)
        if (is_admin()) {
            $backend = new BackendServiceProvider($this->container);
            $backend->register();
        }

        // Inisialisasi Frontend (Public Area)
        $frontend = new FrontendServiceProvider($this->container);
        $frontend->register();
    }
}

// Jalankan Plugin
function run_umroh_management() {
    new UmrohManagementPlugin();
}

add_action('plugins_loaded', 'run_umroh_management');