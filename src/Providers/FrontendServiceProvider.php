<?php
// Path: src/Providers/FrontendServiceProvider.php

namespace UmrahManagement\Providers;

use UmrahManagement\Core\Container;
use UmrahManagement\Controllers\Frontend\BookingFormController;
use UmrahManagement\Controllers\Frontend\PackageCatalogController;
use UmrahManagement\Controllers\Frontend\JemaahDashboardController;

class FrontendServiceProvider {
    private $container;

    public function __construct(Container $container) {
        $this->container = $container;
    }

    public function register() {
        // Registrasi Shortcode
        add_shortcode('umroh_booking_form', [$this, 'renderBookingForm']);
        add_shortcode('umroh_package_list', [$this, 'renderPackageList']);
        add_shortcode('umroh_jemaah_dashboard', [$this, 'renderDashboard']);
        
        // Enqueue Assets Frontend
        add_action('wp_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    public function renderBookingForm($atts) {
        $controller = $this->container->get(BookingFormController::class);
        return $controller->render($atts);
    }

    public function renderPackageList($atts) {
        $controller = $this->container->get(PackageCatalogController::class);
        return $controller->index($atts);
    }

    public function renderDashboard($atts) {
        if (!is_user_logged_in()) {
            return '<p>Silakan login terlebih dahulu.</p>';
        }
        $controller = $this->container->get(JemaahDashboardController::class);
        return $controller->index();
    }
    
    public function enqueueAssets() {
        // Contoh load JS Alpine.js untuk booking form
        // wp_enqueue_script('alpine-js', 'https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js', [], null, true);
        
        // Load JS Form Booking custom
        wp_enqueue_script(
            'umroh-booking-js', 
            plugins_url('../../assets/js/booking-form.js', __DIR__), 
            ['jquery'], 
            '1.0', 
            true
        );
    }
}