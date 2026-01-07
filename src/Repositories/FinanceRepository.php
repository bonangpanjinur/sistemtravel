<?php
namespace UmhMgmt\Repositories;

class FinanceRepository {
    private $table;

    public function __construct() {
        global $wpdb;
        $this->table = $wpdb->prefix . 'umh_journal_entries';
    }

    public function addEntry($data) {
        global $wpdb;
        return $wpdb->insert($this->table, [
            'transaction_ref_id' => $data['ref_id'],
            'account_code' => $data['code'],
            'description' => $data['description'],
            'debit' => $data['debit'] ?? 0,
            'credit' => $data['credit'] ?? 0
        ]);
    }

    public function getPendingPayments() {
        global $wpdb;
        return $wpdb->get_results("
            SELECT b.*, d.departure_date, p.name as package_name 
            FROM {$wpdb->prefix}umh_bookings b
            JOIN {$wpdb->prefix}umh_departures d ON b.departure_id = d.id
            JOIN {$wpdb->prefix}umh_packages p ON d.package_id = p.id
            WHERE b.status = 'pending'
            ORDER BY b.created_at DESC
        ");
    }
}
