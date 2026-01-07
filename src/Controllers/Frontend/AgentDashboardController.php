<?php
// File: AgentDashboardController.php
// Location: src/Controllers/Frontend/AgentDashboardController.php

namespace UmhMgmt\Controllers\Frontend;

use UmhMgmt\Utils\View;

class AgentDashboardController {

    public function __construct() {
        // Shortcode: [umh_agent_dashboard]
        add_shortcode('umh_agent_dashboard', [$this, 'render_dashboard']);
    }

    public function render_dashboard() {
        // 1. Security & Role Check
        if (!is_user_logged_in()) {
            return '<div class="umh-alert umh-alert-warning">Silakan login sebagai Agen untuk mengakses halaman ini.</div>';
        }

        $user = wp_get_current_user();
        if (!in_array('umh_agent', (array) $user->roles) && !in_array('administrator', (array) $user->roles)) {
            return '<div class="umh-alert umh-alert-danger">Akses Ditolak. Halaman ini khusus untuk Agen Travel.</div>';
        }

        global $wpdb;
        $user_id = $user->ID;

        // 2. Statistik Agen (Query Data)
        
        // Total Penjualan (Jumlah Booking)
        $total_bookings = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}umh_bookings WHERE agent_id = %d AND status != 'canceled'", 
            $user_id
        ));

        // Total Komisi (Pending & Paid)
        $commission_stats = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END) as paid,
                SUM(amount) as total
             FROM {$wpdb->prefix}umh_commissions 
             WHERE agent_id = %d",
            $user_id
        ));

        // Riwayat Penjualan Terakhir
        $recent_sales = $wpdb->get_results($wpdb->prepare(
            "SELECT b.*, p.name as package_name, b.status as booking_status
             FROM {$wpdb->prefix}umh_bookings b
             JOIN {$wpdb->prefix}umh_departures d ON b.departure_id = d.id
             JOIN {$wpdb->prefix}umh_packages p ON d.package_id = p.id
             WHERE b.agent_id = %d
             ORDER BY b.created_at DESC
             LIMIT 10",
            $user_id
        ));

        // Generate Referral Link
        // Asumsi format: https://website.com/?ref=username
        $referral_link = home_url('/?ref=' . $user->user_login);

        ob_start();
        View::render('frontend/agent-dashboard', [
            'user' => $user,
            'total_bookings' => $total_bookings,
            'commission_stats' => $commission_stats,
            'recent_sales' => $recent_sales,
            'referral_link' => $referral_link
        ]);
        return ob_get_clean();
    }
}