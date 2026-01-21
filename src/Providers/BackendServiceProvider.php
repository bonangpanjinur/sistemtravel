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
        // PENTING: Binding Router dengan Config-nya agar Dependency Injection berhasil
        // Jika ini tidak ada, Router akan mengandalkan logika Fallback yang kita buat di atas
        $this->container->singleton(Router::class, function($c) {
            $configPath = dirname(dirname(__DIR__)) . '/src/Config/routes.php';
            $config = file_exists($configPath) ? require $configPath : [];
            return new Router($c, $config);
        });
    }

    /**
     * Bootstrap backend services.
     */
    public function boot()
    {
        // 1. Setup Admin Menu Routes
        // Hook 'admin_menu' wajib digunakan untuk add_menu_page
        add_action('admin_menu', function () {
            try {
                if ($this->container->has(Router::class)) {
                    $router = $this->container->get(Router::class);
                    $router->registerRoutes();
                } else {
                    // Fallback jika binding register() di atas gagal/tidak jalan
                    // Kita load manual
                    $router = new Router($this->container, []); 
                    $router->registerRoutes();
                }
            } catch (\Exception $e) {
                error_log('Sistem Travel Error: ' . $e->getMessage());
            }
        });

        // 2. Enqueue Admin Assets
        add_action('admin_enqueue_scripts', function () {
            $pluginRootUrl = plugin_dir_url(dirname(dirname(__DIR__)) . '/umroh-management.php');
            wp_enqueue_style(
                'sistem-travel-admin',
                $pluginRootUrl . 'assets/css/admin.css',
                [],
                '1.0.0'
            );
        });
    }
}