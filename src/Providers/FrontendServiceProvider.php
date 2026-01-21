<?php

namespace SistemTravel\UmrohManagement\Providers;

use App\Core\Container;
use App\Controllers\Frontend\PackageCatalogController;
use App\Controllers\Frontend\BookingFormController;
use App\Controllers\Frontend\JemaahDashboardController;
use App\Controllers\Frontend\PaymentController;
use App\Controllers\Frontend\InvoiceController;
use App\Controllers\Frontend\DocumentController;
use App\Controllers\Frontend\CertificateController;
use App\Controllers\Frontend\DigitalIdController;
use App\Controllers\Frontend\AgentDashboardController;

class FrontendServiceProvider
{
    public function register()
    {
        // Register Shortcodes
        add_shortcode('travel_package_catalog', [$this, 'renderPackageCatalog']);
        add_shortcode('travel_booking_form', [$this, 'renderBookingForm']);
        add_shortcode('travel_jemaah_dashboard', [$this, 'renderJemaahDashboard']);
        add_shortcode('travel_payment_page', [$this, 'renderPaymentPage']);
        add_shortcode('travel_invoice_print', [$this, 'renderInvoicePrint']);
        add_shortcode('travel_inventory_scanner', [$this, 'renderInventoryScanner']);
        add_shortcode('travel_document_upload', [$this, 'renderDocumentUpload']);
        add_shortcode('travel_certificate_print', [$this, 'renderCertificatePrint']);
        add_shortcode('travel_digital_id', [$this, 'renderDigitalId']);
        add_shortcode('travel_agent_dashboard', [$this, 'renderAgentDashboard']);

        // Register Frontend Assets
        add_action('wp_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    public function enqueueAssets()
    {
        wp_enqueue_style('travel-umroh-frontend', plugins_url('../../assets/css/frontend.css', __FILE__), [], '1.0.0');
        wp_enqueue_script('travel-umroh-frontend', plugins_url('../../assets/js/frontend.js', __FILE__), ['jquery'], '1.0.0', true);

        // Localize script for AJAX
        wp_localize_script('travel-umroh-frontend', 'travelUmroh', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('travel_umroh_nonce')
        ]);
    }

    // --- Render Methods ---

    public function renderPackageCatalog($atts)
    {
        $controller = new PackageCatalogController();
        return $controller->index($atts);
    }

    public function renderBookingForm($atts)
    {
        $controller = new BookingFormController();
        return $controller->index($atts);
    }

    public function renderJemaahDashboard($atts)
    {
        // Check login
        if (!is_user_logged_in()) {
            return '<p>Silakan login terlebih dahulu.</p>';
        }
        
        $controller = new JemaahDashboardController();
        return $controller->index($atts);
    }

    public function renderPaymentPage($atts)
    {
        $controller = new PaymentController();
        
        if (isset($_GET['action']) && $_GET['action'] == 'verify') {
             return $controller->verify();
        }

        return $controller->index($atts);
    }

    public function renderInvoicePrint($atts)
    {
        $controller = new InvoiceController();
        return $controller->index($atts);
    }
    
    public function renderInventoryScanner($atts)
    {
        // Scanner logic here or separate controller
        ob_start();
        include plugin_dir_path(__FILE__) . '../../templates/frontend/inventory-scanner.php';
        return ob_get_clean();
    }

    public function renderDocumentUpload($atts) {
        $controller = new DocumentController();
        return $controller->index($atts);
    }

    public function renderCertificatePrint($atts) {
        $controller = new CertificateController();
        return $controller->index($atts);
    }

    public function renderDigitalId($atts) {
        $controller = new DigitalIdController();
        return $controller->index($atts);
    }

    public function renderAgentDashboard($atts) {
        if (!is_user_logged_in()) {
            return '<p>Silakan login sebagai agen.</p>';
        }
        $controller = new AgentDashboardController();
        return $controller->index($atts);
    }
}