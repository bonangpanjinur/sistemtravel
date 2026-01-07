<?php
namespace UmhMgmt\Controllers\Admin;

use UmhMgmt\Utils\View;

class AgentsHRController {
    public function __construct() {
        add_action('admin_menu', [$this, 'add_submenu_page']);
    }

    public function add_submenu_page() {
        add_submenu_page(
            'umh-dashboard',
            'Agents & HR',
            'Agents & HR',
            'manage_options',
            'umh-agents-hr',
            [$this, 'render_page']
        );
    }

    public function render_page() {
        View::render('admin/agents-hr');
    }
}
