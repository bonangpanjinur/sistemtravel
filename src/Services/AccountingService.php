<?php
// Folder: src/Services/
// File: AccountingService.php

namespace UmhMgmt\Services;

class AccountingService {
    private $wpdb;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    /**
     * Inisialisasi Chart of Accounts (COA) Standar Travel Umroh
     * Jalankan ini sekali saja (misal saat aktivasi plugin)
     */
    public function initCOA() {
        $accounts = [
            // ASSETS (1xxx)
            ['1001', 'Kas Besar', 'ASSET'],
            ['1002', 'Bank BCA', 'ASSET'],
            ['1003', 'Bank Mandiri', 'ASSET'],
            ['1004', 'Piutang Agen', 'ASSET'],
            
            // LIABILITIES (2xxx)
            ['2001', 'Uang Muka Jemaah (Deposit)', 'LIABILITY'],
            ['2002', 'Titipan Dana Visa', 'LIABILITY'],
            
            // REVENUE (4xxx)
            ['4001', 'Pendapatan Paket Umroh', 'REVENUE'],
            ['4002', 'Pendapatan LA / Handling', 'REVENUE'],
            
            // EXPENSES (5xxx)
            ['5001', 'Biaya Tiket Pesawat', 'EXPENSE'],
            ['5002', 'Biaya Hotel & Akomodasi', 'EXPENSE'],
            ['5003', 'Biaya Visa & Muassasah', 'EXPENSE'],
            ['5004', 'Komisi Agen', 'EXPENSE'],
            ['5005', 'Gaji Karyawan', 'EXPENSE'],
        ];

        foreach ($accounts as $acc) {
            $this->wpdb->replace(
                $this->wpdb->prefix . 'umh_gl_accounts',
                ['account_code' => $acc[0], 'account_name' => $acc[1], 'account_type' => $acc[2]],
                ['%s', '%s', '%s']
            );
        }
    }

    /**
     * Catat Transaksi Jurnal Umum (Double Entry)
     */
    public function recordTransaction($refNo, $description, $debitAccount, $creditAccount, $amount, $userId) {
        if ($amount <= 0) return false;

        $this->wpdb->query('START TRANSACTION');
        try {
            // 1. Entry Debit
            $this->wpdb->insert($this->wpdb->prefix . 'umh_gl_entries', [
                'transaction_date' => current_time('mysql'),
                'reference_no' => $refNo,
                'description' => $description,
                'account_code' => $debitAccount,
                'debit' => $amount,
                'credit' => 0,
                'created_by' => $userId
            ]);

            // 2. Entry Credit
            $this->wpdb->insert($this->wpdb->prefix . 'umh_gl_entries', [
                'transaction_date' => current_time('mysql'),
                'reference_no' => $refNo,
                'description' => $description,
                'account_code' => $creditAccount,
                'debit' => 0,
                'credit' => $amount,
                'created_by' => $userId
            ]);

            $this->wpdb->query('COMMIT');
            return true;
        } catch (\Exception $e) {
            $this->wpdb->query('ROLLBACK');
            error_log('Accounting Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Export Data Jurnal untuk Accounting Software (Jurnal.id / Excel)
     */
    public function exportLedger($startDate, $endDate) {
        $sql = "
            SELECT 
                e.transaction_date, 
                e.reference_no, 
                e.description, 
                e.account_code, 
                a.account_name,
                e.debit, 
                e.credit 
            FROM {$this->wpdb->prefix}umh_gl_entries e
            JOIN {$this->wpdb->prefix}umh_gl_accounts a ON e.account_code = a.account_code
            WHERE DATE(e.transaction_date) BETWEEN %s AND %s
            ORDER BY e.transaction_date ASC, e.id ASC
        ";
        
        return $this->wpdb->get_results($this->wpdb->prepare($sql, $startDate, $endDate), ARRAY_A);
    }
}