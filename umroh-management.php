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

// --- 1. Autoloading (Mekanisme Pemuatan File Otomatis) ---

// Opsi A: Jika menggunakan Composer (Best Practice)
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
} 

// Opsi B: Fallback Manual (PENTING untuk mengatasi error 'Class not found' jika composer belum jalan)
// Kode ini memberitahu PHP cara mencari file di dalam folder src/ secara manual
spl_autoload_register(function ($class) {
    // Prefix namespace plugin kita
    $prefix = 'UmrahManagement\\';
    
    // Folder dasar tempat file kode berada (folder src)
    $base_dir = __DIR__ . '/src/';

    // Cek apakah class yang dipanggil menggunakan prefix kita
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return; // Bukan class plugin ini, biarkan PHP mencari di tempat lain
    }

    // Ambil nama class relatif (membuang prefix)
    $relative_class = substr($class, $len);

    // Ubah format namespace menjadi path file (ganti backslash \ jadi slash /)
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    // Jika filenya ada, muat file tersebut
    if (file_exists($file)) {
        require_once $file;
    }
});

// Import Class yang dibutuhkan
use UmrahManagement\Core\Container;
use UmrahManagement\Core\WordPressDatabaseAdapter;
use UmrahManagement\Interfaces\DatabaseInterface;
use UmrahManagement\Providers\BackendServiceProvider;
use UmrahManagement\Providers\FrontendServiceProvider;
use UmrahManagement\Config\DatabaseSchema;

class UmrohManagementPlugin {
    private $container;

    public function __construct() {
        $this->initContainer();
        $this->initProviders();
        
        // Hook untuk aktivasi (dijalankan saat plugin diaktifkan)
        register_activation_hook(__FILE__, ['UmrahManagement\Config\DatabaseSchema', 'createTables']);
    }

    private function initContainer() {
        $this->container = new Container();

        // Hubungkan Interface Database ke Adapter WordPress
        // Ini agar kita tidak perlu pakai 'global $wpdb' lagi di file lain
        $this->container->singleton(DatabaseInterface::class, function() {
            return new WordPressDatabaseAdapter();
        });
    }

    public function initProviders() {
        // Inisialisasi Backend (Halaman Admin)
        if (is_admin()) {
            // Pastikan class BackendServiceProvider ada sebelum dipanggil
            if (class_exists(BackendServiceProvider::class)) {
                $backend = new BackendServiceProvider($this->container);
                $backend->register();
            }
        }

        // Inisialisasi Frontend (Halaman Pengunjung)
        if (class_exists(FrontendServiceProvider::class)) {
            $frontend = new FrontendServiceProvider($this->container);
            $frontend->register();
        }
    }
}

// Jalankan Plugin
function run_umroh_management() {
    new UmrohManagementPlugin();
}

add_action('plugins_loaded', 'run_umroh_management');