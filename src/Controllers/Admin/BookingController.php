<?php
namespace UmhMgmt\Controllers\Admin;

use UmhMgmt\Utils\View;
use UmhMgmt\Repositories\BookingRepository;

class BookingController {
    private $repo;

    public function __construct() {
        $this->repo = new BookingRepository();
        add_action('admin_menu', [$this, 'add_submenu_page']);
        add_action('admin_post_umh_update_booking_status', [$this, 'handle_update_status']);
    }

    public function add_submenu_page() {
        add_submenu_page(
            'umh-dashboard',
            'Bookings',
            'Bookings',
            'manage_options',
            'umh-bookings',
            [$this, 'render_page']
        );
    }

    public function handle_update_status() {
        check_admin_referer('umh_booking_nonce');
        if (!current_user_can('manage_options')) wp_die('Unauthorized');

        global $wpdb;
        $id = absint($_GET['id']);
        $status = sanitize_text_field($_GET['status']);

        $wpdb->update($wpdb->prefix . 'umh_bookings', ['status' => $status], ['id' => $id]);
        wp_redirect(admin_url('admin.php?page=umh-bookings'));
        exit;
    }

    public function render_page() {
        global $wpdb;
        $bookings = $wpdb->get_results("
            SELECT b.*, d.departure_date, p.name as package_name, u.display_name as customer_name
            FROM {$wpdb->prefix}umh_bookings b
            JOIN {$wpdb->prefix}umh_departures d ON b.departure_id = d.id
            JOIN {$wpdb->prefix}umh_packages p ON d.package_id = p.id
            JOIN {$wpdb->prefix}users u ON b.customer_user_id = u.ID
            ORDER BY b.created_at DESC
        ");
        View::render('admin/bookings/list', ['bookings' => $bookings]);
    }
}
