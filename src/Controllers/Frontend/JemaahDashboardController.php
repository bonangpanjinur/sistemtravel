<?php
// Folder: src/Controllers/Frontend/
// File: JemaahDashboardController.php

namespace UmhMgmt\Controllers\Frontend;

use UmhMgmt\Utils\View;
use UmhMgmt\Services\JemaahAppService;

class JemaahDashboardController {
    private $wpdb;
    private $jemaahService;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->jemaahService = new JemaahAppService();

        // Shortcode: [umh_jemaah_dashboard]
        // Ini mengembalikan fungsi agar bisa dipasang di Page WordPress
        add_shortcode('umh_jemaah_dashboard', [$this, 'render_dashboard']);
    }

    public function render_dashboard() {
        // 1. Security Check: Wajib Login
        if (!is_user_logged_in()) {
            return '<div class="umh-alert umh-alert-warning">Silakan login untuk mengakses area jamaah.</div>';
        }

        $userId = get_current_user_id();
        $userMeta = get_userdata($userId);

        // 2. Data Fetching: Ambil Semua Booking (Logika Lama + Perbaikan)
        // Kita gunakan LEFT JOIN agar booking tetap muncul meski belum diassign ke Departure/Paket
        $bookings = $this->wpdb->get_results($this->wpdb->prepare("
            SELECT b.*, 
                   COALESCE(p.name, 'Paket Belum Ditentukan') as package_name, 
                   d.departure_date, 
                   br.name as branch_name
            FROM {$this->wpdb->prefix}umh_bookings b
            LEFT JOIN {$this->wpdb->prefix}umh_departures d ON b.departure_id = d.id
            LEFT JOIN {$this->wpdb->prefix}umh_packages p ON d.package_id = p.id
            LEFT JOIN {$this->wpdb->prefix}umh_branches br ON b.branch_id = br.id
            WHERE b.customer_user_id = %d
            ORDER BY b.created_at DESC
        ", $userId));

        // 3. Data Fetching: Tentukan 'Active Booking' untuk Fitur Baru (Timeline)
        // Ambil booking terbaru yang statusnya bukan cancelled
        $activeBooking = null;
        if (!empty($bookings)) {
            foreach ($bookings as $b) {
                if ($b->status !== 'cancelled') {
                    $activeBooking = $b;
                    break;
                }
            }
        }

        $timeline = [];
        $guides = [];

        // Jika ada booking aktif, tarik data Smart Timeline & Guides dari Service Baru
        if ($activeBooking) {
            $timeline = $this->jemaahService->getProgressTimeline($activeBooking->id);
            $guides = $this->jemaahService->getDigitalGuides('all');
        }

        // 4. Render View dengan Output Buffering (Wajib untuk Shortcode)
        ob_start();
        View::render('frontend/jemaah-dashboard', [
            'user' => $userMeta,
            'bookings' => $bookings,         // Data List (Legacy Support)
            'payments' => [],                // Placeholder (Legacy Support)
            'active_booking' => $activeBooking, // Data Baru: Booking Utama
            'timeline' => $timeline,         // Data Baru: Progress Bar
            'guides' => $guides              // Data Baru: Panduan Manasik
        ]);
        return ob_get_clean();
    }
}