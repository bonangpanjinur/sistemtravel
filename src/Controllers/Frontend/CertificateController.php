<?php
// File: CertificateController.php
// Location: src/Controllers/Frontend/CertificateController.php

namespace UmhMgmt\Controllers\Frontend;

use UmhMgmt\Utils\View;

class CertificateController {

    public function __construct() {
        // Endpoint khusus untuk cetak sertifikat
        add_action('admin_post_umh_download_certificate', [$this, 'render_certificate']);
    }

    public function render_certificate() {
        // 1. Security Check
        if (!is_user_logged_in()) wp_die('Harap login.');

        $booking_id = isset($_GET['booking_id']) ? absint($_GET['booking_id']) : 0;
        $pax_id = isset($_GET['pax_id']) ? absint($_GET['pax_id']) : 0;
        
        global $wpdb;
        $current_user_id = get_current_user_id();

        // 2. Validasi Data Booking & Kepemilikan
        $data = $wpdb->get_row($wpdb->prepare("
            SELECT pax.name as jemaah_name, 
                   p.name as package_name, 
                   d.departure_date,
                   b.status,
                   b.customer_user_id
            FROM {$wpdb->prefix}umh_booking_passengers pax
            JOIN {$wpdb->prefix}umh_bookings b ON pax.booking_id = b.id
            JOIN {$wpdb->prefix}umh_departures d ON b.departure_id = d.id
            JOIN {$wpdb->prefix}umh_packages p ON d.package_id = p.id
            WHERE pax.id = %d AND b.id = %d
        ", $pax_id, $booking_id));

        if (!$data) wp_die('Data sertifikat tidak ditemukan.');

        // Cek apakah yang akses adalah pemilik booking (atau admin)
        if ($data->customer_user_id != $current_user_id && !current_user_can('edit_posts')) {
            wp_die('Anda tidak memiliki akses ke sertifikat ini.');
        }

        // 3. Cek Status Keberangkatan (Opsional: harusnya status 'completed' atau tanggal lewat)
        // Untuk demo, kita izinkan jika status 'paid'
        if (!in_array($data->status, ['paid', 'completed', 'departed'])) {
            wp_die('Sertifikat belum tersedia. Menunggu penyelesaian ibadah.');
        }

        // 4. Data Direktur & Tanda Tangan (Ambil dari Settings)
        $director_name = "H. Fulan Bin Fulan"; // Bisa dari DB setting
        $company_name = get_bloginfo('name');

        // 5. Generate QR Validation Code
        // QR berisi URL validasi ke website
        $validation_url = home_url("/verify-certificate?id={$pax_id}&code=" . substr(md5($data->jemaah_name . 'SECRET'), 0, 8));
        $qr_image = "https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=" . urlencode($validation_url);

        // 6. Load View (Clean HTML for Print/PDF)
        $template_path = UMH_PLUGIN_DIR . 'templates/frontend/certificate-print.php';
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            wp_die('Template sertifikat hilang.');
        }
        exit;
    }
}