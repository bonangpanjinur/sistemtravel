<?php
// Folder: src/Controllers/Admin/
// File: AgentsHRController.php

namespace App\Controllers\Admin;

use App\Utils\View;
use App\Config\Constants;

class AgentsHRController {
    public function __construct() {
        add_action('admin_menu', [$this, 'add_submenu_page']);
    }

    public function add_submenu_page() {
        add_submenu_page(
            'umh-dashboard',
            'Agents & HR',
            'Agents & HR',
            Constants::CAP_MANAGE_OPTIONS, // Menggunakan Constant
            'umh-agents-hr',
            [$this, 'render_page']
        );
    }

    public function render_page() {
        // SECURITY FIX: Cek ulang permission sebelum render
        if (!current_user_can(Constants::CAP_MANAGE_OPTIONS)) {
            wp_die('Unauthorized access');
        }

        View::render('admin/agents-hr');
    }
}