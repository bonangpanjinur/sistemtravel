<?php
// File: AgentCommissionController.php
// Location: src/Controllers/Admin/AgentCommissionController.php

namespace UmhMgmt\Controllers\Admin;

use UmhMgmt\Utils\View;

class AgentCommissionController {

    public function __construct() {
        add_action('admin_menu', [$this, 'add_menu']);
        add_action('admin_post_umh_verify_commission', [$this, 'handle_verify']);
        add_action('admin_post_umh_pay_commission', [$this, 'handle_payout']);
    }

    public function add_menu() {
        // Menambahkan submenu di bawah menu utama plugin (umh-dashboard)
        add_submenu_page(
            'umh-dashboard',
            'Manajemen Komisi Agen',
            'Komisi Agen',
            'manage_options', // Capability (Admin/Manager)
            'umh-agent-commissions',
            [$this, 'render_page']
        );
    }

    public function handle_verify() {
        check_admin_referer('umh_commission_action');
        if (!current_user_can('manage_options')) wp_die('Unauthorized');

        global $wpdb;
        $id = absint($_POST['id']);
        
        // Ubah status dari pending -> verified (siap bayar)
        $wpdb->update(
            $wpdb->prefix . 'umh_commissions',
            ['status' => 'verified'],
            ['id' => $id]
        );

        wp_redirect(admin_url('admin.php?page=umh-agent-commissions&msg=verified'));
        exit;
    }

    public function handle_payout() {
        check_admin_referer('umh_commission_action');
        if (!current_user_can('manage_options')) wp_die('Unauthorized');

        global $wpdb;
        $id = absint($_POST['id']);

        // Ubah status dari verified -> paid
        $wpdb->update(
            $wpdb->prefix . 'umh_commissions',
            ['status' => 'paid'],
            ['id' => $id]
        );

        // TODO: Di sini idealnya memanggil FinanceService untuk mencatat Pengeluaran (Expense) di Jurnal

        wp_redirect(admin_url('admin.php?page=umh-agent-commissions&msg=paid'));
        exit;
    }

    public function render_page() {
        global $wpdb;

        // Filter Status
        $filter_status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
        $where_sql = "";
        if ($filter_status) {
            $where_sql = $wpdb->prepare("AND c.status = %s", $filter_status);
        }

        // Query Komisi Join dengan User (Agen) dan Booking
        $query = "
            SELECT c.*, 
                   u.display_name as agent_name, 
                   u.user_email as agent_email,
                   b.total_price as booking_amount,
                   p.name as package_name
            FROM {$wpdb->prefix}umh_commissions c
            LEFT JOIN {$wpdb->users} u ON c.agent_id = u.ID
            LEFT JOIN {$wpdb->prefix}umh_bookings b ON c.booking_id = b.id
            LEFT JOIN {$wpdb->prefix}umh_departures d ON b.departure_id = d.id
            LEFT JOIN {$wpdb->prefix}umh_packages p ON d.package_id = p.id
            WHERE 1=1 $where_sql
            ORDER BY c.created_at DESC
            LIMIT 100
        ";

        $commissions = $wpdb->get_results($query);

        // Hitung Statistik Ringkas
        $stats = $wpdb->get_row("
            SELECT 
                SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END) as pending_total,
                SUM(CASE WHEN status = 'verified' THEN amount ELSE 0 END) as verified_total,
                SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END) as paid_total
            FROM {$wpdb->prefix}umh_commissions
        ");

        View::render('admin/agents/commissions', [
            'commissions' => $commissions,
            'stats' => $stats,
            'current_status' => $filter_status
        ]);
    }
}