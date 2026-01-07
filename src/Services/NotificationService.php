<?php
// Folder: src/Services/
// File: NotificationService.php

namespace UmhMgmt\Services;

class NotificationService {
    
    public function __construct() {
        // Listen to event
        add_action('umh_booking_created', [$this, 'sendAdminNotification'], 10, 1);
    }

    public function sendAdminNotification($bookingId) {
        $admin_email = get_option('admin_email');
        $subject = "[Umroh System] Booking Baru ID #$bookingId";
        
        $message  = "Halo Admin,\n\n";
        $message .= "Ada booking baru masuk dengan ID #$bookingId.\n";
        $message .= "Silakan cek dashboard untuk verifikasi.\n\n";
        $message .= "Salam,\nUmroh Management System";

        $headers = ['Content-Type: text/plain; charset=UTF-8'];

        wp_mail($admin_email, $subject, $message, $headers);
    }
}