<?php
// Path: src/Controllers/Admin/DashboardController.php

namespace UmrahManagement\Controllers\Admin; // PENTING: Namespace harus ini

use UmrahManagement\Repositories\DashboardRepository;
use UmrahManagement\Utils\View;

class DashboardController {
    private $dashboardRepo;

    // Dependency Injection otomatis jalan karena Container sudah setup
    public function __construct(DashboardRepository $dashboardRepo) {
        $this->dashboardRepo = $dashboardRepo;
    }

    public function index() {
        // Menggunakan repo yang sudah direfactor sebelumnya
        $stats = $this->dashboardRepo->getSummaryStats();
        $upcoming = $this->dashboardRepo->getUpcomingDepartures(5);

        echo View::render('admin/dashboard', [
            'stats' => $stats,
            'upcoming' => $upcoming
        ]);
    }
}