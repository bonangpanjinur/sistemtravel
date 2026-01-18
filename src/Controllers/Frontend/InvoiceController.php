<?php
// File: src/Controllers/Frontend/InvoiceController.php

namespace UmhMgmt\Controllers\Frontend;

use UmhMgmt\Utils\View;

class InvoiceController {
    public function __construct() {
        // Endpoint khusus untuk cetak invoice (tanpa masuk menu admin)
        add_action('admin_post_umh_print_invoice', [$this, 'handle_print_invoice']);
    }

    public function handle_print_invoice() {
        if (!is_user_logged_in()) {
            wp_die('Silakan login terlebih dahulu.', 'Akses Ditolak', ['response' => 403]);
        }

        $bookingId = isset($_GET['booking_id']) ? intval($_GET['booking_id']) : 0;
        if (!$bookingId) wp_die('Booking ID tidak valid.');

        global $wpdb;

        // 1. Ambil Data Booking
        $booking = $wpdb->get_row($wpdb->prepare(
            "SELECT b.*, d.departure_date, p.name as package_name, br.name as branch_name 
             FROM {$wpdb->prefix}umh_bookings b
             JOIN {$wpdb->prefix}umh_departures d ON b.departure_id = d.id
             JOIN {$wpdb->prefix}umh_packages p ON d.package_id = p.id
             LEFT JOIN {$wpdb->prefix}umh_branches br ON b.branch_id = br.id
             WHERE b.id = %d",
            $bookingId
        ));

        if (!$booking) wp_die('Data booking tidak ditemukan.');

        // Security Check: Pastikan user berhak melihat invoice ini
        $currentUserId = get_current_user_id();
        // Menggunakan edit_posts agar Staff (bukan hanya Admin) bisa melihat
        $isAgentOrStaff = current_user_can('edit_posts'); 
        
        if ($booking->customer_user_id != $currentUserId && !$isAgentOrStaff) {
            wp_die('Anda tidak memiliki akses ke invoice ini.');
        }

        // 2. Ambil Data Penumpang
        $passengers = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}umh_booking_passengers WHERE booking_id = %d",
            $bookingId
        ));

        // 3. Ambil Data Add-ons
        $addons = $wpdb->get_results($wpdb->prepare(
            "SELECT ba.*, s.service_name 
             FROM {$wpdb->prefix}umh_booking_addons ba
             JOIN {$wpdb->prefix}umh_service_catalog s ON ba.service_id = s.id
             WHERE ba.booking_id = %d",
            $bookingId
        ));

        // 4. [INTEGRASI] Ambil Riwayat Pembayaran (Dari kode lama)
        // Data ini penting untuk menghitung sisa tagihan di template
        $payments = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}umh_payments WHERE booking_id = %d AND status = 'verified'",
            $bookingId
        ));

        // Hitung Total Terbayar & Sisa Tagihan
        $total_paid = 0;
        foreach ($payments as $pay) {
            $total_paid += $pay->amount;
        }
        $due_amount = $booking->total_price - $total_paid;

        // 5. Data Perusahaan (Hardcoded or from Settings)
        $company = [
            'name' => get_bloginfo('name'),
            'address' => 'Jl. Contoh Travel No. 123, Jakarta',
            'phone' => '+62 812-3456-7890',
            'email' => get_bloginfo('admin_email')
        ];

        // 6. Render View Invoice (Clean HTML for Printing)
        // Variable $payments, $total_paid, dan $due_amount sekarang tersedia untuk digunakan di template
        include plugin_dir_path(__FILE__) . '../../../templates/frontend/invoice-print.php';
        exit;
    }
}