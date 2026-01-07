<?php
// Folder: src/Utils/
// File: BranchScopeTrait.php

namespace UmhMgmt\Utils;

use UmhMgmt\Config\Constants;

/**
 * Trait untuk memfilter query SQL berdasarkan cabang user login.
 * Menjamin admin cabang Surabaya TIDAK BISA melihat data Jakarta.
 */
trait BranchScopeTrait {

    /**
     * Menghasilkan string klausa WHERE SQL.
     * * @param string $columnName Nama kolom branch_id di tabel target (default: 'branch_id')
     * @return string Contoh: " AND branch_id = 5 " atau "" (kosong jika owner)
     */
    protected function getBranchScopeSQL($columnName = 'branch_id') {
        // 1. Jika user memiliki kapabilitas 'manage_all_branches' (Owner/GM), tidak ada filter
        if (current_user_can('umh_manage_all_branches') || current_user_can('administrator')) {
            return ""; 
        }

        // 2. Ambil ID cabang user yang sedang login
        $user_branch_id = (int) get_user_meta(get_current_user_id(), 'umh_branch_id', true);

        // 3. Jika tidak punya cabang tapi bukan admin, blokir akses (return kondisi false)
        if ($user_branch_id === 0) {
            return " AND 1=0 "; // Security fail-safe
        }

        // 4. Return filter
        return " AND {$columnName} = {$user_branch_id} ";
    }
}