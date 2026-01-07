<?php
// File: NotificationService.php
// Location: src/Services/NotificationService.php

namespace UmhMgmt\Services;

class NotificationService {

    public function __construct() {
        // Listener hooks bisa ditambahkan di sini jika ingin full event-driven
    }

    /**
     * Kirim Notifikasi Booking Baru
     */
    public static function sendBookingCreated($booking_id) {
        global $wpdb;
        
        // 1. Ambil Data Booking & User
        $booking = $wpdb->get_row($wpdb->prepare("
            SELECT b.*, u.display_name, u.user_email, p.name as package_name 
            FROM {$wpdb->prefix}umh_bookings b
            JOIN {$wpdb->users} u ON b.customer_user_id = u.ID
            JOIN {$wpdb->prefix}umh_departures d ON b.departure_id = d.id
            JOIN {$wpdb->prefix}umh_packages p ON d.package_id = p.id
            WHERE b.id = %d
        ", $booking_id));

        if (!$booking) return;

        // 2. Ambil Template Pesan
        $template = $wpdb->get_var("SELECT setting_value FROM {$wpdb->prefix}umh_settings WHERE setting_key = 'wa_msg_booking'");
        if (!$template) $template = "Booking #{id} berhasil dibuat.";

        // 3. Replace Variabel
        $message = str_replace(
            ['{name}', '{package}', '{id}', '{price}'],
            [$booking->display_name, $booking->package_name, $booking->id, number_format($booking->total_price)],
            $template
        );

        // 4. Masukkan ke Outbox
        // Asumsi kita punya nomor HP di usermeta 'billing_phone' atau 'phone_number'
        $phone = get_user_meta($booking->customer_user_id, 'phone_number', true); 
        if (!$phone) $phone = '620000000000'; // Fallback/Error handling

        self::addToOutbox($booking->customer_user_id, $phone, $message);
    }

    /**
     * Kirim Notifikasi Pembayaran Lunas
     */
    public static function sendPaymentReceived($booking_id, $amount) {
        global $wpdb;
        // ... Logic serupa dengan di atas, ambil template 'wa_msg_payment' ...
        // Simplified for brevity
        $template = "Pembayaran Rp " . number_format($amount) . " diterima untuk Booking #$booking_id.";
        // self::addToOutbox(..., ..., $template);
    }

    private static function addToOutbox($user_id, $phone, $message) {
        global $wpdb;
        $wpdb->insert($wpdb->prefix . 'umh_wa_outbox', [
            'user_id' => $user_id,
            'phone_number' => $phone,
            'message' => $message,
            'status' => 'pending'
        ]);
        
        // Di sistem nyata, kita bisa trigger cron job atau langsung kirim via CURL di sini
        // self::processQueue(); 
    }
}