<?php

namespace SistemTravel\Providers;

use SistemTravel\Core\Container;
use SistemTravel\Core\Router;

class BackendServiceProvider
{
    /**
     * @var Container
     */
    private $container;

    /**
     * BackendServiceProvider constructor.
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Register backend services in the container.
     * Digunakan untuk binding interface ke implementasi spesifik admin 
     * jika tidak tercover oleh autowiring.
     */
    public function register()
    {
        // Tempat untuk mendaftarkan binding khusus admin.
        // Contoh: $this->container->bind(AdminInterface::class, AdminImplementation::class);
    }

    /**
     * Bootstrap backend services.
     * Dijalankan setelah semua service terdaftar.
     */
    public function boot()
    {
        // 1. Setup Admin Menu Routes
        // PERBAIKAN: Membungkus eksekusi router ke dalam hook 'admin_menu'.
        // Ini memastikan WordPress sudah siap menerima pendaftaran menu (add_menu_page).
        add_action('admin_menu', function () {
            try {
                // Pastikan class Router di-resolve dari container agar dependency injection berjalan
                if ($this->container->has(Router::class)) {
                    $router = $this->container->get(Router::class);
                    $router->registerRoutes();
                }
            } catch (\Exception $e) {
                // Logging error jika gagal memuat router agar tidak mematikan situs
                error_log('Sistem Travel Error: Gagal memuat router menu admin. ' . $e->getMessage());
            }
        });

        // 2. Enqueue Admin Assets (CSS & JS)
        add_action('admin_enqueue_scripts', function ($hook) {
            // Memuat file CSS khusus admin panel
            // Path diarahkan naik 2 level dari src/Providers ke root plugin
            $pluginRootUrl = plugin_dir_url(dirname(dirname(__DIR__)) . '/umroh-management.php');
            
            wp_enqueue_style(
                'sistem-travel-admin-style',
                $pluginRootUrl . 'assets/css/admin.css',
                [],
                '1.0.0'
            );

            // Opsional: Load JS khusus jika berada di halaman plugin ini
            // if (strpos($hook, 'sistem-travel') !== false) {
            //     wp_enqueue_script(...);
            // }
        });
    }
}