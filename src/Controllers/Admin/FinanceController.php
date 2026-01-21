<?php

namespace App\Controllers\Admin;

use App\Utils\View;
use App\Repositories\FinanceRepository;

class FinanceController
{
    private $repository;

    public function __construct(FinanceRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Halaman Utama Dashboard Keuangan
     */
    public function index()
    {
        // Menangani submission form transaksi jika ada POST request di halaman index
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_transaction') {
            $this->store();
            return; // Stop execution after redirect in store()
        }

        // Ambil data statistik dan ringkasan
        $summary = $this->repository->getSummary();
        $recent_transactions = $this->repository->getRecentTransactions(10);
        $pending_payments = $this->repository->getPendingPayments();
        
        // Data cashflow untuk grafik (jika template mendukung)
        $cash_flow = $this->repository->getCashFlowStats();

        View::render('admin/finance', [
            'title' => 'Keuangan & Akuntansi',
            'summary' => $summary,
            'transactions' => $recent_transactions,
            'pending_payments' => $pending_payments,
            'cash_flow' => $cash_flow
        ]);
    }

    /**
     * Halaman Daftar Semua Transaksi (History)
     */
    public function transactions()
    {
        $page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $filters = [
            'type' => isset($_GET['type']) ? sanitize_text_field($_GET['type']) : '',
            'start_date' => isset($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : '',
            'end_date' => isset($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : '',
        ];

        // Asumsi method ini ada di repository, jika belum ada perlu ditambahkan
        // Jika repository belum support, ini akan perlu penyesuaian
        if (method_exists($this->repository, 'getAllTransactions')) {
            $transactions = $this->repository->getAllTransactions($filters, $limit, $offset);
            $total_items = $this->repository->countTransactions($filters);
        } else {
            // Fallback ke recent transactions jika method spesifik belum ada
            $transactions = $this->repository->getRecentTransactions($limit);
            $total_items = count($transactions); // Placeholder
        }
        
        View::render('admin/finance/transactions', [ // Pastikan file template ini dibuat/ada
            'title' => 'Riwayat Transaksi Lengkap',
            'transactions' => $transactions,
            'filters' => $filters,
            'current_page' => $page,
            'total_pages' => ceil($total_items / $limit)
        ]);
    }

    /**
     * Menyimpan Transaksi Baru (Pemasukan/Pengeluaran Manual)
     */
    public function store()
    {
        // Cek permission dan nonce
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'save_finance_transaction')) {
            // Jika nonce gagal, jangan die, tapi kembalikan error
             add_settings_error('finance_messages', 'nonce_error', 'Security check failed. Please try again.', 'error');
             return;
        }

        $type = sanitize_text_field($_POST['type']); // income / expense
        $amount = floatval(str_replace(['.', ','], '', $_POST['amount'])); // Remove formatting
        $date = sanitize_text_field($_POST['transaction_date']);
        $description = sanitize_textarea_field($_POST['description']);
        $category = sanitize_text_field($_POST['category']);

        if ($amount <= 0 || empty($date)) {
            add_settings_error('finance_messages', 'invalid_input', 'Jumlah dan tanggal harus diisi dengan benar.', 'error');
            return;
        }

        $data = [
            'type' => $type,
            'amount' => $amount,
            'transaction_date' => $date,
            'description' => $description,
            'category' => $category,
            'created_by' => get_current_user_id(),
            'created_at' => current_time('mysql')
        ];

        // Panggil method create di repository
        // Pastikan FinanceRepository memiliki method 'createTransaction' atau 'insert'
        if (method_exists($this->repository, 'createTransaction')) {
            $result = $this->repository->createTransaction($data);
        } else {
            // Fallback manual insert via DB interface jika repository terbatas
            // Ini contoh pattern jika repository belum lengkap
            // $this->db->insert(...) 
            $result = false; // Placeholder error
        }

        if ($result) {
            add_settings_error('finance_messages', 'success', 'Transaksi berhasil disimpan.', 'success');
        } else {
            add_settings_error('finance_messages', 'error', 'Gagal menyimpan transaksi.', 'error');
        }

        $this->redirect('admin.php?page=travel-umroh-finance');
    }

    /**
     * Verifikasi Pembayaran Booking
     */
    public function verifyPayment()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $payment_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        
        if ($payment_id > 0 && method_exists($this->repository, 'verifyPayment')) {
            $this->repository->verifyPayment($payment_id, get_current_user_id());
            add_settings_error('finance_messages', 'verified', 'Pembayaran telah diverifikasi.', 'success');
        }

        $this->redirect('admin.php?page=travel-umroh-finance');
    }

    /**
     * Hapus Transaksi
     */
    public function delete()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $nonce = isset($_GET['_wpnonce']) ? $_GET['_wpnonce'] : '';

        if (wp_verify_nonce($nonce, 'delete_transaction_' . $id)) {
            if (method_exists($this->repository, 'deleteTransaction')) {
                $this->repository->deleteTransaction($id);
                add_settings_error('finance_messages', 'deleted', 'Transaksi dihapus.', 'success');
            }
        }

        $this->redirect('admin.php?page=travel-umroh-finance');
    }

    /**
     * Helper Redirect
     */
    private function redirect($url)
    {
        if (!headers_sent()) {
            wp_redirect($url);
            exit;
        } else {
            echo "<script>window.location.href='$url';</script>";
            exit;
        }
    }
}