<?php
namespace UmhMgmt\Controllers\Admin;

use UmhMgmt\Utils\View;
use UmhMgmt\Repositories\BookingRepository;
use UmhMgmt\Repositories\OperationalRepository;

class DashboardController {
    public function __construct() {
        add_action('admin_menu', [$this, 'add_menu_page']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function enqueue_assets($hook) {
        if (strpos($hook, 'umh-') !== false) {
            wp_enqueue_style('umh-admin-css', UMH_PLUGIN_URL . 'assets/css/admin.css', [], UMH_VERSION);
        }
    }

    public function add_menu_page() {
        add_menu_page(
            'Umroh Mgmt',
            'Umroh Mgmt',
            'manage_options',
            'umh-dashboard',
            [$this, 'render_dashboard'],
            'dashicons-airplane',
            6
        );
    }

    public function render_dashboard() {
        global $wpdb;
        
        // 1. Ambil Total Omzet (Paid Only)
        $revenue = $wpdb->get_var("SELECT SUM(total_price) FROM {$wpdb->prefix}umh_bookings WHERE status='paid'");
        
        // 2. Ambil Total Jamaah Bulan Ini
        $jamaah_month = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}umh_bookings WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())");
        
        // 3. Ambil Total Booking
        $total_bookings = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}umh_bookings");

        // 4. Ambil 5 Keberangkatan Terdekat
        $departures = $wpdb->get_results("
            SELECT d.*, p.name as package_name 
            FROM {$wpdb->prefix}umh_departures d
            LEFT JOIN {$wpdb->prefix}umh_packages p ON d.package_id = p.id
            WHERE d.departure_date >= CURRENT_DATE() 
            ORDER BY d.departure_date ASC 
            LIMIT 5
        ");

        $data = [
            'total_bookings' => $total_bookings ?: 0,
            'total_revenue'  => $revenue ?: 0,
            'jamaah_month'   => $jamaah_month ?: 0,
            'upcoming_departures' => $departures
        ];

        View::render('admin/dashboard', $data);
    }
}
