<?php
// src/Repositories/BookingRepository.php

namespace UmhMgmt\Repositories;

use UmhMgmt\Core\DB;
use UmhMgmt\Utils\BranchScopeTrait;

/**
 * Class BookingRepository
 * Menangani operasi database untuk data Booking.
 *
 * @package UmhMgmt\Repositories
 */
class BookingRepository {
    use BranchScopeTrait; // Mengaktifkan fitur filter cabang otomatis

    private $table;
    private $departure_table;
    private $wpdb;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table = $wpdb->prefix . 'umh_bookings';
        $this->departure_table = $wpdb->prefix . 'umh_departures';
    }

    /**
     * [LEGACY SUPPORTED] Mengambil semua booking dengan detail lengkap (Join Tables).
     * Menggunakan $wpdb langsung karena DB Wrapper saat ini belum support JOIN kompleks.
     */
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

    /**
     * Mencari booking aktif berdasarkan User ID.
     * Menggunakan DB Wrapper untuk keamanan dan clean code.
     *
     * @param int $userId
     * @return object|null
     */
    public function findActiveBooking($userId) {
        // REFACTOR: Menggunakan DB Wrapper
        return DB::table('umh_bookings')
            ->where('user_id', '=', $userId)
            ->where('status', '!=', 'cancelled')
            ->orderBy('created_at', 'DESC')
            ->first();
    }

    /**
     * Mengambil tagihan yang belum dibayar.
     *
     * @param int $userId
     * @return array
     */
    public function getUnpaidBills($userId) {
        return DB::table('umh_invoices')
            ->where('user_id', '=', $userId)
            ->where('status', '=', 'unpaid')
            ->get();
    }

    /**
     * Mencari data booking berdasarkan ID.
     *
     * @param int $id
     * @return object|null
     */
    public function find($id) {
        return DB::table('umh_bookings')
            ->where('id', '=', $id)
            ->first();
    }

    /**
     * Membuat data booking baru.
     * * @param array $data
     * @return int Insert ID
     */
    public function create($data) {
        // Pastikan branch_id terisi saat create
        $branch_id = isset($data['branch_id']) ? $data['branch_id'] : get_user_meta(get_current_user_id(), 'umh_branch_id', true);

        // Generate default code jika tidak ada
        $code = isset($data['code']) ? $data['code'] : 'BKG-' . strtoupper(uniqid());

        $this->wpdb->insert($this->table, [
            'code' => $code,
            'departure_id' => $data['departure_id'],
            'branch_id' => $branch_id ?: 0, // Default 0 (Pusat) jika tidak ada
            'customer_user_id' => $data['customer_user_id'],
            'total_price' => $data['total_price'],
            'status' => 'pending',
            'created_at' => current_time('mysql')
        ]);
        
        return $this->wpdb->insert_id;
    }

    /**
     * Mengurangi kuota keberangkatan.
     * * @param int $departure_id
     * @param int $count
     */
    public function decreaseQuota($departure_id, $count) {
        $this->wpdb->query($this->wpdb->prepare(
            "UPDATE {$this->departure_table} SET available_seats = available_seats - %d WHERE id = %d",
            $count, $departure_id
        ));
    }

    /**
     * Menghitung total booking dengan scope cabang.
     * * @return int
     */
    public function countAll() {
        $branch_sql = $this->getBranchScopeSQL('branch_id');
        return (int) $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->table} WHERE 1=1 {$branch_sql}");
    }

    /**
     * Menghitung total revenue (paid) dengan scope cabang.
     * * @return float
     */
    public function sumRevenue() {
        $branch_sql = $this->getBranchScopeSQL('branch_id');
        return (float) $this->wpdb->get_var("SELECT SUM(total_price) FROM {$this->table} WHERE status = 'paid' {$branch_sql}");
    }
}