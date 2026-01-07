<?php
// File: RoleManager.php
// Location: src/Config/RoleManager.php

namespace UmhMgmt\Config;

class RoleManager {
    public static function init() {
        self::register_roles();
    }

    public static function register_roles() {
        // 1. ROLE JEMAAH (Customer)
        add_role('umh_jemaah', 'Jamaah Umroh', [
            'read' => true,
            'umh_view_dashboard_jemaah' => true, // Hanya bisa lihat dashboard jemaah
            'umh_upload_documents' => true,
        ]);

        // 2. ROLE STAFF (Operasional Harian)
        add_role('umh_staff', 'Staff Operasional', [
            'read' => true,
            'umh_view_dashboard' => true,
            'umh_manage_bookings' => true, // Bisa edit booking tapi terbatas
            'upload_files' => true,
        ]);

        // 3. ROLE AGEN (Mitra)
        add_role('umh_agent', 'Agen Travel', [
            'read' => true,
            'umh_view_dashboard_agent' => true,
            'umh_create_booking' => true,
            'umh_view_own_commission' => true,
        ]);

        // 4. ROLE CABANG (Branch Manager)
        add_role('umh_branch_manager', 'Kepala Cabang', [
            'read' => true,
            'umh_view_dashboard' => true,
            'umh_manage_branch_bookings' => true, // Hanya booking cabang dia (via BranchScopeTrait)
            'umh_view_finance_summary' => true,
        ]);

        // 5. ROLE OWNER & MANAGER (Super User)
        // (Biasanya pakai Administrator WP, tapi kita buat spesifik jika perlu)
        add_role('umh_manager', 'General Manager', [
            'read' => true,
            'manage_options' => true, // Akses penuh
            'umh_manage_all_branches' => true,
        ]);
    }

    public static function remove_roles() {
        $roles = ['umh_jemaah', 'umh_staff', 'umh_agent', 'umh_branch_manager', 'umh_manager'];
        foreach ($roles as $role) {
            remove_role($role);
        }
    }
}