<?php
/**
 * Plugin Name: Sistem Travel Umroh Management
 * Plugin URI:  https://bonangpanjinur.com/
 * Description: Sistem manajemen travel umroh lengkap dengan fitur booking, inventori, dan keuangan.
 * Version:     1.0.0
 * Author:      Bonang Panji Nur
 * Author URI:  https://bonangpanjinur.com/
 * Text Domain: sistem-travel
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Define Plugin Constants
define('SISTEM_TRAVEL_PATH', plugin_dir_path(__FILE__));
define('SISTEM_TRAVEL_URL', plugin_dir_url(__FILE__));
define('SISTEM_TRAVEL_VERSION', '1.0.0');

// 1. Coba load Composer autoloader
if (file_exists(SISTEM_TRAVEL_PATH . 'vendor/autoload.php')) {
    require_once SISTEM_TRAVEL_PATH . 'vendor/autoload.php';
}

// 2. Registrasi Custom Autoloader (Cadangan)
spl_autoload_register(function ($class) {
    $prefix = 'SistemTravel\\UmrohManagement\\';
    $base_dir = SISTEM_TRAVEL_PATH . 'src/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    // Menggunakan DIRECTORY_SEPARATOR untuk kompatibilitas path yang lebih baik
    $file = $base_dir . str_replace('\\', DIRECTORY_SEPARATOR, $relative_class) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

use SistemTravel\UmrohManagement\Core\Container;
use SistemTravel\UmrohManagement\Providers\BackendServiceProvider;
use SistemTravel\UmrohManagement\Providers\FrontendServiceProvider;

class SistemTravelInit
{
    private static $instance = null;
    private $container;

    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        // 1. Inisialisasi Container
        $this->init_container();
        
        // 2. Muat Provider
        $this->load_providers();
    }

    private function init_container()
    {
        // FORCE LOAD: Jika autoloader gagal, paksa load file Container secara manual
        if (!class_exists('SistemTravel\\UmrohManagement\\Core\\Container')) {
            $file = SISTEM_TRAVEL_PATH . 'src/Core/Container.php';
            if (file_exists($file)) {
                require_once $file;
            } else {
                // Log error fatal jika file fisik benar-benar hilang
                error_log('Sistem Travel Critical: src/Core/Container.php tidak ditemukan.');
                return;
            }
        }

        // Sekarang aman untuk instansiasi
        if (class_exists('SistemTravel\\UmrohManagement\\Core\\Container')) {
            $this->container = new Container();
        }
    }

    private function load_providers()
    {
        if (!$this->container) {
            return;
        }

        // --- BACKEND (ADMIN) ---
        if (is_admin()) {
            $backendClass = 'SistemTravel\\UmrohManagement\\Providers\\BackendServiceProvider';
            
            // FORCE LOAD: Paksa load file Provider jika autoloader gagal
            if (!class_exists($backendClass)) {
                $file = SISTEM_TRAVEL_PATH . 'src/Providers/BackendServiceProvider.php';
                if (file_exists($file)) {
                    require_once $file;
                }
            }

            if (class_exists($backendClass)) {
                $backend = new BackendServiceProvider($this->container);
                $backend->register();
            } else {
                // Tampilkan pesan error di admin dashboard jika gagal total
                add_action('admin_notices', function() {
                    echo '<div class="notice notice-error"><p><strong>Sistem Travel Error:</strong> Gagal memuat BackendServiceProvider. Periksa folder <code>src/Providers/</code>.</p></div>';
                });
            }
        }

        // --- FRONTEND (PUBLIC) ---
        $frontendClass = 'SistemTravel\\UmrohManagement\\Providers\\FrontendServiceProvider';
        if (!class_exists($frontendClass)) {
             $file = SISTEM_TRAVEL_PATH . 'src/Providers/FrontendServiceProvider.php';
             if (file_exists($file)) {
                 require_once $file;
             }
        }

        if (class_exists($frontendClass)) {
            $frontend = new FrontendServiceProvider($this->container);
            $frontend->register();
        }
    }
}

add_action('plugins_loaded', ['SistemTravelInit', 'get_instance']);

// Aktivasi & Deaktivasi
register_activation_hook(__FILE__, function() {
    // \SistemTravel\UmrohManagement\Config\DatabaseSchema::createTables();
});