<?php
// File: umroh-management.php
// Location: umroh-management.php

/**
 * Plugin Name: Umroh Management System (Enterprise Edition)
 * Plugin URI: https://example.com/umroh-management
 * Description: Sistem manajemen travel umroh dengan arsitektur PSR-4 dan keamanan audit yang ditingkatkan.
 * Version: 2.3.0
 * Author: bonangpanjinur
 * Text Domain: umroh-management
 */

if (!defined('ABSPATH')) {
    exit;
}

define('UMH_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('UMH_PLUGIN_URL', plugin_dir_url(__FILE__));
define('UMH_VERSION', '2.3.0');

// Simple PSR-4 Autoloader
spl_autoload_register(function ($class) {
    $prefix = 'UmhMgmt\\';
    $base_dir = UMH_PLUGIN_DIR . 'src/';

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

/**
 * The code that runs during plugin activation.
 */
function activate_umh_management() {
    global $wpdb;
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    
    $schemas = \UmhMgmt\Config\DatabaseSchema::get_schema();
    foreach ($schemas as $sql) {
        dbDelta($sql);
    }
    
    // Init Roles saat aktivasi
    \UmhMgmt\Config\RoleManager::init();
}
register_activation_hook(__FILE__, 'activate_umh_management');

/**
 * Initialize the plugin
 */
class UMH_Management {
    public function __construct() {
        \UmhMgmt\Config\RoleManager::init();
        
        // Init Services (Listeners)
        new \UmhMgmt\Services\NotificationService();
        
        $this->init_controllers();
    }

    private function init_controllers() {
        if (is_admin()) {
            new \UmhMgmt\Controllers\Admin\DashboardController();
            new \UmhMgmt\Controllers\Admin\MasterDataController();
            new \UmhMgmt\Controllers\Admin\PackageController();
            new \UmhMgmt\Controllers\Admin\DepartureController(); 
            new \UmhMgmt\Controllers\Admin\BookingController();
            new \UmhMgmt\Controllers\Admin\FinanceController();
            new \UmhMgmt\Controllers\Admin\CRMController();
            new \UmhMgmt\Controllers\Admin\SavingsPlanController();
            new \UmhMgmt\Controllers\Admin\EmployeeController();
            new \UmhMgmt\Controllers\Admin\BranchController();
            new \UmhMgmt\Controllers\Admin\OperationalController();
            new \UmhMgmt\Controllers\Admin\AgentsHRController();
            new \UmhMgmt\Controllers\Admin\SpecialServicesController();
            new \UmhMgmt\Controllers\Admin\CustomerCareController();
        } 
        
        // Frontend Controllers
        new \UmhMgmt\Controllers\Frontend\BookingFormController();
        new \UmhMgmt\Controllers\Frontend\PackageCatalogController();
        // [NEW] Dashboard Area untuk Jemaah
        new \UmhMgmt\Controllers\Frontend\JemaahDashboardController();
    }
}

function run_umh_management() {
    new UMH_Management();
}
run_umh_management();