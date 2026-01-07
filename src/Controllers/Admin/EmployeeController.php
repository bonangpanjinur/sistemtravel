<?php
namespace UmhMgmt\Controllers\Admin;

use UmhMgmt\Utils\View;

class EmployeeController {
    public function __construct() {
        add_action('admin_menu', [$this, 'add_menu_page']);
    }

    public function add_menu_page() {
        add_submenu_page(
            'umh-dashboard',
            'Data Karyawan',
            'Karyawan (HR)',
            'umh_manage_staff',
            'umh-employees',
            [$this, 'render_list']
        );
    }

    public function render_list() {
        global $wpdb;
        $employees = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}umh_employees ORDER BY name ASC");

        View::render('admin/hr/employee-list', ['employees' => $employees]);
    }
}
