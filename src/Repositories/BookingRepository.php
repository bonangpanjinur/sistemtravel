<?php
// Folder: src/Repositories/
// File: BookingRepository.php

namespace UmhMgmt\Repositories;

use UmhMgmt\Utils\BranchScopeTrait; // Gunakan Trait

class BookingRepository {
    use BranchScopeTrait; // Aktifkan Trait

    private $table;
    private $departure_table;
    private $wpdb;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table = $wpdb->prefix . 'umh_bookings';
        $this->departure_table = $wpdb->prefix . 'umh_departures';
    }

    // [UPGRADE] Mengambil semua booking dengan filter cabang otomatis
    public function findAllWithDetails() {
        // Ambil klausa WHERE dinamis (kosong jika Owner, berisi "AND branch_id=X" jika admin cabang)
        $branch_sql = $this->getBranchScopeSQL('b.branch_id');

        $sql = "
            SELECT b.*, d.departure_date, p.name as package_name, u.display_name as customer_name, br.name as branch_name
            FROM {$this->table} b
            JOIN {$this->departure_table} d ON b.departure_id = d.id
            JOIN {$this->wpdb->prefix}umh_packages p ON d.package_id = p.id
            LEFT JOIN {$this->wpdb->prefix}users u ON b.customer_user_id = u.ID
            LEFT JOIN {$this->wpdb->prefix}umh_branches br ON b.branch_id = br.id
            WHERE 1=1 
            {$branch_sql}  -- Inject Filter Cabang Di Sini
            ORDER BY b.created_at DESC
        ";

        return $this->wpdb->get_results($sql);
    }

    public function create($data) {
        // Logic create tetap sama...
        // Pastikan branch_id terisi saat create
        $branch_id = isset($data['branch_id']) ? $data['branch_id'] : get_user_meta(get_current_user_id(), 'umh_branch_id', true);

        $this->wpdb->insert($this->table, [
            'departure_id' => $data['departure_id'],
            'branch_id' => $branch_id ?: 0, // Default 0 (Pusat) jika tidak ada
            'customer_user_id' => $data['customer_user_id'],
            'total_price' => $data['total_price'],
            'status' => 'pending'
        ]);
        return $this->wpdb->insert_id;
    }

    public function decreaseQuota($departure_id, $count) {
        $this->wpdb->query($this->wpdb->prepare(
            "UPDATE {$this->departure_table} SET available_seats = available_seats - %d WHERE id = %d",
            $count, $departure_id
        ));
    }

    // Statistik juga harus kena filter cabang
    public function countAll() {
        $branch_sql = $this->getBranchScopeSQL('branch_id');
        return (int) $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->table} WHERE 1=1 {$branch_sql}");
    }

    public function sumRevenue() {
        $branch_sql = $this->getBranchScopeSQL('branch_id');
        return (float) $this->wpdb->get_var("SELECT SUM(total_price) FROM {$this->table} WHERE status = 'paid' {$branch_sql}");
    }
}