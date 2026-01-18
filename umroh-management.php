<?php
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
    // Pastikan class DatabaseSchema ada sebelum dipanggil
    if (class_exists('\UmhMgmt\Config\DatabaseSchema')) {
        $schemas = \UmhMgmt\Config\DatabaseSchema::get_schema();
        foreach ($schemas as $sql) dbDelta($sql);
    }
    if (class_exists('\UmhMgmt\Config\RoleManager')) {
        \UmhMgmt\Config\RoleManager::init();
    }
});

class UMH_Management {
    public function __construct() {
        if (class_exists('\UmhMgmt\Config\RoleManager')) {
            \UmhMgmt\Config\RoleManager::init();
        }
        
        // Load Core Services
        if (class_exists('\UmhMgmt\Services\NotificationService')) {
            new \UmhMgmt\Services\NotificationService();
        }
        
        // Init Controllers
        $this->init_controllers();
    }

    private function init_controllers() {
        // Helper function untuk init class dengan aman
        $load = function($class) {
            if (class_exists($class)) {
                new $class();
            }
        };

        // --- Admin Controllers ---
        if (is_admin()) {
            $load('\UmhMgmt\Controllers\Admin\DashboardController');
            $load('\UmhMgmt\Controllers\Admin\MasterDataController');
            $load('\UmhMgmt\Controllers\Admin\PackageController');
            $load('\UmhMgmt\Controllers\Admin\DepartureController'); 
            $load('\UmhMgmt\Controllers\Admin\BookingController');
            $load('\UmhMgmt\Controllers\Admin\FinanceController');
            $load('\UmhMgmt\Controllers\Admin\CRMController');
            $load('\UmhMgmt\Controllers\Admin\SavingsPlanController');
            $load('\UmhMgmt\Controllers\Admin\EmployeeController');
            $load('\UmhMgmt\Controllers\Admin\BranchController');
            $load('\UmhMgmt\Controllers\Admin\OperationalController');
            $load('\UmhMgmt\Controllers\Admin\AgentsHRController');
            $load('\UmhMgmt\Controllers\Admin\SpecialServicesController');
            $load('\UmhMgmt\Controllers\Admin\CustomerCareController');
            $load('\UmhMgmt\Controllers\Admin\AgentCommissionController');
            $load('\UmhMgmt\Controllers\Admin\ManifestController');
            $load('\UmhMgmt\Controllers\Admin\RoomingListController');
            $load('\UmhMgmt\Controllers\Admin\VisaController');
            $load('\UmhMgmt\Controllers\Admin\IntegrationController');
            $load('\UmhMgmt\Controllers\Admin\ReportController');
            $load('\UmhMgmt\Controllers\Admin\InventoryScannerController');
            
            // [NEW] Settings Controller (Pusat Pengaturan)
            $load('\UmhMgmt\Controllers\Admin\SettingsController');
        } 
        
        // --- Frontend Controllers ---
        $load('\UmhMgmt\Controllers\Frontend\BookingFormController');
        $load('\UmhMgmt\Controllers\Frontend\PackageCatalogController');
        $load('\UmhMgmt\Controllers\Frontend\JemaahDashboardController');
        $load('\UmhMgmt\Controllers\Frontend\DocumentController');
        $load('\UmhMgmt\Controllers\Frontend\AgentDashboardController');
        $load('\UmhMgmt\Controllers\Frontend\PaymentController');
        $load('\UmhMgmt\Controllers\Frontend\DigitalIdController');
        $load('\UmhMgmt\Controllers\Frontend\CertificateController');
        
        // [NEW] Payment Gateway Callback Handler (Webhook)
        // Load dengan aman. Jika file belum ada, website tidak akan crash.
        $load('\UmhMgmt\Controllers\Frontend\PaymentCallbackController');
    }
}

new UMH_Management();