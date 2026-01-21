<?php

namespace SistemTravel\Core;

class Router
{
    private $container;
    private $routes;

    public function __construct(Container $container, array $config)
    {
        $this->container = $container;
        // Asumsi config array memiliki key 'admin_menu', sesuaikan dengan struktur routes.php Anda
        $this->routes = $config['admin_menu'] ?? [];
    }

    public function registerRoutes()
    {
        foreach ($this->routes as $route) {
            // 1. Siapkan Callback untuk Menu Utama
            $mainCallback = $this->resolveCallback($route['callback']);

            // 2. Daftarkan Menu Utama
            add_menu_page(
                $route['page_title'],
                $route['menu_title'],
                $route['capability'],
                $route['menu_slug'],
                $mainCallback,
                $route['icon_url'] ?? '',
                $route['position'] ?? null
            );

            // 3. Proses Submenu (jika ada)
            if (isset($route['submenu']) && is_array($route['submenu'])) {
                foreach ($route['submenu'] as $submenu) {
                    $submenuCallback = $this->resolveCallback($submenu['callback']);

                    add_submenu_page(
                        $route['menu_slug'], // Parent slug
                        $submenu['page_title'],
                        $submenu['menu_title'],
                        $submenu['capability'],
                        $submenu['menu_slug'],
                        $submenuCallback
                    );
                }
            }
        }
    }

    /**
     * Helper untuk mengubah definisi controller [Class, Method] menjadi Closure yang executable.
     */
    private function resolveCallback($callbackConfig)
    {
        // Validasi format callback
        if (!is_array($callbackConfig) || count($callbackConfig) < 2) {
            return function() { echo "Konfigurasi callback menu salah."; };
        }

        // Return Closure
        return function () use ($callbackConfig) {
            $controllerClass = $callbackConfig[0];
            $method = $callbackConfig[1];

            // Cek apakah class ada di container
            if ($this->container->has($controllerClass)) {
                $controller = $this->container->get($controllerClass);
            } else {
                // Fallback: coba instosiasi manual jika tidak terdaftar di container (opsional)
                $controller = new $controllerClass();
            }

            // Jalankan method
            return call_user_func([$controller, $method]);
        };
    }
}