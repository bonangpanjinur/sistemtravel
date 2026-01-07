<?php
namespace UmhMgmt\Controllers\Admin;

use UmhMgmt\Utils\View;

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
        View::render('admin/dashboard');
    }
}
