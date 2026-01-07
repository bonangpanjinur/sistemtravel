<?php
namespace UmhMgmt\Controllers\Admin;

use UmhMgmt\Utils\View;

class MasterDataController {
    public function __construct() {
        add_action('admin_menu', [$this, 'add_submenu_page']);
    }

    public function add_submenu_page() {
        add_submenu_page(
            'umh-dashboard',
            'Master Data',
            'Master Data',
            'manage_options',
            'umh-master',
            [$this, 'render_page']
        );
    }

    public function render_page() {
        View::render('admin/master-data');
    }
}
