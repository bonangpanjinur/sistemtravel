<?php
// Path: src/Core/Router.php

namespace App\Core;

class Router {
    private $container;
    private $routes;

    public function __construct(Container $container) {
        $this->container = $container;
        $this->routes = require plugin_dir_path(dirname(__DIR__)) . 'src/Config/routes.php';
    }

    /**
     * Dispatch Admin Menu Page
     * Dipanggil saat user membuka halaman admin menu.
     */
    public function dispatch($pageSlug) {
        // Sanitasi page slug
        $pageSlug = sanitize_text_field($pageSlug);

        if (!isset($this->routes[$pageSlug])) {
            echo '<div class="notice notice-error"><p>Halaman tidak ditemukan (Route 404).</p></div>';
            return;
        }

        $route = $this->routes[$pageSlug];
        
        // Cek Capability (Permission)
        $capability = $route['capability'] ?? 'manage_options';
        if (!current_user_can($capability)) {
            wp_die('Anda tidak memiliki izin untuk mengakses halaman ini.');
        }

        $method = $route['method'] ?? 'index';
        $this->execute($route['controller'], $method);
    }

    /**
     * Dispatch POST Action (admin-post.php)
     */
    public function dispatchAction($action) {
        if (!isset($this->routes['actions'][$action])) {
            return; // Action tidak dikenal, biarkan WordPress handle atau ignore
        }

        $route = $this->routes['actions'][$action];
        $this->execute($route['controller'], $route['method']);
    }

    private function execute($controllerClass, $method) {
        try {
            // Gunakan Container untuk resolve controller (Auto-wiring dependency)
            $controller = $this->container->get($controllerClass);
            
            if (!method_exists($controller, $method)) {
                throw new \Exception("Method $method tidak ditemukan di controller $controllerClass");
            }

            // Panggil method controller
            $controller->$method();

        } catch (\Exception $e) {
            // Log error menggunakan LoggerService (jika ada) atau error_log standar
            error_log("Router Error: " . $e->getMessage());
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                wp_die("System Error: " . $e->getMessage());
            } else {
                wp_die("Terjadi kesalahan sistem. Silakan hubungi administrator.");
            }
        }
    }
}