<?php

namespace SistemTravel\Core;

class Router
{
    private $container;
    private $routes;

    public function __construct(Container $container, array $config = [])
    {
        $this->container = $container;
        
        // Coba ambil routes dari config yang di-inject
        $this->routes = $config['admin_menu'] ?? [];

        // --- PERBAIKAN & DIAGNOSTIK ---
        // Jika routes kosong, kemungkinan Container gagal inject config.
        // Kita lakukan pemuatan manual (Fallback) agar menu tetap muncul.
        if (empty($this->routes)) {
            $fallbackPath = dirname(__DIR__) . '/Config/routes.php';
            
            if (file_exists($fallbackPath)) {
                $manualConfig = require $fallbackPath;
                $this->routes = $manualConfig['admin_menu'] ?? [];
                // Opsional: Aktifkan log ini jika perlu debugging
                // error_log('[SistemTravel] NOTICE: Router memuat config secara manual (Fallback).');
            } else {
                error_log('[SistemTravel] CRITICAL: File Config/routes.php tidak ditemukan!');
            }
        }
    }

    public function registerRoutes()
    {
        // Pastikan ada routes untuk diproses
        if (empty($this->routes)) {
            return;
        }

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
                        $route['menu_slug'], // Parent slug harus sama dengan menu utama
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
            return function() { 
                echo '<div class="notice notice-error"><p>Sistem Travel Error: Konfigurasi callback menu tidak valid.</p></div>'; 
            };
        }

        // Return Closure untuk eksekusi nanti (lazy execution)
        return function () use ($callbackConfig) {
            $controllerClass = $callbackConfig[0];
            $method = $callbackConfig[1];

            // 1. Coba resolve dari Container
            if ($this->container->has($controllerClass)) {
                $controller = $this->container->get($controllerClass);
            } 
            // 2. Fallback: Instosiasi manual jika class ada tapi tidak di container
            elseif (class_exists($controllerClass)) {
                $controller = new $controllerClass();
            } 
            // 3. Error jika class tidak ditemukan
            else {
                echo "<div class='notice notice-error'><p>Error: Controller <code>{$controllerClass}</code> tidak ditemukan.</p></div>";
                return;
            }

            // Cek method availability
            if (!method_exists($controller, $method)) {
                echo "<div class='notice notice-error'><p>Error: Method <code>{$method}</code> tidak ada di controller.</p></div>";
                return;
            }

            // Jalankan method
            return call_user_func([$controller, $method]);
        };
    }
}