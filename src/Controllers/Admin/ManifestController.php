<?php
// File: ManifestController.php
// Location: src/Controllers/Admin/ManifestController.php

namespace UmhMgmt\Controllers\Admin;

use UmhMgmt\Utils\View;

class ManifestController {

    public function __construct() {
        // Mendaftarkan action untuk handle request "Download/Print Manifest"
        add_action('admin_post_umh_print_manifest', [$this, 'render_manifest']);
    }

    public function render_manifest() {
        // 1. Security Check
        if (!current_user_can('edit_posts')) {
            wp_die('Unauthorized access');
        }

        $departure_id = isset($_GET['departure_id']) ? absint($_GET['departure_id']) : 0;
        if (!$departure_id) wp_die('Invalid Departure ID');

        global $wpdb;

        // 2. Ambil Data Keberangkatan (Flight & Hotel Info)
        $departure = $wpdb->get_row($wpdb->prepare("
            SELECT d.*, p.name as package_name, 
                   a.name as airline_name, a.code as airline_code,
                   h1.name as hotel_mekkah, h2.name as hotel_madinah
            FROM {$wpdb->prefix}umh_departures d
            JOIN {$wpdb->prefix}umh_packages p ON d.package_id = p.id
            LEFT JOIN {$wpdb->prefix}umh_airlines a ON p.airline_id = a.id
            LEFT JOIN {$wpdb->prefix}umh_hotels h1 ON p.hotel_mekkah_id = h1.id
            LEFT JOIN {$wpdb->prefix}umh_hotels h2 ON p.hotel_madinah_id = h2.id
            WHERE d.id = %d
        ", $departure_id));

        if (!$departure) wp_die('Data keberangkatan tidak ditemukan.');

        // 3. Ambil Data Penumpang (Manifest)
        // Hanya ambil yang bookingnya tidak dicancel
        $passengers = $wpdb->get_results($wpdb->prepare("
            SELECT pax.*, b.id as booking_ref, b.agent_id, u.display_name as agent_name
            FROM {$wpdb->prefix}umh_booking_passengers pax
            JOIN {$wpdb->prefix}umh_bookings b ON pax.booking_id = b.id
            LEFT JOIN {$wpdb->users} u ON b.agent_id = u.ID
            WHERE b.departure_id = %d 
            AND b.status IN ('paid', 'confirmed', 'verified') 
            ORDER BY pax.name ASC
        ", $departure_id));

        // 4. Load View Khusus (Tanpa Header/Footer WordPress Admin agar bersih saat diprint)
        // Kita include file view secara langsung
        $template_path = UMH_PLUGIN_DIR . 'templates/admin/operations/manifest-print.php';
        
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            wp_die('Template manifest tidak ditemukan: ' . $template_path);
        }
        exit; // Stop execution agar tidak load UI WordPress lainnya
    }
}