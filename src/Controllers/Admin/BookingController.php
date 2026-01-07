<?php
// Folder: src/Controllers/Admin/
// File: BookingController.php

namespace UmhMgmt\Controllers\Admin;

use UmhMgmt\Utils\View;
use UmhMgmt\Repositories\BookingRepository;
use UmhMgmt\Services\AuditLogService;
use UmhMgmt\Config\Constants;

class BookingController {
    private $repo;

    public function __construct() {
        $this->repo = new BookingRepository();
        add_action('admin_menu', [$this, 'add_submenu_page']);
        add_action('admin_post_umh_update_booking_status', [$this, 'handle_update_status']);
    }

    public function add_submenu_page() {
        // SECURITY: Hanya user dengan hak akses spesifik yang bisa melihat menu ini
        // Ganti 'manage_options' dengan capability kustom
        add_submenu_page(
            'umh-dashboard',
            'Bookings',
            'Bookings',
            Constants::CAP_MANAGE_BOOKINGS, // e.g., 'umh_manage_bookings'
            'umh-bookings',
            [$this, 'render_page']
        );
    }

    public function handle_update_status() {
        check_admin_referer('umh_booking_nonce');
        
        // SECURITY: Cek permission level tinggi untuk mengubah status
        if (!current_user_can(Constants::CAP_MANAGE_BOOKINGS)) {
            wp_die('Unauthorized: Anda tidak memiliki izin mengelola booking.', 403);
        }

        global $wpdb;
        $id = absint($_GET['id']);
        $new_status = sanitize_text_field($_GET['status']);

        // 1. Ambil Data Lama (Snapshot untuk Audit)
        $old_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}umh_bookings WHERE id = %d", $id), ARRAY_A);

        if (!$old_data) wp_die('Booking not found');

        // 2. Update Status
        $updated = $wpdb->update(
            $wpdb->prefix . 'umh_bookings', 
            ['status' => $new_status], 
            ['id' => $id]
        );

        if ($updated !== false) {
            // 3. ENTERPRISE FEATURE: Catat Audit Log
            AuditLogService::log(
                'update_status', 
                'booking', 
                $id, 
                ['status' => $old_data['status']], // Data Lama
                ['status' => $new_status]          // Data Baru
            );

            // Opsional: Trigger notifikasi lain jika status 'paid'
            if ($new_status === 'paid') {
                do_action('umh_booking_paid', $id);
            }
        }

        wp_redirect(admin_url('admin.php?page=umh-bookings&message=status_updated'));
        exit;
    }

    public function render_page() {
        // SECURITY: Double check saat render
        if (!current_user_can(Constants::CAP_MANAGE_BOOKINGS)) {
            wp_die('Access Denied');
        }

        // Repository sekarang akan otomatis memfilter berdasarkan cabang user (lihat update Repository di bawah)
        $bookings = $this->repo->findAllWithDetails(); 
        
        View::render('admin/bookings/list', ['bookings' => $bookings]);
    }
}