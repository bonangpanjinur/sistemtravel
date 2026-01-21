<?php

namespace SistemTravel\UmrohManagement\Providers;

// Use App namespaces for internal components
use App\Core\WordPressDatabaseAdapter;
use App\Interfaces\DatabaseInterface;
use App\Controllers\Admin\DashboardController;
use App\Controllers\Admin\BookingController;
use App\Controllers\Admin\PackageController;
use App\Controllers\Admin\OperationalController;
use App\Controllers\Admin\CRMController;
use App\Controllers\Admin\FinanceController;
use App\Controllers\Admin\ReportController;
use App\Controllers\Admin\SettingsController;
use App\Controllers\Admin\AgentsHRController;
use App\Controllers\Admin\BranchController;
use App\Controllers\Admin\MasterDataController;
use App\Controllers\Admin\IntegrationController;
use App\Controllers\Admin\SpecialServicesController;
use App\Controllers\Admin\SavingsController;
use App\Controllers\Admin\InventoryScannerController;
use App\Controllers\Admin\ManifestController;
use App\Controllers\Admin\LeadController;

class BackendServiceProvider
{
    /**
     * @var mixed The dependency injection container
     */
    private $container;

    /**
     * Constructor receives the container from the main plugin class.
     * * @param mixed $container The DI container instance
     */
    public function __construct($container = null)
    {
        // FIX: Accept existing container instead of creating new one
        // Fallback to new App\Core\Container only if null passed
        if ($container) {
            $this->container = $container;
        } else {
            // Only strictly require App\Core\Container if we have to create it ourselves
            // This avoids "Class not found" if the caller passed a compatible container from a different namespace
            if (class_exists('App\Core\Container')) {
                $this->container = new \App\Core\Container();
            } else {
                // Last resort fallback
                $this->container = new \stdClass();
            }
        }
    }

    public function register()
    {
        // 1. FIX: Bind DatabaseInterface Explicitly FIRST
        // Ensure the container has the bind/singleton method before calling
        if (method_exists($this->container, 'singleton')) {
            $this->container->singleton(DatabaseInterface::class, function () {
                return new WordPressDatabaseAdapter();
            });
        } elseif (method_exists($this->container, 'bind')) {
             // Fallback for simple containers
             $this->container->bind(DatabaseInterface::class, function () {
                return new WordPressDatabaseAdapter();
            });
        }

        // 2. Bind Repositories
        $this->registerRepositories();

        // 3. Bind Services
        $this->registerServices();

        // 4. Bind Controllers
        $this->registerControllers();

        // 5. Setup Admin Hooks
        add_action('admin_menu', [$this, 'registerAdminMenu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    private function registerRepositories()
    {
        if (!method_exists($this->container, 'bind')) {
            return;
        }

        // Repositories automatically resolved if DatabaseInterface is present
        $repositories = [
            \App\Repositories\DashboardRepository::class,
            \App\Repositories\BookingRepository::class,
            \App\Repositories\PackageRepository::class,
            \App\Repositories\OperationalRepository::class,
            \App\Repositories\CRMRepository::class,
            \App\Repositories\FinanceRepository::class,
            \App\Repositories\MasterDataRepository::class,
            \App\Repositories\LeadRepository::class,
        ];

        foreach ($repositories as $repo) {
            $this->container->bind($repo, function ($c) use ($repo) {
                // Handle different container implementations if necessary
                $db = $c->get(DatabaseInterface::class);
                return new $repo($db);
            });
        }
    }

    private function registerServices()
    {
        // Register services here if needed explicitly
    }

    private function registerControllers()
    {
        // Controllers resolve dependencies automatically via reflection in resolve()
    }

    public function registerAdminMenu()
    {
        add_menu_page(
            'Sistem Travel',
            'Sistem Travel',
            'manage_options',
            'travel-umroh',
            [$this, 'renderDashboard'],
            'dashicons-airplane',
            2
        );

        add_submenu_page('travel-umroh', 'Dashboard', 'Dashboard', 'manage_options', 'travel-umroh', [$this, 'renderDashboard']);
        add_submenu_page('travel-umroh', 'Bookings', 'Bookings', 'manage_options', 'travel-umroh-bookings', [$this, 'renderBookings']);
        add_submenu_page('travel-umroh', 'Packages', 'Packages', 'manage_options', 'travel-umroh-packages', [$this, 'renderPackages']);
        add_submenu_page('travel-umroh', 'Operational', 'Operational', 'manage_options', 'travel-umroh-operational', [$this, 'renderOperational']);
        add_submenu_page('travel-umroh', 'CRM & Leads', 'CRM & Leads', 'manage_options', 'travel-umroh-crm', [$this, 'renderCRM']);
        add_submenu_page('travel-umroh', 'Finance', 'Finance', 'manage_options', 'travel-umroh-finance', [$this, 'renderFinance']);
        add_submenu_page('travel-umroh', 'Agents & HR', 'Agents & HR', 'manage_options', 'travel-umroh-agents', [$this, 'renderAgents']);
        add_submenu_page('travel-umroh', 'Reports', 'Reports', 'manage_options', 'travel-umroh-reports', [$this, 'renderReports']);
        add_submenu_page('travel-umroh', 'Settings', 'Settings', 'manage_options', 'travel-umroh-settings', [$this, 'renderSettings']);
        
        // Hidden pages or sub-features
        add_submenu_page(null, 'Branches', 'Branches', 'manage_options', 'travel-umroh-branches', [$this, 'renderBranches']);
        add_submenu_page(null, 'Master Data', 'Master Data', 'manage_options', 'travel-umroh-master-data', [$this, 'renderMasterData']);
        add_submenu_page(null, 'Integrations', 'Integrations', 'manage_options', 'travel-umroh-integrations', [$this, 'renderIntegrations']);
        add_submenu_page(null, 'Special Services', 'Special Services', 'manage_options', 'travel-umroh-special-services', [$this, 'renderSpecialServices']);
        add_submenu_page(null, 'Savings', 'Savings', 'manage_options', 'travel-umroh-savings', [$this, 'renderSavings']);
    }

    public function enqueueAssets($hook)
    {
        if (strpos($hook, 'travel-umroh') === false) {
            return;
        }

        wp_enqueue_style('travel-umroh-admin', plugins_url('../../assets/css/admin.css', __FILE__), [], '1.0.0');
    }

    // --- Render Methods ---

    public function renderDashboard()
    {
        $controller = $this->resolve(DashboardController::class);
        if ($controller) $controller->index();
    }

    public function renderBookings()
    {
        $controller = $this->resolve(BookingController::class);
        if ($controller) $controller->index();
    }

    public function renderPackages()
    {
        $controller = $this->resolve(PackageController::class);
        if ($controller) {
            if (isset($_GET['action']) && $_GET['action'] === 'create') {
                $controller->create();
            } elseif (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
                $controller->edit($_GET['id']);
            } else {
                $controller->index();
            }
        }
    }

    public function renderOperational()
    {
        $controller = $this->resolve(OperationalController::class);
        if ($controller) $controller->index();
    }

    public function renderCRM()
    {
        $controller = $this->resolve(CRMController::class);
        if ($controller) $controller->index();
    }

    public function renderFinance()
    {
        $controller = $this->resolve(FinanceController::class);
        if ($controller) $controller->index();
    }

    public function renderAgents()
    {
        $controller = $this->resolve(AgentsHRController::class);
        if ($controller) $controller->index(); 
    }

    public function renderReports()
    {
        $controller = $this->resolve(ReportController::class);
        if ($controller) $controller->index();
    }

    public function renderSettings()
    {
        $controller = $this->resolve(SettingsController::class);
        if ($controller) $controller->index();
    }
    
    public function renderBranches() {
        $controller = $this->resolve(BranchController::class);
        if ($controller) $controller->index();
    }

    public function renderMasterData() {
        $controller = $this->resolve(MasterDataController::class);
        if ($controller) $controller->index();
    }
    
    public function renderIntegrations() {
        $controller = $this->resolve(IntegrationController::class);
        if ($controller) $controller->index();
    }
    
    public function renderSpecialServices() {
        $controller = $this->resolve(SpecialServicesController::class);
        if ($controller) $controller->index();
    }

    public function renderSavings() {
        $controller = $this->resolve(SavingsController::class);
        if ($controller) $controller->index();
    }

    /**
     * Simple dependency resolver
     */
    private function resolve($class)
    {
        // Try container first
        if (method_exists($this->container, 'has') && $this->container->has($class)) {
            return $this->container->get($class);
        }

        if (!class_exists($class)) {
            echo "<div class='error'><p>Class $class not found. Please check namespaces.</p></div>";
            return null;
        }

        $reflector = new \ReflectionClass($class);
        $constructor = $reflector->getConstructor();

        if (is_null($constructor)) {
            return new $class;
        }

        $parameters = $constructor->getParameters();
        $dependencies = [];

        foreach ($parameters as $parameter) {
            $type = $parameter->getType();
            if ($type && !$type->isBuiltin()) {
                $dependencyClass = $type->getName();
                
                // Check container
                if (method_exists($this->container, 'has') && $this->container->has($dependencyClass)) {
                    $dependencies[] = $this->container->get($dependencyClass);
                } else {
                    // Specific fix for DatabaseInterface
                    if ($dependencyClass === DatabaseInterface::class) {
                        if (method_exists($this->container, 'has') && !$this->container->has(DatabaseInterface::class)) {
                            // Late binding if missed
                             if (method_exists($this->container, 'singleton')) {
                                $this->container->singleton(DatabaseInterface::class, function () {
                                    return new WordPressDatabaseAdapter();
                                });
                             }
                        }
                        if (method_exists($this->container, 'get')) {
                            $dependencies[] = $this->container->get(DatabaseInterface::class);
                        } else {
                            $dependencies[] = new WordPressDatabaseAdapter();
                        }
                    } else {
                        $dependencies[] = $this->resolve($dependencyClass);
                    }
                }
            } else {
                if ($parameter->isDefaultValueAvailable()) {
                    $dependencies[] = $parameter->getDefaultValue();
                } else {
                    $dependencies[] = null;
                }
            }
        }

        return $reflector->newInstanceArgs($dependencies);
    }
}