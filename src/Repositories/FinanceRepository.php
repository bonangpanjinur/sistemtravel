<?php
// Path: src/Repositories/FinanceRepository.php

namespace App\Repositories;

use App\Interfaces\DatabaseInterface;

class FinanceRepository {
    private $db;
    private $table_payments;
    private $table_journal;
    private $table_bookings;
    private $table_departures;
    private $table_packages;
    private $table_users;

    public function __construct(DatabaseInterface $db) {
        $this->db = $db;
        $prefix = $this->db->prefix();
        
        // Menggunakan prefix 'umh_' agar konsisten dengan kode legacy
        $this->table_payments = $prefix . 'umh_payments';
        $this->table_journal = $prefix . 'umh_journal_entries'; // Dari kode legacy
        $this->table_bookings = $prefix . 'umh_bookings';
        $this->table_departures = $prefix . 'umh_departures';
        $this->table_packages = $prefix . 'umh_packages';
        $this->table_users = $prefix . 'users';
    }

    /**
     * --- PAYMENT SECTION (New/Standard) ---
     */

    public function getRecentTransactions($limit = 10) {
        $sql = "SELECT p.*, u.display_name as user_name 
                FROM {$this->table_payments} p
                LEFT JOIN {$this->table_users} u ON p.user_id = u.ID
                ORDER BY p.payment_date DESC LIMIT $limit";
        return $this->db->get_results($sql);
    }

    public function getRevenueReport($startDate, $endDate) {
        $sql = $this->db->prepare(
            "SELECT DATE(payment_date) as date, SUM(amount) as total 
             FROM {$this->table_payments} 
             WHERE status = 'paid' AND payment_date BETWEEN %s AND %s 
             GROUP BY DATE(payment_date)",
            $startDate,
            $endDate
        );
        return $this->db->get_results($sql);
    }

    public function recordPayment($data) {
        return $this->db->insert($this->table_payments, $data);
    }
    
    public function getTotalRevenue() {
        return $this->db->get_var("SELECT SUM(amount) FROM {$this->table_payments} WHERE status = 'paid'");
    }

    /**
     * --- ACCOUNTING JOURNAL SECTION (Restored from Legacy) ---
     */

    public function addJournalEntry($data) {
        // Menggantikan fungsi 'addEntry' lama
        // Data yang diharapkan: ref_id, code, description, debit, credit
        return $this->db->insert($this->table_journal, [
            'transaction_ref_id' => $data['ref_id'],
            'account_code'       => $data['code'],
            'description'        => $data['description'],
            'debit'              => $data['debit'] ?? 0,
            'credit'             => $data['credit'] ?? 0,
            'created_at'         => current_time('mysql') // Tambahan timestamp
        ]);
    }

    /**
     * --- BOOKING FINANCE SECTION (Restored from Legacy) ---
     */

    public function getPendingPayments() {
        $sql = "
            SELECT b.*, d.departure_date, p.name as package_name 
            FROM {$this->table_bookings} b
            JOIN {$this->table_departures} d ON b.departure_id = d.id
            JOIN {$this->table_packages} p ON d.package_id = p.id
            WHERE b.status = 'pending'
            ORDER BY b.created_at DESC
        ";
        return $this->db->get_results($sql);
    }
}