<?php
namespace UmhMgmt\Controllers\Admin;

use UmhMgmt\Utils\View;

class CustomerCareController {
    public function __construct() {
        add_action('admin_menu', [$this, 'add_submenu_page']);
    }

    public function add_submenu_page() {
        add_submenu_page(
            'umh-dashboard',
            'Customer Care',
            'Customer Care',
            'manage_options',
            'umh-customer-care',
            [$this, 'render_page']
        );
    }

    public function render_page() {
        View::render('admin/customer-care');
    }
}
