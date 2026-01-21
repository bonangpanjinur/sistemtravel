<?php

namespace App\Repositories;

use App\Interfaces\DatabaseInterface;

class DashboardRepository
{
    protected $db;

    public function __construct(DatabaseInterface $db)
    {
        $this->db = $db;
    }

    public function getMonthlyStats()
    {
        return [
            'bookings' => 0,
            'revenue' => 0,
            'pax' => 0
        ];
    }

    /**
     * Fixes error: Call to undefined method ...::getSummaryStats()
     */
    public function getSummaryStats()
    {
        // Contoh implementasi sederhana agar dashboard tidak crash
        return [
            'total_bookings' => $this->db->get_var("SELECT COUNT(*) FROM {$this->db->prefix()}travel_bookings") ?? 0,
            'total_revenue' => 0, // Bisa ditambahkan query ke tabel finance
            'active_packages' => $this->db->get_var("SELECT COUNT(*) FROM {$this->db->prefix()}travel_packages WHERE status = 'active'") ?? 0,
            'total_jemaah' => $this->db->get_var("SELECT COUNT(*) FROM {$this->db->prefix()}users") ?? 0,
        ];
    }

    public function getRecentBookings($limit = 5)
    {
        return $this->db->get_results(
            $this->db->prepare("SELECT * FROM {$this->db->prefix()}travel_bookings ORDER BY created_at DESC LIMIT %d", $limit)
        );
    }
}