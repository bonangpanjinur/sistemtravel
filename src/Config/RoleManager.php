<?php
// File: RoleManager.php
// Location: src/Config/RoleManager.php

namespace UmhMgmt\Config;

class RoleManager {

    /**
     * Inisialisasi Role saat Plugin Aktif
     */
    public static function init() {
        // Daftarkan Role Baru
        self::add_owner_role();
        self::add_finance_role();
        self::add_operational_role();
        self::add_branch_manager_role();
        self::add_agent_role();
        self::add_jemaah_role();

        // Tambahkan Capabilities ke Admin (Super User)
        self::update_admin_caps();
    }

    /**
     * 1. Role OWNER (Pemilik Bisnis)
     * Fokus: Melihat Laporan, Audit Log, Dashboard Analitik. Tidak input teknis.
     */
    private static function add_owner_role() {
        add_role('umh_owner', 'Travel Owner', [
            'read' => true,
            'umh_view_dashboard' => true,      // Lihat Dashboard Utama
            'umh_view_financial_reports' => true, // Lihat Laporan Keuangan
            'umh_view_audit_log' => true,      // Lihat siapa login/edit data
            'umh_manage_settings' => true,     // Ubah setting global (opsional)
        ]);
    }

    /**
     * 2. Role STAFF KEUANGAN (Finance)
     * Fokus: Pembayaran, Invoice, Jurnal Akuntansi.
     */
    private static function add_finance_role() {
        add_role('umh_finance', 'Staff Keuangan', [
            'read' => true,
            'umh_view_dashboard' => true,
            'umh_manage_bookings' => true,     // Perlu lihat booking untuk verifikasi
            'umh_manage_payments' => true,     // CRUD Pembayaran
            'umh_manage_accounting' => true,   // Akses General Ledger
            'umh_view_financial_reports' => true,
            'upload_files' => true,            // Upload bukti bayar/invoice
        ]);
    }

    /**
     * 3. Role STAFF OPERASIONAL (Handling)
     * Fokus: Manifest, Visa, Koper, Rooming.
     */
    private static function add_operational_role() {
        add_role('umh_operational', 'Staff Operasional', [
            'read' => true,
            'umh_view_dashboard' => true,
            'umh_manage_departures' => true,   // Kelola Keberangkatan
            'umh_manage_manifest' => true,     // Cetak Manifest & Siskopatuh
            'umh_manage_passport' => true,     // Input Data Paspor
            'umh_manage_rooming' => true,      // Atur Kamar Hotel
            'umh_manage_luggage' => true,      // Scan Koper
            'umh_scan_attendance' => true,     // Scan Absensi
            'upload_files' => true,            // Upload Dokumen Jemaah
        ]);
    }

    /**
     * 4. Role KEPALA CABANG (Branch Manager)
     * Fokus: Booking Cabang, Laporan Cabang. 
     * NOTE: Pembatasan data (Data Scoping) dilakukan di Controller/BranchScopeTrait.
     */
    private static function add_branch_manager_role() {
        add_role('umh_branch_manager', 'Kepala Cabang', [
            'read' => true,
            'umh_view_dashboard' => true,
            'umh_manage_bookings' => true,     // Create Booking (Scoped)
            'umh_view_branch_reports' => true, // Laporan khusus cabang
            'umh_manage_customers' => true,    // Data Jemaah Cabang
        ]);
    }

    /**
     * 5. Role AGEN TRAVEL
     * Fokus: Dashboard Agen, Komisi, Link Referral.
     */
    private static function add_agent_role() {
        add_role('umh_agent', 'Agen Travel', [
            'read' => true,
            'umh_access_agent_area' => true,   // Akses Halaman Frontend Agen
            'umh_create_bookings' => true,     // Booking atas nama jemaah
            'umh_view_commissions' => true,    // Lihat komisi sendiri
            'umh_view_network' => true,        // Lihat Downline (MLM)
        ]);
    }

    /**
     * 6. Role JEMAAH (Customer)
     * Fokus: Lihat status, upload dokumen, panduan.
     */
    private static function add_jemaah_role() {
        add_role('umh_jemaah', 'Jemaah', [
            'read' => true,
            'umh_access_jemaah_area' => true,  // Dashboard Jemaah
            'umh_view_own_booking' => true,    // Lihat booking sendiri
            'umh_upload_documents' => true,    // Upload KTP/Paspor sendiri
        ]);
    }

    /**
     * Update Administrator (Super Admin)
     * Pastikan Admin WP punya semua akses ini.
     */
    private static function update_admin_caps() {
        $role = get_role('administrator');
        if ($role) {
            $caps = [
                'umh_view_dashboard', 
                'umh_view_financial_reports', 
                'umh_manage_settings',
                'umh_manage_master_data',
                'umh_manage_bookings',
                'umh_manage_payments',
                'umh_manage_accounting',
                'umh_manage_departures',
                'umh_manage_manifest',
                'umh_manage_passport',
                'umh_manage_rooming',
                'umh_manage_luggage',
                'umh_scan_attendance',
                'umh_manage_branches',
                'umh_manage_agents',
                'umh_view_audit_log'
            ];
            foreach ($caps as $cap) {
                $role->add_cap($cap);
            }
        }
    }

    /**
     * Helper: Cek Izin Akses di Controller
     * Contoh: RoleManager::check_access('umh_manage_payments');
     */
    public static function check_access($cap) {
        if (!current_user_can($cap) && !current_user_can('administrator')) {
            wp_die(__('Maaf, Anda tidak memiliki akses ke halaman ini.', 'umh-mgmt'));
        }
    }

    /**
     * Helper: Hapus semua role saat plugin uninstall
     */
    public static function remove_roles() {
        remove_role('umh_owner');
        remove_role('umh_finance');
        remove_role('umh_operational');
        remove_role('umh_branch_manager');
        remove_role('umh_agent');
        remove_role('umh_jemaah');
    }
}