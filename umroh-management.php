<?php
/**
 * Plugin Name: Umroh Management System (Full Enterprise)
 * Plugin URI: https://example.com/umroh-management
 * Description: Sistem manajemen travel umroh End-to-End mencakup Marketing, Sales, Operasional, Keuangan, HR, hingga Customer Care.
 * Version: 1.0.0
 * Author: Manus AI
 * Text Domain: umroh-management
 */

if (!defined('ABSPATH')) {
    exit;
}

define('UMH_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('UMH_PLUGIN_URL', plugin_dir_url(__FILE__));
define('UMH_VERSION', '1.0.0');

// Include core classes
require_once UMH_PLUGIN_DIR . 'includes/class-umh-activator.php';
require_once UMH_PLUGIN_DIR . 'includes/class-umh-deactivator.php';
require_once UMH_PLUGIN_DIR . 'includes/class-umh-db.php';

/**
 * The code that runs during plugin activation.
 */
function activate_umh_management() {
    require_once UMH_PLUGIN_DIR . 'includes/class-umh-activator.php';
    UMH_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_umh_management() {
    require_once UMH_PLUGIN_DIR . 'includes/class-umh-deactivator.php';
    UMH_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_umh_management');
register_deactivation_hook(__FILE__, 'deactivate_umh_management');

/**
 * Initialize the plugin
 */
class UMH_Management {
    public function __construct() {
        $this->load_dependencies();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    private function load_dependencies() {
        require_once UMH_PLUGIN_DIR . 'includes/class-umh-api.php';
    }

    public function run() {
        new UMH_API();
    }

    private function define_admin_hooks() {
        require_once UMH_PLUGIN_DIR . 'admin/class-umh-admin.php';
        $admin = new UMH_Admin();
    }

    private function define_public_hooks() {
        // Public hooks
    }

    public function run() {
        // Run the plugin
    }
}

function run_umh_management() {
    $plugin = new UMH_Management();
    $plugin->run();
}

run_umh_management();
