<?php
// File: FinanceController.php
// Location: src/Controllers/Admin/FinanceController.php

namespace UmhMgmt\Controllers\Admin;

use UmhMgmt\Utils\View;

class FinanceController {

    public function __construct() {
        add_action('admin_menu', [$this, 'add_menu']);
        add_action('admin_post_umh_verify_payment', [$this, 'handle_verify_payment']);
        add_action('admin_post_umh_reject_payment', [$this, 'handle_reject_payment']);
    }

    public function add_menu() {
        add_submenu_page(
            'umh-dashboard',
            'Keuangan & Pembayaran',
            'Finance',
            'manage_options',
            'umh-finance',
            [$this, 'render_page']
        );
    }

    public function handle_verify_payment() {
        check_admin_referer('umh_finance_action');
        if (!current_user_can('manage_options')) wp_die('Unauthorized');

        global $wpdb;
        $payment_id = absint($_POST['payment_id']);
        $admin_id = get_current_user_id();

        // 1. Update Status Pembayaran -> Verified
        $wpdb->update(
            $wpdb->prefix . 'umh_payments',
            [
                'status' => 'verified',
                'verified_by' => $admin_id,
                'verified_at' => current_time('mysql')
            ],
            ['id' => $payment_id]
        );

        // 2. Cek Total Pembayaran Booking Terkait
        $payment = $wpdb->get_row($wpdb->prepare("SELECT booking_id FROM {$wpdb->prefix}umh_payments WHERE id = %d", $payment_id));
        $booking_id = $payment->booking_id;

        $booking = $wpdb->get_row($wpdb->prepare("SELECT total_price FROM {$wpdb->prefix}umh_bookings WHERE id = %d", $booking_id));
        
        $total_verified = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(amount) FROM {$wpdb->prefix}umh_payments WHERE booking_id = %d AND status = 'verified'",
            $booking_id
        ));

        // 3. Logic Pelunasan Otomatis
        // Jika total verified >= total tagihan, set booking jadi PAID
        if ($total_verified >= $booking->total_price) {
            $wpdb->update(
                $wpdb->prefix . 'umh_bookings',
                ['status' => 'paid'],
                ['id' => $booking_id]
            );
        }

        wp_redirect(admin_url('admin.php?page=umh-finance&msg=verified'));
        exit;
    }

    public function handle_reject_payment() {
        check_admin_referer('umh_finance_action');
        global $wpdb;
        $payment_id = absint($_POST['payment_id']);

        $wpdb->update(
            $wpdb->prefix . 'umh_payments',
            ['status' => 'rejected'],
            ['id' => $payment_id]
        );

        wp_redirect(admin_url('admin.php?page=umh-finance&msg=rejected'));
        exit;
    }

    public function render_page() {
        global $wpdb;

        // Ambil Pembayaran Pending (Butuh Verifikasi)
        $pending_payments = $wpdb->get_results("
            SELECT p.*, b.total_price as booking_total, u.display_name as jemaah_name
            FROM {$wpdb->prefix}umh_payments p
            JOIN {$wpdb->prefix}umh_bookings b ON p.booking_id = b.id
            JOIN {$wpdb->users} u ON p.user_id = u.ID
            WHERE p.status = 'pending_verification'
            ORDER BY p.created_at ASC
        ");

        // Ambil History Terakhir (50)
        $history = $wpdb->get_results("
            SELECT p.*, u.display_name as jemaah_name 
            FROM {$wpdb->prefix}umh_payments p
            JOIN {$wpdb->users} u ON p.user_id = u.ID
            WHERE p.status != 'pending_verification'
            ORDER BY p.verified_at DESC LIMIT 50
        ");

        View::render('admin/finance', [
            'pending_payments' => $pending_payments,
            'history' => $history
        ]);
    }
}