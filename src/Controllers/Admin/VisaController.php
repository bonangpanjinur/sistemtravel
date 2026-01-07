<?php
// File: VisaController.php
// Location: src/Controllers/Admin/VisaController.php

namespace UmhMgmt\Controllers\Admin;

use UmhMgmt\Utils\View;

class VisaController {

    public function __construct() {
        add_action('admin_menu', [$this, 'add_menu']);
        add_action('admin_post_umh_update_visa_status', [$this, 'handle_status_update']);
    }

    public function add_menu() {
        add_submenu_page(
            'umh-dashboard', // Parent menu
            'Visa Handling',
            'Visa Handling',
            'manage_options',
            'umh-visa-handling',
            [$this, 'render_page']
        );
    }

    public function handle_status_update() {
        check_admin_referer('umh_visa_nonce');
        if (!current_user_can('edit_posts')) wp_die('Unauthorized');

        global $wpdb;
        $passenger_ids = isset($_POST['pax_ids']) ? array_map('absint', $_POST['pax_ids']) : [];
        $new_status = sanitize_text_field($_POST['new_status']);
        $notes = sanitize_textarea_field($_POST['notes']);

        if (!empty($passenger_ids)) {
            $ids_placeholder = implode(',', $passenger_ids);
            
            // Update status verifikasi dokumen (sebagai proxy status visa)
            // Idealnya kita punya kolom khusus 'visa_status', tapi kita bisa pakai 'doc_verification_status'
            // atau menambah kolom baru. Untuk sekarang kita pakai 'doc_verification_status' dengan value custom.
            
            $wpdb->query("
                UPDATE {$wpdb->prefix}umh_booking_passengers 
                SET doc_verification_status = '$new_status' 
                WHERE id IN ($ids_placeholder)
            ");
        }

        wp_redirect(add_query_arg(['message' => 'updated'], wp_get_referer()));
        exit;
    }

    public function render_page() {
        global $wpdb;
        
        // Filter Keberangkatan
        $departure_id = isset($_GET['departure_id']) ? absint($_GET['departure_id']) : 0;

        // Ambil Daftar Keberangkatan Aktif
        $departures = $wpdb->get_results("
            SELECT d.id, d.departure_date, p.name as package_name
            FROM {$wpdb->prefix}umh_departures d
            JOIN {$wpdb->prefix}umh_packages p ON d.package_id = p.id
            WHERE d.status != 'closed'
            ORDER BY d.departure_date ASC
        ");

        $passengers = [];
        if ($departure_id) {
            $passengers = $wpdb->get_results($wpdb->prepare("
                SELECT pax.*, b.id as booking_ref
                FROM {$wpdb->prefix}umh_booking_passengers pax
                JOIN {$wpdb->prefix}umh_bookings b ON pax.booking_id = b.id
                WHERE b.departure_id = %d AND b.status IN ('paid', 'confirmed', 'verified')
                ORDER BY pax.name ASC
            ", $departure_id));
        }

        View::render('admin/operations/visa-handling', [
            'departures' => $departures,
            'current_departure_id' => $departure_id,
            'passengers' => $passengers
        ]);
    }
}