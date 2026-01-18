<?php
// File: src/Controllers/Frontend/PaymentCallbackController.php

namespace UmhMgmt\Controllers\Frontend;

use UmhMgmt\Services\NotificationService;
use UmhMgmt\Repositories\BookingRepository;

class PaymentCallbackController {
    
    public function __construct() {
        // Hook khusus untuk menangani Notification Webhook dari Midtrans
        // URL Webhook di Midtrans dashboard: https://websiteanda.com/wp-admin/admin-post.php?action=umh_midtrans_callback
        add_action('admin_post_umh_midtrans_callback', [$this, 'handleWebhook']);
        add_action('admin_post_nopriv_umh_midtrans_callback', [$this, 'handleWebhook']);
    }

    public function handleWebhook() {
        // Ambil JSON Raw Body
        $json = file_get_contents('php://input');
        $notification = json_decode($json);

        if (!$notification) {
            http_response_code(400); // Bad Request
            exit;
        }

        // Validasi Signature (Keamanan)
        $serverKey = get_option('umh_midtrans_server_key', '');
        if (empty($serverKey)) {
             // Fallback atau log error jika key belum disetting
             error_log('Midtrans Server Key is missing in settings.');
             exit;
        }

        $validSignature = hash("sha512", $notification->order_id . $notification->status_code . $notification->gross_amount . $serverKey);

        if ($notification->signature_key !== $validSignature) {
            http_response_code(403); // Forbidden
            exit;
        }

        $transactionStatus = $notification->transaction_status;
        $orderIdParts = explode('-', $notification->order_id); 
        $bookingId = isset($orderIdParts[1]) ? intval($orderIdParts[1]) : 0;

        if (!$bookingId) exit;

        global $wpdb;
        $booking = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}umh_bookings WHERE id = %d", $bookingId));

        if (!$booking) exit;

        // Logic Update Status
        $newStatus = '';
        if ($transactionStatus == 'capture' || $transactionStatus == 'settlement') {
            $newStatus = 'paid';
        } elseif ($transactionStatus == 'pending') {
            $newStatus = 'waiting_payment';
        } elseif ($transactionStatus == 'deny' || $transactionStatus == 'expire' || $transactionStatus == 'cancel') {
            $newStatus = 'cancelled';
        }

        if ($newStatus && $newStatus !== $booking->status) {
            // Update Database
            $wpdb->update(
                "{$wpdb->prefix}umh_bookings",
                ['status' => $newStatus, 'updated_at' => current_time('mysql')],
                ['id' => $bookingId]
            );

            // Log Pembayaran
            $wpdb->insert("{$wpdb->prefix}umh_payments", [
                'booking_id' => $bookingId,
                'amount' => $notification->gross_amount,
                'payment_method' => 'midtrans_' . ($notification->payment_type ?? 'unknown'),
                'status' => 'verified', // Karena dari gateway, auto verified
                'proof_file' => $notification->transaction_id ?? '', // Simpan ref ID midtrans
                'created_at' => current_time('mysql')
            ]);

            // Trigger Notification (Email & WA) via Hook
            if ($newStatus == 'paid') {
                do_action('umh_booking_paid', $bookingId);
            }
        }

        http_response_code(200); // OK
        echo "OK";
        exit;
    }
}