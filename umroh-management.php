<?php
// File: umroh-management.php
// Location: umroh-management.php

/**
 * Plugin Name: Umroh Management System (Enterprise Edition)
 * Plugin URI: https://example.com/umroh-management
 * Description: Sistem manajemen travel umroh dengan arsitektur PSR-4 dan keamanan audit yang ditingkatkan.
 * Version: 2.4.3
 * Author: bonangpanjinur
 * Text Domain: umroh-management
 */

if (!defined('ABSPATH')) {
    exit;
}

define('UMH_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('UMH_PLUGIN_URL', plugin_dir_url(__FILE__));
define('UMH_VERSION', '2.4.3');

// PSR-4 Autoloader
spl_autoload_register(function ($class) {
    $prefix = 'UmhMgmt\\';
    $base_dir = UMH_PLUGIN_DIR . 'src/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) require $file;
});

// Activation Hook
register_activation_hook(__FILE__, function() {
    global $wpdb;
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    $schemas = \UmhMgmt\Config\DatabaseSchema::get_schema();
    foreach ($schemas as $sql) dbDelta($sql);
    \UmhMgmt\Config\RoleManager::init();
});

class UMH_Management {
    public function __construct() {
        \UmhMgmt\Config\RoleManager::init();
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
            new \UmhMgmt\Controllers\Admin\AgentCommissionController();
            new \UmhMgmt\Controllers\Admin\ManifestController();
            new \UmhMgmt\Controllers\Admin\RoomingListController();
            new \UmhMgmt\Controllers\Admin\VisaController();
            new \UmhMgmt\Controllers\Admin\IntegrationController();
            new \UmhMgmt\Controllers\Admin\ReportController();
            new \UmhMgmt\Controllers\Admin\InventoryScannerController();
        } 
        
        // Frontend Controllers
        new \UmhMgmt\Controllers\Frontend\BookingFormController();
        new \UmhMgmt\Controllers\Frontend\PackageCatalogController();
        new \UmhMgmt\Controllers\Frontend\JemaahDashboardController();
        new \UmhMgmt\Controllers\Frontend\DocumentController();
        new \UmhMgmt\Controllers\Frontend\AgentDashboardController();
        new \UmhMgmt\Controllers\Frontend\PaymentController();
        new \UmhMgmt\Controllers\Frontend\DigitalIdController();
        
        // [NEW] Certificate
        new \UmhMgmt\Controllers\Frontend\CertificateController();
    }
}

new UMH_Management();