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

    /**
     * Get statistics for the current month
     */
    public function getMonthlyStats()
    {
        $current_month = date('m');
        $current_year = date('Y');

        // Total Bookings this month
        $bookings = $this->db->get_var(
            $this->db->prepare(
                "SELECT COUNT(*) FROM {$this->db->prefix()}travel_bookings 
                 WHERE MONTH(created_at) = %d AND YEAR(created_at) = %d AND status != 'cancelled'",
                $current_month, $current_year
            )
        ) ?? 0;

        // Total Revenue this month (sum of total_price for confirmed/paid bookings)
        $revenue = $this->db->get_var(
            $this->db->prepare(
                "SELECT SUM(total_price) FROM {$this->db->prefix()}travel_bookings 
                 WHERE MONTH(created_at) = %d AND YEAR(created_at) = %d AND status IN ('confirmed', 'completed', 'paid')",
                $current_month, $current_year
            )
        ) ?? 0;

        // Total Pax (Jemaah) this month
        // Assuming 1 booking = 1 pax, or you might have a 'pax_count' column. 
        // If 1 booking can have multiple pax, change COUNT(*) to SUM(pax_count)
        $pax = $bookings; 

        return [
            'bookings' => (int) $bookings,
            'revenue' => (float) $revenue,
            'pax' => (int) $pax
        ];
    }

    /**
     * Get summary statistics for the dashboard cards
     */
    public function getSummaryStats()
    {
        // 1. Total Active Bookings (not cancelled)
        $total_bookings = $this->db->get_var(
            "SELECT COUNT(*) FROM {$this->db->prefix()}travel_bookings WHERE status != 'cancelled'"
        ) ?? 0;

        // 2. Total Revenue (All time)
        $total_revenue = $this->db->get_var(
            "SELECT SUM(total_price) FROM {$this->db->prefix()}travel_bookings WHERE status IN ('confirmed', 'completed', 'paid')"
        ) ?? 0;

        // 3. Active Packages (Upcoming departures)
        $active_packages = $this->db->get_var(
            "SELECT COUNT(*) FROM {$this->db->prefix()}travel_packages WHERE status = 'active' AND departure_date >= CURDATE()"
        ) ?? 0;

        // 4. Total Registered Jemaah
        $total_jemaah = $this->db->get_var(
            "SELECT COUNT(*) FROM {$this->db->prefix()}users"
        ) ?? 0;

        return [
            'total_bookings' => (int) $total_bookings,
            'total_revenue' => (float) $total_revenue,
            'active_packages' => (int) $active_packages,
            'total_jemaah' => (int) $total_jemaah,
        ];
    }

    /**
     * Get list of recent bookings
     */
    public function getRecentBookings($limit = 5)
    {
        // Join with Users table to get customer name
        // Join with Packages table to get package name
        $query = "SELECT b.*, u.display_name as customer_name, p.name as package_name 
                  FROM {$this->db->prefix()}travel_bookings b
                  LEFT JOIN {$this->db->prefix()}users u ON b.user_id = u.ID
                  LEFT JOIN {$this->db->prefix()}travel_packages p ON b.package_id = p.id
                  ORDER BY b.created_at DESC 
                  LIMIT %d";

        return $this->db->get_results($this->db->prepare($query, $limit));
    }

    /**
     * Get upcoming departures for the dashboard widget
     */
    public function getUpcomingDepartures($limit = 5)
    {
        $query = "SELECT p.*, 
                  (SELECT COUNT(*) FROM {$this->db->prefix()}travel_bookings b WHERE b.package_id = p.id AND b.status != 'cancelled') as booked_seats
                  FROM {$this->db->prefix()}travel_packages p
                  WHERE p.status = 'active' AND p.departure_date >= CURDATE()
                  ORDER BY p.departure_date ASC
                  LIMIT %d";

        return $this->db->get_results($this->db->prepare($query, $limit));
    }
}