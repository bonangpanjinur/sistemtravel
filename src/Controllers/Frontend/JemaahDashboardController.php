<?php
// File: JemaahDashboardController.php
// Location: src/Controllers/Frontend/JemaahDashboardController.php

namespace UmhMgmt\Controllers\Frontend;

use UmhMgmt\Utils\View;

class JemaahDashboardController {

    public function __construct() {
        // Shortcode: [umh_jemaah_dashboard]
        add_shortcode('umh_jemaah_dashboard', [$this, 'render_dashboard']);
    }

    public function render_dashboard() {
        // 1. Security Check: Wajib Login
        if (!is_user_logged_in()) {
            return '<div class="umh-alert umh-alert-warning">Silakan login untuk mengakses area jamaah.</div>';
        }

        $user_id = get_current_user_id();
        $user_meta = get_userdata($user_id);

        // 2. Security Check: Role Validation
        // Jika Agen atau Admin mencoba akses shortcode ini, bisa kita block atau biarkan (opsional)
        // Di sini kita biarkan agar admin bisa preview, tapi idealnya di-restrict.

        // 3. Data Fetching (Secure Query)
        global $wpdb;
        
        // Ambil Booking Milik User Ini Saja
        $bookings = $wpdb->get_results($wpdb->prepare("
            SELECT b.*, p.name as package_name, d.departure_date, br.name as branch_name
            FROM {$wpdb->prefix}umh_bookings b
            JOIN {$wpdb->prefix}umh_departures d ON b.departure_id = d.id
            JOIN {$wpdb->prefix}umh_packages p ON d.package_id = p.id
            LEFT JOIN {$wpdb->prefix}umh_branches br ON b.branch_id = br.id
            WHERE b.customer_user_id = %d
            ORDER BY b.created_at DESC
        ", $user_id));

        // Ambil Tagihan Pembayaran (Dummy Logic for UI)
        // Nanti diganti dengan tabel finance real
        $payments = []; 

        ob_start();
        View::render('frontend/jemaah-dashboard', [
            'user' => $user_meta,
            'bookings' => $bookings,
            'payments' => $payments
        ]);
        return ob_get_clean();
    }
}