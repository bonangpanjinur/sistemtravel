<?php

namespace App\Providers;

use App\Core\Container;
use App\Controllers\Admin\DashboardController;
use App\Controllers\Admin\PackageController;
use App\Controllers\Admin\BookingController;
use App\Controllers\Admin\LeadController;
use App\Controllers\Admin\FinanceController;
use App\Controllers\Admin\SavingsController;
use App\Controllers\Admin\SavingsPlanController;
use App\Controllers\Admin\AgentsHRController;
use App\Controllers\Admin\AgentCommissionController;
use App\Controllers\Admin\EmployeeController;
use App\Controllers\Admin\BranchController;
use App\Controllers\Admin\ReportController;
use App\Controllers\Admin\SettingsController;
use App\Controllers\Admin\IntegrationController;
use App\Controllers\Admin\MasterDataController;
use App\Controllers\Admin\InventoryScannerController;
use App\Controllers\Admin\SpecialServicesController;
use App\Controllers\Admin\OperationalController;
use App\Controllers\Admin\DepartureController;
use App\Controllers\Admin\ManifestController;
use App\Controllers\Admin\RoomingListController;
use App\Controllers\Admin\VisaController;

class BackendServiceProvider
{
    private $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function register()
    {
        // Container setup
    }

    public function boot()
    {
        add_action('admin_menu', [$this, 'registerAdminMenus']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminAssets']);
    }

    public function enqueueAdminAssets()
    {
        // Pastikan path ini benar relatif terhadap file plugin utama
        // dirname(__DIR__, 2) naik 2 level dari src/Providers ke root plugin
        wp_enqueue_style(
            'travel-sys-admin', 
            plugins_url('assets/css/admin.css', dirname(__DIR__, 2) . '/umroh-management.php'), 
            [], 
            '1.0.0'
        );
    }

    /**
     * Helper untuk memanggil controller secara aman.
     * Mengembalikan Closure yang valid untuk callback WordPress.
     */
    private function createCallback($controllerClass, $method = 'index')
    {
        // Kita bind $this secara eksplisit agar container bisa diakses
        return function() use ($controllerClass, $method) {
            // Cek apakah class ada di container atau autoload
            if (!class_exists($controllerClass)) {
                echo "<div class='notice notice-error'><p>Error: Controller <code>{$controllerClass}</code> tidak ditemukan.</p></div>";
                return;
            }

            try {
                $controller = $this->container->get($controllerClass);
                if (method_exists($controller, $method)) {
                    $controller->$method();
                } else {
                    echo "<div class='notice notice-error'><p>Error: Method <code>{$method}</code> tidak ditemukan di <code>{$controllerClass}</code>.</p></div>";
                }
            } catch (\Exception $e) {
                echo "<div class='notice notice-error'><p>System Error: " . $e->getMessage() . "</p></div>";
            }
        };
    }

    public function registerAdminMenus()
    {
        $capability = 'manage_options';
        $slug_prefix = 'travel-sys';

        // 1. MENU UTAMA: TRAVEL SYSTEM
        // POSISI DIUBAH KE 30 AGAR TIDAK KONFLIK DENGAN DASHBOARD BAWAAN WP
        add_menu_page(
            'Travel Management',    // Page Title
            'Sistem Travel',        // Menu Title
            $capability,            // Capability
            $slug_prefix . '-dashboard', // Menu Slug
            $this->createCallback(DashboardController::class), // Callback
            'dashicons-airplane',   // Icon
            30                      // Position (30 = Middle sidebar)
        );

        // Submenu: Dashboard (Harus sama slugnya dengan parent agar jadi default)
        add_submenu_page(
            $slug_prefix . '-dashboard',
            'Dashboard',
            'Dashboard',
            $capability,
            $slug_prefix . '-dashboard',
            $this->createCallback(DashboardController::class)
        );

        // ----------------------------------------------------------------
        // GROUP: PRODUK & LAYANAN
        // ----------------------------------------------------------------
        
        add_submenu_page(
            $slug_prefix . '-dashboard',
            'Manajemen Paket',
            'Paket Travel',
            $capability,
            $slug_prefix . '-packages',
            $this->createCallback(PackageController::class)
        );

        add_submenu_page(
            $slug_prefix . '-dashboard',
            'Layanan Tambahan',
            'Layanan Tambahan',
            $capability,
            $slug_prefix . '-special-services',
            $this->createCallback(SpecialServicesController::class)
        );

        // ----------------------------------------------------------------
        // GROUP: PENJUALAN (SALES & CRM)
        // ----------------------------------------------------------------

        add_submenu_page(
            $slug_prefix . '-dashboard',
            'Data Booking',
            'Transaksi Booking',
            $capability,
            $slug_prefix . '-bookings',
            $this->createCallback(BookingController::class)
        );

        add_submenu_page(
            $slug_prefix . '-dashboard',
            'Leads / Prospek',
            'Leads (CRM)',
            $capability,
            $slug_prefix . '-leads',
            $this->createCallback(LeadController::class)
        );

        // ----------------------------------------------------------------
        // GROUP: KEUANGAN (FINANCE & SAVINGS)
        // ----------------------------------------------------------------

        add_submenu_page(
            $slug_prefix . '-dashboard',
            'Keuangan & Invoicing',
            '-- Keuangan --', 
            $capability,
            $slug_prefix . '-finance',
            $this->createCallback(FinanceController::class)
        );

        add_submenu_page(
            $slug_prefix . '-dashboard',
            'Tabungan Umrah',
            'Tabungan Jamaah',
            $capability,
            $slug_prefix . '-savings',
            $this->createCallback(SavingsController::class)
        );

        add_submenu_page(
            $slug_prefix . '-dashboard',
            'Rencana Tabungan',
            'Setup Paket Tabungan',
            $capability,
            $slug_prefix . '-savings-plan',
            $this->createCallback(SavingsPlanController::class)
        );

        // ----------------------------------------------------------------
        // GROUP: OPERASIONAL (OPERATIONS)
        // ----------------------------------------------------------------
        
        add_submenu_page(
            $slug_prefix . '-dashboard',
            'Operasional Umum',
            '-- Operasional --', 
            $capability,
            $slug_prefix . '-operational',
            $this->createCallback(OperationalController::class)
        );

        add_submenu_page(
            $slug_prefix . '-dashboard',
            'Jadwal Keberangkatan',
            'Keberangkatan',
            $capability,
            $slug_prefix . '-departures',
            $this->createCallback(DepartureController::class)
        );

        add_submenu_page(
            $slug_prefix . '-dashboard',
            'Manifest Penerbangan',
            'Manifest',
            $capability,
            $slug_prefix . '-manifest',
            $this->createCallback(ManifestController::class)
        );

        add_submenu_page(
            $slug_prefix . '-dashboard',
            'Rooming List Hotel',
            'Rooming List',
            $capability,
            $slug_prefix . '-rooming',
            $this->createCallback(RoomingListController::class)
        );

        add_submenu_page(
            $slug_prefix . '-dashboard',
            'Handling Visa',
            'Visa Handling',
            $capability,
            $slug_prefix . '-visa',
            $this->createCallback(VisaController::class)
        );

        add_submenu_page(
            $slug_prefix . '-dashboard',
            'Inventory Scanner',
            'Scan Perlengkapan',
            $capability,
            $slug_prefix . '-inventory',
            $this->createCallback(InventoryScannerController::class)
        );

        // ----------------------------------------------------------------
        // GROUP: SDM & CABANG
        // ----------------------------------------------------------------

        add_submenu_page(
            $slug_prefix . '-dashboard',
            'Data Cabang',
            '-- Cabang & SDM --', 
            $capability,
            $slug_prefix . '-branches',
            $this->createCallback(BranchController::class)
        );

        add_submenu_page(
            $slug_prefix . '-dashboard',
            'Data Agen & Mitra',
            'Agen & Mitra',
            $capability,
            $slug_prefix . '-agents',
            $this->createCallback(AgentsHRController::class)
        );

        add_submenu_page(
            $slug_prefix . '-dashboard',
            'Komisi Agen',
            'Komisi Agen',
            $capability,
            $slug_prefix . '-commissions',
            $this->createCallback(AgentCommissionController::class)
        );

        add_submenu_page(
            $slug_prefix . '-dashboard',
            'Data Karyawan',
            'Data Karyawan',
            $capability,
            $slug_prefix . '-employees',
            $this->createCallback(EmployeeController::class)
        );

        // ----------------------------------------------------------------
        // GROUP: SYSTEM & SETTINGS
        // ----------------------------------------------------------------

        add_submenu_page(
            $slug_prefix . '-dashboard',
            'Laporan Lengkap',
            '-- Laporan & Sistem --',
            $capability,
            $slug_prefix . '-reports',
            $this->createCallback(ReportController::class)
        );

        add_submenu_page(
            $slug_prefix . '-dashboard',
            'Master Data',
            'Master Data',
            $capability,
            $slug_prefix . '-master-data',
            $this->createCallback(MasterDataController::class)
        );

        add_submenu_page(
            $slug_prefix . '-dashboard',
            'Integrasi API',
            'Integrasi API',
            $capability,
            $slug_prefix . '-integrations',
            $this->createCallback(IntegrationController::class)
        );

        add_submenu_page(
            $slug_prefix . '-dashboard',
            'Pengaturan Sistem',
            'Pengaturan',
            $capability,
            $slug_prefix . '-settings',
            $this->createCallback(SettingsController::class)
        );
    }
}