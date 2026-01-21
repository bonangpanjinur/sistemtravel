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
        $this->container->singleton(Router::class, function($c) {
            // Arahkan ke file src/Config/routes.php
            // __DIR__ = src/Providers
            // dirname(__DIR__) = src
            // dirname(dirname(__DIR__)) = root plugin
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
        // Gunakan hook 'admin_menu' untuk memastikan WordPress siap
        add_action('admin_menu', function () {
            try {
                // Cek apakah Router ada di container
                if ($this->container->has(Router::class)) {
                    $router = $this->container->get(Router::class);
                    $router->registerRoutes();
                } else {
                    // Fallback manual jika container gagal
                    $router = new Router($this->container, []); 
                    $router->registerRoutes();
                }
            } catch (\Exception $e) {
                // Catat error ke log tapi jangan hentikan eksekusi
                error_log('Sistem Travel Error (Menu): ' . $e->getMessage());
            }
        });

        // 2. Enqueue Admin Assets
        add_action('admin_enqueue_scripts', function () {
            // Arahkan ke root plugin untuk URL assets
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