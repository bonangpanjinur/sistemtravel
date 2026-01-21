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
     */
    public function register()
    {
        // Tempat untuk mendaftarkan binding khusus admin.
    }

    /**
     * Bootstrap backend services.
     */
    public function boot()
    {
        // 1. Setup Admin Menu Routes
        // PERBAIKAN: Membungkus eksekusi router ke dalam hook 'admin_menu'.
        add_action('admin_menu', function () {
            try {
                // Pastikan class Router di-resolve dari container
                if ($this->container->has(Router::class)) {
                    $router = $this->container->get(Router::class);
                    $router->registerRoutes();
                }
            } catch (\Exception $e) {
                error_log('Sistem Travel Error: Gagal memuat router menu admin. ' . $e->getMessage());
            }
        });

        // 2. Enqueue Admin Assets (CSS & JS)
        add_action('admin_enqueue_scripts', function ($hook) {
            // Path diarahkan naik 2 level dari src/Providers ke root plugin
            $pluginRootUrl = plugin_dir_url(dirname(dirname(__DIR__)) . '/umroh-management.php');
            
            wp_enqueue_style(
                'sistem-travel-admin-style',
                $pluginRootUrl . 'assets/css/admin.css',
                [],
                '1.0.0'
            );
        });
    }
}