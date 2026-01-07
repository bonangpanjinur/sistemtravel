<?php
namespace UmhMgmt\Controllers\Admin;

use UmhMgmt\Utils\View;

class BranchController {
    public function __construct() {
        add_action('admin_menu', [$this, 'add_menu_page']);
    }

    public function add_menu_page() {
        add_submenu_page(
            'umh-dashboard',
            'Manajemen Cabang',
            'Cabang',
            'umh_manage_all_branches',
            'umh-branches',
            [$this, 'render_list']
        );
    }

    public function render_list() {
        global $wpdb;
        $branches = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}umh_branches ORDER BY name ASC");

        View::render('admin/branches/list', ['branches' => $branches]);
    }
}
