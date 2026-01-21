<?php
/**
 * Plugin Name: Sistem Travel Umroh Management
 * Plugin URI: https://sistemtravel.com
 * Description: Plugin manajemen travel umroh lengkap (Booking, Manifest, Finance, dll)
 * Version: 1.0.0
 * Author: Sistem Travel Team
 * Text Domain: travel-umroh
 */

if (!defined('ABSPATH')) {
    exit;
}

// Autoloader sederhana untuk namespace App
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = plugin_dir_path(__FILE__) . 'src/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

// Autoloader untuk namespace legacy SistemTravel (jika ada file lama)
spl_autoload_register(function ($class) {
    $prefix = 'SistemTravel\\UmrohManagement\\';
    $base_dir = plugin_dir_path(__FILE__) . 'src/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

class SistemTravelInit {
    
    private static $instance = null;
    public $container;

    public function __construct() {
        // Init Container
        // Check if App\Core\Container exists via autoloader
        if (class_exists('App\\Core\\Container')) {
            $this->container = new \App\Core\Container();
        } else {
            // Fallback just in case
             $this->container = new stdClass();
        }
        
        $this->load_providers();
    }

    public static function get_instance() {
        if (self::$instance == null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function load_providers() {
        // Load Backend Provider
        // ERROR HAPPENS HERE IN STACK TRACE #1 calling #0
        // The stack trace says: SistemTravel\UmrohManagement\Providers\BackendServiceProvider->__construct(Object(SistemTravel\UmrohManagement\Core\Container))
        // But the class is namespaced App\Core\Container in src/Core/Container.php
        
        // Let's check how it's instantiated. 
        // Based on stack trace #0, it seems the code here is passing the container to the constructor.
        
        // NOTE: I cannot see the exact content of this file in the "uploaded" list fully until I read it, but I see the stack trace.
        // Stack trace says: #0 ... BackendServiceProvider->__construct(Object(SistemTravel\UmrohManagement\Core\Container))
        // This means the object passed IS of type SistemTravel\UmrohManagement\Core\Container.
        // But src/Core/Container.php has `namespace App\Core;`
        
        // Ah, maybe the user has TWO Container.php files? Or the autoloader is mapping `SistemTravel\UmrohManagement` to `src/` as well?
        // Yes, see the second autoloader above. `SistemTravel\UmrohManagement` -> `src/`.
        // `App` -> `src/`.
        
        // So `src/Core/Container.php` can be loaded as `App\Core\Container` OR `SistemTravel\UmrohManagement\Core\Container` depending on how it's called, BUT the file itself declares `namespace App\Core;`.
        // If the file declares `namespace App\Core;`, then `new SistemTravel\UmrohManagement\Core\Container()` would fail unless there is a class alias or another file.
        
        // Wait, if the file `src/Core/Container.php` says `namespace App\Core;`, then `SistemTravel\UmrohManagement\Core\Container` does not exist unless defined elsewhere.
        // However, the stack trace explicitly says `Object(SistemTravel\UmrohManagement\Core\Container)`. This implies PHP *thinks* that class exists and has an instance of it.
        
        // Let's look at `src/Providers/BackendServiceProvider.php` again.
        
        // The error is `Class 'App\Core\Container' not found` in `src/Providers/BackendServiceProvider.php:32`.
        
        // If `umroh-management.php` instantiates `BackendServiceProvider` passing a container, the constructor in `BackendServiceProvider` must match.
        
        $backend = new \SistemTravel\UmrohManagement\Providers\BackendServiceProvider($this->container);
        $backend->register();

        // Load Frontend Provider
        $frontend = new \SistemTravel\UmrohManagement\Providers\FrontendServiceProvider();
        $frontend->register();
    }
}

add_action('plugins_loaded', array('SistemTravelInit', 'get_instance'));