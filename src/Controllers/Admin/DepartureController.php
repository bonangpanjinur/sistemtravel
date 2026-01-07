<?php
namespace UmhMgmt\Controllers\Admin;

use UmhMgmt\Utils\View;

class DepartureController {
    public function __construct() {
        add_action('admin_menu', [$this, 'add_submenu_page']);
    }

    public function add_submenu_page() {
        add_submenu_page(
            'umh-dashboard',
            'Departures',
            'Departures',
            'manage_options',
            'umh-departures',
            [$this, 'render_page']
        );
    }

    public function render_page() {
        View::render('admin/departures');
    }
}
