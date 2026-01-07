<?php
namespace UmhMgmt\Controllers\Admin;

use UmhMgmt\Utils\View;

class DashboardController {
    public function __construct() {
        add_action('admin_menu', [$this, 'add_menu_page']);
    }

    public function add_menu_page() {
        add_menu_page(
            'Umroh Dashboard',
            'Umroh Management',
            'manage_options',
            'umh-dashboard',
            [$this, 'render_dashboard'],
            'dashicons-airplane',
            25
        );
    }

    public function render_dashboard() {
        View::render('admin/dashboard');
    }
}
