<?php

namespace App\Providers;

use App\Core\Container;
// Import Controllers
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
        // Tempat registrasi binding container jika perlu
    }

    public function boot()
    {
        // Pastikan hook ini berjalan
        add_action('admin_menu', [$this, 'registerAdminMenus'], 99); // Priority 99 biar belakangan
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminAssets']);
    }

    public function enqueueAdminAssets()
    {
        // Aset Admin
    }

    /**
     * Helper sederhana untuk dispatch controller
     */
    public function dispatch($controllerClass, $method = 'index')
    {
        if (!class_exists($controllerClass)) {
            echo "<div class='notice notice-error'><p>Class $controllerClass not found.</p></div>";
            return;
        }
        
        try {
            // Gunakan container untuk resolve dependency
            $controller = $this->container->get($controllerClass);
            if (method_exists($controller, $method)) {
                $controller->$method();
            } else {
                echo "<div class='notice notice-error'><p>Method $method missing in $controllerClass</p></div>";
            }
        } catch (\Exception $e) {
            echo "<div class='notice notice-error'><p>Error: " . $e->getMessage() . "</p></div>";
        }
    }

    public function registerAdminMenus()
    {
        $capability = 'manage_options';
        $slug_prefix = 'travel-sys';

        // --- MENU UTAMA ---
        add_menu_page(
            'Sistem Travel',
            'Sistem Travel',
            $capability,
            $slug_prefix . '-dashboard',
            function() { $this->dispatch(DashboardController::class); },
            'dashicons-airplane',
            50 // Posisi aman di tengah
        );

        add_submenu_page(
            $slug_prefix . '-dashboard',
            'Dashboard',
            'Dashboard',
            $capability,
            $slug_prefix . '-dashboard',
            function() { $this->dispatch(DashboardController::class); }
        );

        // --- GROUP: PRODUK ---
        add_submenu_page(
            $slug_prefix . '-dashboard',
            'Paket Umrah & Wisata',
            'Paket Travel',
            $capability,
            $slug_prefix . '-packages',
            function() { $this->dispatch(PackageController::class); }
        );
        
        add_submenu_page(
            $slug_prefix . '-dashboard',
            'Layanan Tambahan',
            'Layanan Tambahan',
            $capability,
            $slug_prefix . '-special-services',
            function() { $this->dispatch(SpecialServicesController::class); }
        );

        // --- GROUP: PENJUALAN ---
        add_submenu_page(
            $slug_prefix . '-dashboard',
            'Data Transaksi Booking',
            'Transaksi Booking',
            $capability,
            $slug_prefix . '-bookings',
            function() { $this->dispatch(BookingController::class); }
        );

        add_submenu_page(
            $slug_prefix . '-dashboard',
            'Data Prospek (Leads)',
            'Leads / CRM',
            $capability,
            $slug_prefix . '-leads',
            function() { $this->dispatch(LeadController::class); }
        );

        // --- GROUP: KEUANGAN ---
        add_submenu_page(
            $slug_prefix . '-dashboard',
            'Keuangan',
            '-- Keuangan --', // Separator
            $capability,
            $slug_prefix . '-finance',
            function() { $this->dispatch(FinanceController::class); }
        );

        add_submenu_page(
            $slug_prefix . '-dashboard',
            'Tabungan Jamaah',
            'Tabungan Umrah',
            $capability,
            $slug_prefix . '-savings',
            function() { $this->dispatch(SavingsController::class); }
        );

        // --- GROUP: OPERASIONAL ---
        add_submenu_page(
            $slug_prefix . '-dashboard',
            'Operasional',
            '-- Operasional --', // Separator
            $capability,
            $slug_prefix . '-operational',
            function() { $this->dispatch(OperationalController::class); }
        );

        add_submenu_page(
            $slug_prefix . '-dashboard',
            'Keberangkatan',
            'Jadwal Keberangkatan',
            $capability,
            $slug_prefix . '-departures',
            function() { $this->dispatch(DepartureController::class); }
        );

        add_submenu_page(
            $slug_prefix . '-dashboard',
            'Manifest Penumpang',
            'Manifest Pesawat',
            $capability,
            $slug_prefix . '-manifest',
            function() { $this->dispatch(ManifestController::class); }
        );

        add_submenu_page(
            $slug_prefix . '-dashboard',
            'Rooming List',
            'Rooming Hotel',
            $capability,
            $slug_prefix . '-rooming',
            function() { $this->dispatch(RoomingListController::class); }
        );

        add_submenu_page(
            $slug_prefix . '-dashboard',
            'Visa Handling',
            'Visa Handling',
            $capability,
            $slug_prefix . '-visa',
            function() { $this->dispatch(VisaController::class); }
        );

        // --- GROUP: SDM & AGEN ---
        add_submenu_page(
            $slug_prefix . '-dashboard',
            'Keagenan',
            '-- Agen & SDM --', // Separator
            $capability,
            $slug_prefix . '-agents',
            function() { $this->dispatch(AgentsHRController::class); }
        );

        add_submenu_page(
            $slug_prefix . '-dashboard',
            'Komisi Agen',
            'Komisi Agen',
            $capability,
            $slug_prefix . '-commissions',
            function() { $this->dispatch(AgentCommissionController::class); }
        );
        
        add_submenu_page(
            $slug_prefix . '-dashboard',
            'Data Cabang',
            'Data Cabang',
            $capability,
            $slug_prefix . '-branches',
            function() { $this->dispatch(BranchController::class); }
        );

        // --- GROUP: SISTEM ---
        add_submenu_page(
            $slug_prefix . '-dashboard',
            'Laporan',
            '-- Laporan & Setting --',
            $capability,
            $slug_prefix . '-reports',
            function() { $this->dispatch(ReportController::class); }
        );

        add_submenu_page(
            $slug_prefix . '-dashboard',
            'Master Data',
            'Master Data',
            $capability,
            $slug_prefix . '-master-data',
            function() { $this->dispatch(MasterDataController::class); }
        );

        add_submenu_page(
            $slug_prefix . '-dashboard',
            'Pengaturan',
            'Pengaturan Sistem',
            $capability,
            $slug_prefix . '-settings',
            function() { $this->dispatch(SettingsController::class); }
        );
    }
}