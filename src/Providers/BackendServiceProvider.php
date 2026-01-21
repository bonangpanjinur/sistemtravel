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
use App\Controllers\Admin\AgentsHRController;
use App\Controllers\Admin\BranchController;
use App\Controllers\Admin\ReportController;
use App\Controllers\Admin\SettingsController;
use App\Controllers\Admin\MasterDataController;
use App\Controllers\Admin\OperationalController;
use App\Controllers\Admin\DepartureController;
use App\Controllers\Admin\ManifestController;
use App\Controllers\Admin\RoomingListController;
use App\Controllers\Admin\IntegrationController;

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
        add_action('admin_menu', [$this, 'registerAdminMenus'], 99);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminAssets']);
    }

    public function enqueueAdminAssets()
    {
        // Aset Admin
    }

    public function dispatch($controllerClass, $method = 'index')
    {
        if (!class_exists($controllerClass)) {
            echo "<div class='notice notice-error'><p>Class $controllerClass not found.</p></div>";
            return;
        }
        
        try {
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

        // 1. Dashboard (Utama)
        add_menu_page(
            'Sistem Travel',
            'Sistem Travel',
            $capability,
            $slug_prefix . '-dashboard',
            function() { $this->dispatch(DashboardController::class); },
            'dashicons-airplane',
            50
        );

        add_submenu_page(
            $slug_prefix . '-dashboard',
            'Dashboard',
            'Dashboard',
            $capability,
            $slug_prefix . '-dashboard',
            function() { $this->dispatch(DashboardController::class); }
        );

        // 2. Penjualan (Submenu: Paket, Booking, Leads, Katalog)
        add_submenu_page(
            $slug_prefix . '-dashboard',
            'Penjualan',
            'Penjualan',
            $capability,
            $slug_prefix . '-sales',
            function() { $this->dispatch(PackageController::class); } // Default ke Paket
        );

        // 3. Operasional (Submenu: Keberangkatan, Jamaah, Manifest, Inventory)
        add_submenu_page(
            $slug_prefix . '-dashboard',
            'Operasional',
            'Operasional',
            $capability,
            $slug_prefix . '-ops',
            function() { $this->dispatch(OperationalController::class); }
        );

        // 4. Keuangan (Submenu: Laporan, Tagihan, Tabungan, Komisi)
        add_submenu_page(
            $slug_prefix . '-dashboard',
            'Keuangan',
            'Keuangan',
            $capability,
            $slug_prefix . '-finance-group',
            function() { $this->dispatch(FinanceController::class); }
        );

        // 5. Pengaturan (Submenu: Staff, Agen, Master Data, Integrasi)
        add_submenu_page(
            $slug_prefix . '-dashboard',
            'Pengaturan',
            'Pengaturan',
            $capability,
            $slug_prefix . '-settings-group',
            function() { $this->dispatch(SettingsController::class); }
        );
    }
}
