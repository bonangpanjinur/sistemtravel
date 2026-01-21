<?php
// Path: src/Repositories/CRMRepository.php

namespace App\Repositories;

use App\Interfaces\DatabaseInterface;

class CRMRepository {
    private $db;
    private $table_users;
    private $table_usermeta;
    private $table_leads;

    public function __construct(DatabaseInterface $db) {
        $this->db = $db;
        $prefix = $this->db->prefix();
        
        $this->table_users = $prefix . 'users';
        $this->table_usermeta = $prefix . 'usermeta';
        // Menggunakan nama tabel leads dari kode legacy Anda
        $this->table_leads = $prefix . 'umh_leads'; 
    }

    /**
     * --- JEMAAH SECTION ---
     */

    public function getAllJemaah($limit = 20, $offset = 0, $search = '') {
        $sql = "SELECT u.ID, u.user_email, u.display_name, m.meta_value as phone 
                FROM {$this->table_users} u 
                LEFT JOIN {$this->table_usermeta} m ON u.ID = m.user_id AND m.meta_key = 'phone_number'
                WHERE 1=1";

        // TODO: Tambahkan filter role spesifik jika perlu (misal: WHERE u.role = 'jemaah')
        
        if (!empty($search)) {
            $search_term = '%' . $search . '%';
            $sql .= $this->db->prepare(" AND (u.display_name LIKE %s OR u.user_email LIKE %s)", $search_term, $search_term);
        }

        $sql .= " ORDER BY u.registered DESC LIMIT $limit OFFSET $offset";

        return $this->db->get_results($sql);
    }

    public function getJemaahDetail($id) {
        $sql = $this->db->prepare("SELECT * FROM {$this->table_users} WHERE ID = %d", $id);
        $user = $this->db->get_row($sql);
        
        if ($user) {
            // Ambil meta data tambahan
            $meta_sql = $this->db->prepare("SELECT meta_key, meta_value FROM {$this->table_usermeta} WHERE user_id = %d", $id);
            $metas = $this->db->get_results($meta_sql);
            foreach ($metas as $meta) {
                $user->{$meta->meta_key} = $meta->meta_value;
            }
        }
        
        return $user;
    }
    
    /**
     * --- LEADS SECTION (Restored from Legacy) ---
     */

    public function getLeads() {
        // Menggunakan DatabaseInterface, bukan $wpdb langsung
        return $this->db->get_results("SELECT * FROM {$this->table_leads} ORDER BY created_at DESC");
    }

    public function updateLeadStatus($id, $status) {
        // Refactor update statement
        return $this->db->update(
            $this->table_leads,
            ['status' => $status],
            ['id' => $id],
            ['%s'], // Format value
            ['%d']  // Format where
        );
    }

    /**
     * --- STATS SECTION ---
     */
    
    public function getStats() {
        $total_jemaah = $this->db->get_var("SELECT COUNT(*) FROM {$this->table_users}");
        
        // Cek apakah tabel leads ada sebelum query untuk menghindari error jika tabel belum dibuat
        $total_leads = 0;
        $check_table = $this->db->get_var("SHOW TABLES LIKE '{$this->table_leads}'");
        if ($check_table == $this->table_leads) {
            $total_leads = $this->db->get_var("SELECT COUNT(*) FROM {$this->table_leads}");
        }

        return [
            'total_jemaah' => $total_jemaah,
            'total_leads' => $total_leads
        ];
    }
}