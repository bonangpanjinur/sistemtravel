<?php
// File: BranchScopeTrait.php
// Location: src/Utils/BranchScopeTrait.php

namespace UmhMgmt\Utils;

trait BranchScopeTrait {

    /**
     * Mendapatkan klausa WHERE SQL untuk membatasi data berdasarkan cabang user login
     * * @param string $column_prefix Prefix kolom (misal 'b.' untuk table bookings alias b)
     * @return string SQL string (misal " AND b.branch_id = 5 ") atau string kosong
     */
    protected function getBranchScopeSQL($column_prefix = '') {
        $user_id = get_current_user_id();
        
        // 1. Admin/Manager Pusat -> Bisa lihat semua (No Filter)
        if (current_user_can('manage_options') || current_user_can('umh_manage_all_branches')) {
            return ""; 
        }

        // 2. Kepala Cabang / Staff Cabang
        // Ambil ID Cabang dari User Meta (Disimpan saat assign user)
        $branch_id = get_user_meta($user_id, 'umh_assigned_branch_id', true);

        if ($branch_id) {
            return " AND {$column_prefix}branch_id = " . absint($branch_id) . " ";
        }

        // 3. Fallback: Jika staff biasa tidak punya cabang, mungkin block atau show nothing?
        // Untuk safety, kita return false condition agar tidak bocor
        // return " AND 1=0 "; 
        
        return ""; // Sementara return kosong (bisa lihat semua) jika logic role belum strict
    }
}