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
}
