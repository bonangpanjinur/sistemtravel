<?php
// File: ReportController.php
// Location: src/Controllers/Admin/ReportController.php

namespace UmhMgmt\Controllers\Admin;

use UmhMgmt\Utils\View;
use UmhMgmt\Utils\BranchScopeTrait;

class ReportController {
    use BranchScopeTrait;

    public function __construct() {
        add_action('admin_menu', [$this, 'add_menu']);
    }

    public function add_menu() {
        add_submenu_page(
            'umh-dashboard',
            'Laporan Keuangan',
            'Laporan Keuangan',
            'umh_view_finance_summary', // Capability khusus (perlu di add di RoleManager)
            'umh-reports',
            [$this, 'render_page']
        );
    }

    public function render_page() {
        global $wpdb;

        // Filter Tanggal
        $start_date = isset($_GET['start']) ? sanitize_text_field($_GET['start']) : date('Y-m-01');
        $end_date = isset($_GET['end']) ? sanitize_text_field($_GET['end']) : date('Y-m-t');

        // Scope Cabang (Otomatis filter jika user adalah Kepala Cabang)
        $branch_sql = $this->getBranchScopeSQL('b.'); // b = alias tabel bookings

        // 1. Total Pendapatan (Gross) - Booking Status Paid
        $revenue = $wpdb->get_var($wpdb->prepare("
            SELECT SUM(total_price) 
            FROM {$wpdb->prefix}umh_bookings b
            WHERE b.status = 'paid' 
            AND DATE(b.created_at) BETWEEN %s AND %s
            $branch_sql
        ", $start_date, $end_date));

        // 2. Total Pengeluaran Komisi Agen
        $commissions = $wpdb->get_var($wpdb->prepare("
            SELECT SUM(c.amount)
            FROM {$wpdb->prefix}umh_commissions c
            JOIN {$wpdb->prefix}umh_bookings b ON c.booking_id = b.id
            WHERE c.status = 'paid'
            AND DATE(c.created_at) BETWEEN %s AND %s
            $branch_sql
        ", $start_date, $end_date));

        // 3. Breakdown per Paket
        $breakdown = $wpdb->get_results($wpdb->prepare("
            SELECT p.name as package_name, COUNT(b.id) as total_pax, SUM(b.total_price) as total_omzet
            FROM {$wpdb->prefix}umh_bookings b
            JOIN {$wpdb->prefix}umh_departures d ON b.departure_id = d.id
            JOIN {$wpdb->prefix}umh_packages p ON d.package_id = p.id
            WHERE b.status = 'paid'
            AND DATE(b.created_at) BETWEEN %s AND %s
            $branch_sql
            GROUP BY p.id
        ", $start_date, $end_date));

        View::render('admin/reports/financial-report', [
            'start_date' => $start_date,
            'end_date' => $end_date,
            'revenue' => $revenue ?: 0,
            'commissions' => $commissions ?: 0,
            'breakdown' => $breakdown,
            'is_branch_view' => !current_user_can('manage_options') // True jika bukan admin pusat
        ]);
    }
}