<?php
namespace UmhMgmt\Controllers\Admin;

use UmhMgmt\Utils\View;

class BookingController {
    public function __construct() {
        add_action('admin_menu', [$this, 'add_submenu_page']);
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

    public function render_page() {
        View::render('admin/bookings/list');
    }
}
