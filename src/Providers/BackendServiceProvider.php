<?php

namespace SistemTravel\UmrohManagement\Providers;

use SistemTravel\UmrohManagement\Core\Container;
use ReflectionClass;
use ReflectionNamedType;

// Kami menggunakan Fully Qualified Class Name di dalam method untuk menghindari masalah import
class BackendServiceProvider
{
    protected $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Register services and hooks.
     */
    public function register()
    {
        add_action('admin_menu', [$this, 'registerAdminMenus']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminAssets']);
    }

    /**
     * Helper: Resolusi Dependensi Otomatis (Auto-Wiring).
     * Menggantikan "new Class($container)" dengan injeksi cerdas.
     */
    private function resolve($className)
    {
        // 1. Pastikan class target ada
        if (!class_exists($className)) {
            return null;
        }

        $reflector = new ReflectionClass($className);

        // Jika tidak bisa diinstansiasi (abstract/interface)
        if (!$reflector->isInstantiable()) {
            return null;
        }

        $constructor = $reflector->getConstructor();

        // Jika tidak ada constructor, langsung buat instance baru
        if (is_null($constructor)) {
            return new $className;
        }

        $parameters = $constructor->getParameters();
        $dependencies = [];

        foreach ($parameters as $parameter) {
            $type = $parameter->getType();
            
            // Jika parameter memiliki tipe class (Dependency Injection)
            if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                $dependencyName = $type->getName();
                
                // A. Jika meminta Container, berikan container utama
                if (strpos($dependencyName, 'Container') !== false) {
                    $dependencies[] = $this->container;
                    continue;
                }

                // B. Jika dependensi belum dimuat, coba cari filenya
                if (!class_exists($dependencyName)) {
                    $this->autoloadDependency($dependencyName);
                }

                // C. Resolusi rekursif untuk dependensi tersebut
                if (class_exists($dependencyName)) {
                    $dependencies[] = $this->resolve($dependencyName);
                } else {
                    // Fallback: Jika gagal resolve, berikan null (atau Container jika putus asa)
                    $dependencies[] = null;
                }
            } else {
                // Jika parameter primitif (string/int), gunakan nilai default jika ada
                if ($parameter->isDefaultValueAvailable()) {
                    $dependencies[] = $parameter->getDefaultValue();
                } else {
                    $dependencies[] = null;
                }
            }
        }

        return $reflector->newInstanceArgs($dependencies);
    }

    /**
     * Helper: Mencari file Repositories/Services jika Autoloader gagal.
     */
    private function autoloadDependency($className)
    {
        // Daftar folder umum di src/
        $folders = ['Repositories', 'Services', 'Utils', 'Core', 'Models', 'Interfaces'];
        $baseDir = dirname(__DIR__) . '/'; // Path ke folder src/

        // Ambil nama file dari class (misal: App\Repositories\BookingRepository -> BookingRepository)
        $parts = explode('\\', $className);
        $shortName = end($parts);

        foreach ($folders as $folder) {
            // Cek apakah nama class mengandung nama folder (misal "Repository" biasanya ada di folder Repositories)
            // Atau cukup cari file dengan nama tersebut di setiap folder
            $file = $baseDir . $folder . '/' . $shortName . '.php';
            
            if (file_exists($file)) {
                require_once $file;
                
                // Cek namespace mismatch (App vs SistemTravel) dan buat alias jika perlu
                if (!class_exists($className)) {
                    // Cari class apa yang sebenarnya didefinisikan di file itu
                    $content = file_get_contents($file);
                    if (preg_match('/namespace\s+(.+?);/', $content, $matches)) {
                        $realClass = trim($matches[1]) . '\\' . $shortName;
                        if (class_exists($realClass)) {
                            class_alias($realClass, $className);
                        }
                    }
                }
                return;
            }
        }
    }

    /**
     * Helper: Paksa load file controller dan mencoba memperbaiki namespace.
     */
    private function requireController($expectedClassName, $relativePath)
    {
        if (class_exists($expectedClassName)) {
            return true;
        }

        $file = dirname(__DIR__) . '/' . $relativePath;
        
        if (!file_exists($file)) {
            $this->showError($expectedClassName, $file, "File tidak ditemukan.");
            return false;
        }

        require_once $file;

        if (class_exists($expectedClassName)) {
            return true;
        }

        // Auto-fix Namespace
        $content = file_get_contents($file);
        $fileNamespace = '';
        if (preg_match('/namespace\s+(.+?);/', $content, $matches)) {
            $fileNamespace = trim($matches[1]);
        }
        $fileClass = pathinfo($file, PATHINFO_FILENAME);
        $realClassName = $fileNamespace . '\\' . $fileClass;

        if (class_exists($realClassName)) {
            class_alias($realClassName, $expectedClassName);
            return true;
        }

        $this->showError($expectedClassName, $file, "Namespace mismatch: '$fileNamespace'");
        return false;
    }

    private function showError($className, $file, $analisis)
    {
        echo "<div class='notice notice-error' style='padding:15px; border-left: 4px solid #d63638; margin: 20px 0; background:#fff;'>";
        echo "<h3 style='margin:0 0 5px; color:#d63638;'>Sistem Travel Error</h3>";
        echo "<p>Gagal memuat Controller.</p><ul><li>Target: <code>{$className}</code></li><li>File: <code>{$file}</code></li><li>Analisis: {$analisis}</li></ul>";
        echo "</div>";
    }

    public function registerAdminMenus()
    {
        $capability = 'manage_options';
        $main_slug  = 'sistem-travel';

        add_menu_page('Sistem Travel', 'Travel Umroh', $capability, $main_slug, [$this, 'renderDashboard'], 'dashicons-airplane', 2);
        add_submenu_page($main_slug, 'Dashboard', 'Dashboard', $capability, $main_slug, [$this, 'renderDashboard']);
        add_submenu_page($main_slug, 'Data Pendaftaran', 'Booking & Jamaah', $capability, 'st-booking', [$this, 'renderBooking']);
        add_submenu_page($main_slug, 'Kelola Paket', 'Paket Umroh', $capability, 'st-packages', [$this, 'renderPackages']);
        add_submenu_page($main_slug, 'Operasional & Manifest', 'Operasional', $capability, 'st-operational', [$this, 'renderOperational']);
        add_submenu_page($main_slug, 'CRM & Leads', 'CRM / Leads', $capability, 'st-crm', [$this, 'renderCRM']);
        add_submenu_page($main_slug, 'Laporan Keuangan', 'Keuangan', $capability, 'st-finance', [$this, 'renderFinance']);
        add_submenu_page($main_slug, 'Agen & Pegawai', 'Agen & HR', $capability, 'st-agents', [$this, 'renderAgents']);
        add_submenu_page($main_slug, 'Pengaturan Sistem', 'Pengaturan', $capability, 'st-settings', [$this, 'renderSettings']);
    }

    public function enqueueAdminAssets()
    {
        wp_enqueue_style('st-admin-css', plugin_dir_url(dirname(__DIR__, 2)) . 'assets/css/admin.css', [], '1.0.1');
    }

    // --- Callback Functions (Updated to use resolve() instead of new) ---

    public function renderDashboard()
    {
        $class = 'SistemTravel\UmrohManagement\Controllers\Admin\DashboardController';
        if ($this->requireController($class, 'Controllers/Admin/DashboardController.php')) {
            $instance = $this->resolve($class);
            if ($instance) $instance->index();
        }
    }

    public function renderBooking()
    {
        $class = 'SistemTravel\UmrohManagement\Controllers\Admin\BookingController';
        if ($this->requireController($class, 'Controllers/Admin/BookingController.php')) {
            $instance = $this->resolve($class); // Auto-wires BookingRepository
            if ($instance) $instance->index();
        }
    }

    public function renderPackages()
    {
        $class = 'SistemTravel\UmrohManagement\Controllers\Admin\PackageController';
        if ($this->requireController($class, 'Controllers/Admin/PackageController.php')) {
            $instance = $this->resolve($class);
            if ($instance) $instance->index();
        }
    }

    public function renderOperational()
    {
        $class = 'SistemTravel\UmrohManagement\Controllers\Admin\OperationalController';
        if ($this->requireController($class, 'Controllers/Admin/OperationalController.php')) {
            $instance = $this->resolve($class);
            if ($instance) $instance->index();
        }
    }

    public function renderCRM()
    {
        $class = 'SistemTravel\UmrohManagement\Controllers\Admin\CRMController';
        if ($this->requireController($class, 'Controllers/Admin/CRMController.php')) {
            $instance = $this->resolve($class);
            if ($instance) $instance->index();
        }
    }

    public function renderFinance()
    {
        $class = 'SistemTravel\UmrohManagement\Controllers\Admin\FinanceController';
        if ($this->requireController($class, 'Controllers/Admin/FinanceController.php')) {
            $instance = $this->resolve($class);
            if ($instance) $instance->index();
        }
    }

    public function renderAgents()
    {
        $class = 'SistemTravel\UmrohManagement\Controllers\Admin\AgentsHRController';
        if ($this->requireController($class, 'Controllers/Admin/AgentsHRController.php')) {
            $instance = $this->resolve($class);
            if ($instance) $instance->index();
        }
    }

    public function renderSettings()
    {
        $class = 'SistemTravel\UmrohManagement\Controllers\Admin\SettingsController';
        if ($this->requireController($class, 'Controllers/Admin/SettingsController.php')) {
            $instance = $this->resolve($class);
            if ($instance) $instance->index();
        }
    }
}