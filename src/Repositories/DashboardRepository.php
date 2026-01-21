<?php
// Path: src/Repositories/DashboardRepository.php

namespace App\Repositories;

use App\Interfaces\DatabaseInterface;

class DashboardRepository {
    private $db;
    private $table_bookings;
    private $table_departures;
    private $table_packages;

    public function __construct(DatabaseInterface $db) {
        $this->db = $db;
        $prefix = $this->db->prefix();
        
        // Menggunakan prefix 'umh_' sesuai kode legacy
        $this->table_bookings = $prefix . 'umh_bookings';
        $this->table_departures = $prefix . 'umh_departures';
        $this->table_packages = $prefix . 'umh_packages';
    }

    public function getTotalRevenue() {
        // Mengambil total pendapatan dari booking yang statusnya 'paid'
        return $this->db->get_var($this->db->prepare(
            "SELECT SUM(total_price) FROM {$this->table_bookings} WHERE status = %s",
            'paid' // Sebaiknya nanti diganti dengan Constants::STATUS_PAID jika Constants sudah direfactor
        ));
    }

    public function getJamaahThisMonth() {
        return $this->db->get_var("
            SELECT COUNT(*) FROM {$this->table_bookings} 
            WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) 
            AND YEAR(created_at) = YEAR(CURRENT_DATE())
        ");
    }

    public function getTotalBookings() {
        return $this->db->get_var("SELECT COUNT(*) FROM {$this->table_bookings}");
    }

    public function getUpcomingDepartures($limit = 5) {
        $sql = $this->db->prepare("
            SELECT d.*, p.name as package_name 
            FROM {$this->table_departures} d
            LEFT JOIN {$this->table_packages} p ON d.package_id = p.id
            WHERE d.departure_date >= CURDATE() 
            ORDER BY d.departure_date ASC 
            LIMIT %d
        ", $limit);

        return $this->db->get_results($sql);
    }

    /**
     * Method tambahan untuk ringkasan satu kali panggil (opsional, untuk efisiensi)
     */
    public function getSummaryStats() {
        return [
            'total_bookings' => $this->getTotalBookings(),
            'revenue'        => $this->getTotalRevenue(),
            'jamaah_month'   => $this->getJamaahThisMonth()
        ];
    }
}