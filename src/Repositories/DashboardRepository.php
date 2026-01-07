<?php
// Folder: src/Repositories/
// File: DashboardRepository.php

namespace UmhMgmt\Repositories;

use UmhMgmt\Config\Constants;

class DashboardRepository {
    private $wpdb;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    public function getTotalRevenue() {
        // Menggunakan prepare untuk keamanan, meskipun query statis (Best Practice)
        return $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT SUM(total_price) FROM {$this->wpdb->prefix}umh_bookings WHERE status = %s",
            Constants::STATUS_PAID
        ));
    }

    public function getJamaahThisMonth() {
        return $this->wpdb->get_var("
            SELECT COUNT(*) FROM {$this->wpdb->prefix}umh_bookings 
            WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) 
            AND YEAR(created_at) = YEAR(CURRENT_DATE())
        ");
    }

    public function getTotalBookings() {
        return $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->wpdb->prefix}umh_bookings");
    }

    public function getUpcomingDepartures($limit = 5) {
        return $this->wpdb->get_results($this->wpdb->prepare("
            SELECT d.*, p.name as package_name 
            FROM {$this->wpdb->prefix}umh_departures d
            LEFT JOIN {$this->wpdb->prefix}umh_packages p ON d.package_id = p.id
            WHERE d.departure_date >= CURRENT_DATE() 
            ORDER BY d.departure_date ASC 
            LIMIT %d
        ", $limit));
    }
}