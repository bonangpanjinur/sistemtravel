<?php
namespace UmhMgmt\Controllers\Admin;

use UmhMgmt\Utils\View;

class SavingsController {
    public function __construct() {
        add_action('admin_menu', [$this, 'add_submenu_page']);
    }

    public function add_submenu_page() {
        add_submenu_page(
            'umh-dashboard',
            'Savings',
            'Savings',
            'manage_options',
            'umh-savings',
            [$this, 'render_page']
        );
    }

    public function render_page() {
        View::render('admin/savings');
    }
}
