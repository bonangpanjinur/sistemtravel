<?php
// File: NotificationService.php
// Location: src/Services/NotificationService.php

namespace App\Services;

use App\Utils\View;

class NotificationService {

    public function __construct() {
        // Hook Event Listener
        add_action('umh_booking_created', [$this, 'handleBookingCreated']);
        add_action('umh_booking_paid', [$this, 'handlePaymentReceived']);
    }

    /**
     * Handler saat Booking dibuat (Action Hook)
     */
    public function handleBookingCreated($booking_id) {
        $this->sendWhatsAppNotification($booking_id, 'booking');
        $this->sendEmailNotification($booking_id, 'booking_confirmation');
    }

    public function handlePaymentReceived($booking_id) {
        $this->sendWhatsAppNotification($booking_id, 'payment');
        $this->sendEmailNotification($booking_id, 'payment_verified');
    }

    /**
     * [IMPROVED] Kirim Email HTML
     */
    private function sendEmailNotification($booking_id, $type) {
        global $wpdb;
        
        // Ambil Data Booking Lengkap
        $booking = $wpdb->get_row($wpdb->prepare("
            SELECT b.*, u.user_email, u.display_name, p.name as package_name, d.departure_date
            FROM {$wpdb->prefix}umh_bookings b
            JOIN {$wpdb->users} u ON b.customer_user_id = u.ID
            JOIN {$wpdb->prefix}umh_departures d ON b.departure_id = d.id
            JOIN {$wpdb->prefix}umh_packages p ON d.package_id = p.id
            WHERE b.id = %d
        ", $booking_id));

        if (!$booking || empty($booking->user_email)) return;

        $subject = "";
        $template_file = "";

        if ($type === 'booking_confirmation') {
            $subject = "Konfirmasi Booking Umroh #{$booking_id} - " . get_bloginfo('name');
            $template_file = 'emails/booking-email'; // File template di folder templates
        } elseif ($type === 'payment_verified') {
            $subject = "Pembayaran Diterima #{$booking_id} - " . get_bloginfo('name');
            // Bisa buat template terpisah nanti
            $template_file = 'emails/booking-email'; 
        }

        // Render Email Template (gunakan Output Buffering)
        ob_start();
        View::render($template_file, [
            'booking' => $booking,
            'title' => $subject
        ]);
        $message = ob_get_clean();

        // Header HTML
        $headers = ['Content-Type: text/html; charset=UTF-8'];

        // Kirim via wp_mail
        wp_mail($booking->user_email, $subject, $message, $headers);
    }

    /**
     * Kirim WA (Logic Lama dipertahankan)
     */
    private function sendWhatsAppNotification($booking_id, $type) {
        global $wpdb;
        
        // Ambil data untuk replace variable
        $booking = $wpdb->get_row($wpdb->prepare("
            SELECT b.*, u.display_name, p.name as package_name 
            FROM {$wpdb->prefix}umh_bookings b
            JOIN {$wpdb->users} u ON b.customer_user_id = u.ID
            JOIN {$wpdb->prefix}umh_departures d ON b.departure_id = d.id
            JOIN {$wpdb->prefix}umh_packages p ON d.package_id = p.id
            WHERE b.id = %d
        ", $booking_id));

        if (!$booking) return;

        // Ambil Template dari Settings
        $setting_key = ($type == 'booking') ? 'wa_msg_booking' : 'wa_msg_payment';
        $template = $wpdb->get_var($wpdb->prepare("SELECT setting_value FROM {$wpdb->prefix}umh_settings WHERE setting_key = %s", $setting_key));
        
        if (!$template) return;

        // Replace Variable
        $message = str_replace(
            ['{name}', '{package}', '{id}', '{price}'],
            [$booking->display_name, $booking->package_name, $booking->id, number_format($booking->total_price)],
            $template
        );

        $phone = get_user_meta($booking->customer_user_id, 'phone_number', true); 
        if (!$phone) return;

        // Masukkan ke Outbox
        $wpdb->insert($wpdb->prefix . 'umh_wa_outbox', [
            'user_id' => $booking->customer_user_id,
            'phone_number' => $phone,
            'message' => $message,
            'status' => 'pending'
        ]);
    }
}