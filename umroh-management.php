<?php
/**
 * Plugin Name: Sistem Travel Umrah & Haji
 * Plugin URI: https://travel-umrah.com
 * Description: Sistem Manajemen Travel Umrah Komprehensif (ERP)
 * Version: 1.0.0
 * Author: Tim Developer
 * License: GPLv2 or later
 * Text Domain: travel-sys
 */

// Cegah akses langsung
if (!defined('ABSPATH')) {
    exit;
}

// 1. Definisikan Konstanta Plugin
if (!defined('TRAVEL_SYS_PATH')) {
    define('TRAVEL_SYS_PATH', plugin_dir_path(__FILE__));
}
if (!defined('TRAVEL_SYS_URL')) {
    define('TRAVEL_SYS_URL', plugin_dir_url(__FILE__));
}

// 2. Autoloading Handling (ULTIMATE VERSION)
// Kita load manual file-file inti untuk menghindari masalah autoloader hosting

// List file kritis yang WAJIB diload manual jika autoloader macet
$critical_files = [
    'src/Core/Container.php',
    'src/Providers/BackendServiceProvider.php',
    'src/Providers/FrontendServiceProvider.php'
];

foreach ($critical_files as $file) {
    $filepath = TRAVEL_SYS_PATH . $file;
    if (file_exists($filepath)) {
        require_once $filepath;
    }
}

// Autoloader Standard (Cadangan untuk file controller lain)
if (file_exists(TRAVEL_SYS_PATH . 'vendor/autoload.php')) {
    require_once TRAVEL_SYS_PATH . 'vendor/autoload.php';
}

spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = TRAVEL_SYS_PATH . 'src/';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    
    if (file_exists($file)) {
        require_once $file;
    }
});

use App\Core\Container;
use App\Providers\BackendServiceProvider;
use App\Providers\FrontendServiceProvider;

// 3. Bootstrap Plugin
class TravelSystemPlugin {
    private $container;

    public function __construct() {
        add_action('admin_notices', [$this, 'checkSystemHealth']);

        try {
            // Cek Ketersediaan Class Inti
            if (!class_exists('App\Core\Container')) {
                throw new Exception("CRITICAL: Class App\Core\Container tidak ditemukan. Pastikan file src/Core/Container.php memiliki 'namespace App\Core;'");
            }

            $this->container = new Container();
            $this->initProviders();
            
        } catch (Exception $e) {
            error_log('Travel System Boot Error: ' . $e->getMessage());
            // Simpan error di global untuk ditampilkan di admin notice
            $GLOBALS['travel_sys_boot_error'] = $e->getMessage();
        }
    }

    private function initProviders() {
        // Init Backend (Admin Menu)
        if (class_exists('App\Providers\BackendServiceProvider')) {
            $backend = new BackendServiceProvider($this->container);
            $backend->register();
            $backend->boot(); 
        } else {
             error_log('Travel System: BackendServiceProvider missing.');
        }

        // Init Frontend
        if (class_exists('App\Providers\FrontendServiceProvider')) {
            $frontend = new FrontendServiceProvider($this->container);
            $frontend->register();
            $frontend->boot();
        }
    }

    /**
     * Fitur Diagnostik
     */
    public function checkSystemHealth() {
        if (!current_user_can('activate_plugins')) return;

        // Tampilkan error boot jika ada
        if (isset($GLOBALS['travel_sys_boot_error'])) {
            echo '<div class="notice notice-error"><p><strong>Travel System Error:</strong> ' . esc_html($GLOBALS['travel_sys_boot_error']) . '</p></div>';
        }

        // Cek file fisik
        $missing = [];
        if (!file_exists(TRAVEL_SYS_PATH . 'src/Core/Container.php')) $missing[] = 'src/Core/Container.php';
        if (!file_exists(TRAVEL_SYS_PATH . 'src/Providers/BackendServiceProvider.php')) $missing[] = 'src/Providers/BackendServiceProvider.php';

        if (!empty($missing)) {
             echo '<div class="notice notice-warning"><p>File berikut hilang dari server: ' . implode(', ', $missing) . '</p></div>';
        }
    }
}

// 4. Jalankan Plugin
add_action('plugins_loaded', function() {
    new TravelSystemPlugin();
});