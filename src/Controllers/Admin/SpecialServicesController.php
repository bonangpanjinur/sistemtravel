<?php
namespace UmhMgmt\Controllers\Admin;

use UmhMgmt\Utils\View;

class SpecialServicesController {
    public function __construct() {
        add_action('admin_menu', [$this, 'add_submenu_page']);
    }

    public function add_submenu_page() {
        add_submenu_page(
            'umh-dashboard',
            'Special Services',
            'Special Services',
            'manage_options',
            'umh-special-services',
            [$this, 'render_page']
        );
    }

    public function render_page() {
        View::render('admin/special-services');
    }
}
