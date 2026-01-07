<?php
// Folder: src/Controllers/Admin/
// File: DashboardController.php

namespace UmhMgmt\Controllers\Admin;

use UmhMgmt\Utils\View;
use UmhMgmt\Repositories\DashboardRepository;
use UmhMgmt\Config\Constants;

class DashboardController {
    private $repo;

    public function __construct() {
        $this->repo = new DashboardRepository();
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
            Constants::CAP_MANAGE_OPTIONS,
            'umh-dashboard',
            [$this, 'render_dashboard'],
            'dashicons-airplane',
            6
        );
    }

    public function render_dashboard() {
        // SECURITY FIX: Menggunakan Repository, tidak ada Raw SQL disini
        $data = [
            'total_bookings' => $this->repo->getTotalBookings() ?: 0,
            'total_revenue'  => $this->repo->getTotalRevenue() ?: 0,
            'jamaah_month'   => $this->repo->getJamaahThisMonth() ?: 0,
            'upcoming_departures' => $this->repo->getUpcomingDepartures(5)
        ];

        View::render('admin/dashboard', $data);
    }
}