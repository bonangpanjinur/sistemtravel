<?php
// Path: src/Repositories/BookingRepository.php

namespace App\Repositories;

use App\Interfaces\DatabaseInterface;
// use App\Utils\BranchScopeTrait; // Uncomment jika Trait sudah direfactor ke namespace baru

class BookingRepository {
    // use BranchScopeTrait; 

    private $db;
    private $table;
    private $table_departures;
    private $table_packages;
    private $table_users;
    private $table_branches;
    private $table_invoices;

    public function __construct(DatabaseInterface $db) {
        $this->db = $db;
        $prefix = $this->db->prefix();
        
        // Menggunakan prefix 'umh_' sesuai kode legacy Anda
        $this->table = $prefix . 'umh_bookings';
        $this->table_departures = $prefix . 'umh_departures';
        $this->table_packages = $prefix . 'umh_packages'; // Asumsi prefix konsisten
        $this->table_users = $prefix . 'users';
        $this->table_branches = $prefix . 'umh_branches';
        $this->table_invoices = $prefix . 'umh_invoices';
    }

    /**
     * Helper sementara untuk menggantikan BranchScopeTrait sampai trait tersebut direfactor.
     * Mengembalikan potongan SQL untuk filter cabang berdasarkan user yang login.
     */
    protected function getBranchScopeSQL($column) {
        // Logika sederhana: jika user punya meta 'umh_branch_id', filter query.
        if (!function_exists('get_current_user_id')) return '';
        
        $user_id = get_current_user_id();
        // Cek apakah user adalah admin pusat (bisa diimplementasikan logic role check di sini)
        // Untuk sekarang kita simulasi ambil meta
        $branch_id = get_user_meta($user_id, 'umh_branch_id', true);
        
        if ($branch_id) {
            return $this->db->prepare(" AND {$column} = %d ", $branch_id);
        }
        
        return '';
    }

    /**
     * Mengambil semua booking dengan detail lengkap (Join Tables).
     */
    public function findAllWithDetails() {
        $branch_sql = $this->getBranchScopeSQL('b.branch_id');

        $sql = "
            SELECT b.*, d.departure_date, p.name as package_name, 
                   u.display_name as customer_name, br.name as branch_name
            FROM {$this->table} b
            JOIN {$this->table_departures} d ON b.departure_id = d.id
            JOIN {$this->table_packages} p ON d.package_id = p.id
            LEFT JOIN {$this->table_users} u ON b.customer_user_id = u.ID
            LEFT JOIN {$this->table_branches} br ON b.branch_id = br.id
            WHERE 1=1 
            {$branch_sql}
            ORDER BY b.created_at DESC
        ";

        return $this->db->get_results($sql);
    }

    /**
     * Mencari booking aktif berdasarkan User ID.
     */
    public function findActiveBooking($userId) {
        $sql = $this->db->prepare(
            "SELECT * FROM {$this->table} 
             WHERE user_id = %d AND status != 'cancelled' 
             ORDER BY created_at DESC LIMIT 1",
            $userId
        );
        return $this->db->get_row($sql);
    }

    /**
     * Mengambil tagihan yang belum dibayar.
     */
    public function getUnpaidBills($userId) {
        $sql = $this->db->prepare(
            "SELECT * FROM {$this->table_invoices} 
             WHERE user_id = %d AND status = 'unpaid'",
            $userId
        );
        return $this->db->get_results($sql);
    }

    /**
     * Mencari data booking berdasarkan ID.
     */
    public function find($id) {
        $sql = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = %d", $id);
        return $this->db->get_row($sql);
    }

    /**
     * Membuat data booking baru.
     */
    public function create($data) {
        // Fallback untuk branch_id jika tidak dikirim
        if (!isset($data['branch_id']) && function_exists('get_current_user_id')) {
            $data['branch_id'] = get_user_meta(get_current_user_id(), 'umh_branch_id', true) ?: 0;
        }

        // Generate default code jika tidak ada
        if (!isset($data['code'])) {
            $data['code'] = 'BKG-' . strtoupper(uniqid());
        }

        // Pastikan created_at terisi
        if (!isset($data['created_at'])) {
            $data['created_at'] = current_time('mysql');
        }

        $this->db->insert($this->table, $data);
        return $this->db->last_insert_id();
    }
    
    /**
     * Update data booking
     */
    public function update($id, $data) {
        return $this->db->update($this->table, $data, ['id' => $id]);
    }

    /**
     * Mengurangi kuota keberangkatan.
     */
    public function decreaseQuota($departure_id, $count) {
        $sql = $this->db->prepare(
            "UPDATE {$this->table_departures} 
             SET available_seats = available_seats - %d 
             WHERE id = %d",
            $count, $departure_id
        );
        return $this->db->query($sql);
    }

    /**
     * Menghitung total booking dengan scope cabang.
     */
    public function countAll() {
        $branch_sql = $this->getBranchScopeSQL('branch_id');
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE 1=1 {$branch_sql}";
        return (int) $this->db->get_var($sql);
    }

    /**
     * Menghitung total revenue (paid) dengan scope cabang.
     */
    public function sumRevenue() {
        $branch_sql = $this->getBranchScopeSQL('branch_id');
        $sql = "SELECT SUM(total_price) FROM {$this->table} WHERE status = 'paid' {$branch_sql}";
        return (float) $this->db->get_var($sql);
    }
}