<?php
// Path: src/Providers/FrontendServiceProvider.php

namespace App\Providers;

use App\Core\Container;
use App\Controllers\Frontend\BookingFormController;
use App\Controllers\Frontend\PackageCatalogController;
use App\Controllers\Frontend\JemaahDashboardController;
use App\Controllers\Api\PackageApiController;

class FrontendServiceProvider {
    private $container;

    public function __construct(Container $container) {
        $this->container = $container;
    }

    public function register() {
        // Registrasi Shortcode
        add_shortcode('umroh_booking_form', [$this, 'renderBookingForm']);
        // Mengembalikan shortcode yang sebelumnya hilang
        add_shortcode('umroh_package_list', [$this, 'renderPackageList']);
        add_shortcode('umroh_jemaah_dashboard', [$this, 'renderDashboard']);
        
        // Enqueue Assets
        add_action('wp_enqueue_scripts', [$this, 'enqueueAssets']);

        // Registrasi REST API Routes (Fitur Baru)
        add_action('rest_api_init', [$this, 'registerRestRoutes']);
    }

    public function registerRestRoutes() {
        $apiController = $this->container->get(PackageApiController::class);

        register_rest_route('umh/v1', '/calculate-price', [
            'methods' => 'POST',
            'callback' => [$apiController, 'calculatePrice'],
            'permission_callback' => '__return_true' // Public API
        ]);
    }

    public function renderBookingForm($atts) {
        $controller = $this->container->get(BookingFormController::class);
        return $controller->render($atts);
    }

    // Method dikembalikan dari kode awal
    public function renderPackageList($atts) {
        $controller = $this->container->get(PackageCatalogController::class);
        return $controller->index($atts);
    }

    // Method dikembalikan dari kode awal
    public function renderDashboard($atts) {
        if (!is_user_logged_in()) {
            return '<p>Silakan login terlebih dahulu.</p>';
        }
        $controller = $this->container->get(JemaahDashboardController::class);
        return $controller->index();
    }

    public function enqueueAssets() {
        // 1. Load Alpine.js (Fitur Baru)
        wp_enqueue_script('alpine-js', 'https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js', [], null, true);

        // 2. Load Script Booking Custom (Ganti jQuery lama dengan Alpine logic baru)
        wp_enqueue_script(
            'umh-booking-alpine', 
            plugins_url('../../assets/js/booking-alpine.js', __DIR__), 
            ['alpine-js'], 
            '1.0', 
            true
        );

        // Kirim variable ke JS (agar JS tahu URL API kita)
        wp_localize_script('umh-booking-alpine', 'umhData', [
            'apiUrl' => get_rest_url(null, 'umh/v1/'),
            'nonce' => wp_create_nonce('wp_rest')
        ]);
    }
}