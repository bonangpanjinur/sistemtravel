<?php
// Path: src/Controllers/Admin/DashboardController.php

namespace UmrahManagement\Controllers\Admin;

use UmrahManagement\Repositories\DashboardRepository;
use UmrahManagement\Utils\View;

class DashboardController {
    private $dashboardRepo;

    public function __construct(DashboardRepository $dashboardRepo) {
        $this->dashboardRepo = $dashboardRepo;
    }

    public function index() {
        $stats = $this->dashboardRepo->getSummaryStats();
        $upcoming = $this->dashboardRepo->getUpcomingDepartures(5);

        echo View::render('admin/dashboard', [
            'stats' => $stats,
            'upcoming' => $upcoming
        ]);
    }
}