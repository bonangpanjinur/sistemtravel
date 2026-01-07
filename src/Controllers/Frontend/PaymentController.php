<?php
// File: PaymentController.php
// Location: src/Controllers/Frontend/PaymentController.php

namespace UmhMgmt\Controllers\Frontend;

use UmhMgmt\Services\AuditLogService;

class PaymentController {

    public function __construct() {
        // Handle Form Submission Pembayaran
        add_action('admin_post_umh_submit_payment', [$this, 'handle_payment_submission']);
    }

    public function handle_payment_submission() {
        if (!is_user_logged_in()) wp_die('Harap login.');

        check_admin_referer('umh_payment_nonce');

        global $wpdb;
        $user_id = get_current_user_id();
        $booking_id = absint($_POST['booking_id']);
        $amount = str_replace(['.', ','], '', sanitize_text_field($_POST['amount'])); // Hapus format currency
        $sender_name = sanitize_text_field($_POST['sender_name']);
        $bank_target = sanitize_text_field($_POST['bank_target']);

        // 1. Validasi Kepemilikan Booking
        $is_owner = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}umh_bookings WHERE id = %d AND customer_user_id = %d",
            $booking_id, $user_id
        ));

        if (!$is_owner) wp_die('Akses ditolak. Booking tidak ditemukan.');

        // 2. Upload Bukti Transfer
        $proof_url = '';
        if (!empty($_FILES['proof_file']['name'])) {
            if (!function_exists('wp_handle_upload')) require_once(ABSPATH . 'wp-admin/includes/file.php');
            
            $uploaded = wp_handle_upload($_FILES['proof_file'], ['test_form' => false]);
            if (isset($uploaded['error'])) wp_die('Upload Gagal: ' . $uploaded['error']);
            
            $proof_url = $uploaded['url'];
        } else {
            wp_die('Bukti transfer wajib diupload.');
        }

        // 3. Simpan ke Database
        $wpdb->insert(
            $wpdb->prefix . 'umh_payments',
            [
                'booking_id' => $booking_id,
                'user_id' => $user_id,
                'amount' => $amount,
                'payment_method' => 'bank_transfer',
                'bank_target' => $bank_target,
                'sender_name' => $sender_name,
                'proof_file_url' => $proof_url,
                'status' => 'pending_verification'
            ]
        );

        // 4. Update Status Booking sementara (Optional)
        // Kita tidak ubah status booking jadi PAID dulu, tapi bisa kasih flag 'payment_uploaded'
        // Biarkan admin yang memvalidasi.

        // 5. Notifikasi / Redirect
        wp_redirect(add_query_arg('payment_status', 'submitted', wp_get_referer()));
        exit;
    }
}