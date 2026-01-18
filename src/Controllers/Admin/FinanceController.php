<?php
// Folder: src/Controllers/Admin/
// File: FinanceController.php

namespace UmhMgmt\Controllers\Admin;

use UmhMgmt\Utils\View;
use UmhMgmt\Repositories\FinanceRepository;
use UmhMgmt\Services\AccountingService;
use UmhMgmt\Services\AgentService;

class FinanceController {
    private $financeRepo;
    private $accountingService;
    private $agentService;
    private $wpdb;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->financeRepo = new FinanceRepository();
        $this->accountingService = new AccountingService();
        $this->agentService = new AgentService();
    }

    public function index() {
        if (!current_user_can('umh_manage_payments') && !current_user_can('administrator')) {
            wp_die(__('Akses ditolak.', 'umh-mgmt'));
        }

        $payments = $this->financeRepo->getAllPayments();
        
        $total_verified = 0;
        $total_pending = 0;
        foreach ($payments as $p) {
            if ($p->status == 'verified') $total_verified += $p->amount;
            if ($p->status == 'pending_verification') $total_pending += $p->amount;
        }

        View::render('admin/finance', [
            'payments' => $payments,
            'summary' => [
                'verified' => $total_verified,
                'pending' => $total_pending
            ]
        ]);
    }

    public function handleVerifyPayment() {
        if (!isset($_POST['payment_id']) || !check_admin_referer('umh_verify_payment_nonce')) {
            wp_die('Security check failed');
        }

        $paymentId = intval($_POST['payment_id']);
        $action = sanitize_text_field($_POST['verification_action']); 
        $adminId = get_current_user_id();

        if ($action === 'verify') {
            $this->processVerification($paymentId, $adminId);
        } elseif ($action === 'reject') {
            $this->financeRepo->updateStatus($paymentId, 'rejected', $adminId);
        }

        wp_redirect(admin_url('admin.php?page=umh-finance&status=updated'));
        exit;
    }

    /**
     * CORE LOGIC: Verifikasi + Accounting + Marketing
     */
    private function processVerification($paymentId, $adminId) {
        // 1. Ambil Data Payment
        $payment = $this->financeRepo->getPaymentById($paymentId);
        if (!$payment || $payment->status === 'verified') return;

        // 2. Update Status
        $this->financeRepo->updateStatus($paymentId, 'verified', $adminId);

        // 3. ACCOUNTING: Catat Jurnal
        $debitAccount = '1002'; // Bank BCA
        $creditAccount = '2001'; // Deposit Jemaah
        $description = "Pembayaran Booking #{$payment->booking_id} via {$payment->payment_method}";
        
        $this->accountingService->recordTransaction(
            "INV-" . $payment->booking_id . "-" . $paymentId,
            $description,
            $debitAccount, 
            $creditAccount,
            $payment->amount,
            $adminId
        );

        // 4. MARKETING: Cek Komisi & Poin
        // Logika: Trigger komisi jika pembayaran ini membuat status booking jadi "Lunas" (Paid)
        // Atau sederhananya: Hitung sisa tagihan.
        
        $bookingId = $payment->booking_id;
        $booking = $this->wpdb->get_row($this->wpdb->prepare("SELECT * FROM {$this->wpdb->prefix}umh_bookings WHERE id = %d", $bookingId));
        
        // Hitung total bayar
        $totalPaid = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT SUM(amount) FROM {$this->wpdb->prefix}umh_payments WHERE booking_id = %d AND status = 'verified'", 
            $bookingId
        ));

        if ($booking && $booking->agent_id && $totalPaid >= $booking->total_price) {
            // Jika Lunas, Berikan Komisi & Poin
            
            // Hitung base commission (Misal 5% dari harga paket)
            // Idealnya ambil rule dari Master Data Paket, disini kita hardcode contoh
            $commissionAmount = $booking->total_price * 0.05; 

            // Distribute MLM Commission
            $this->agentService->distributeCommission($bookingId, $booking->agent_id, $commissionAmount);
            
            // Add Points (Misal 1 poin per 1 juta)
            $points = floor($booking->total_price / 1000000);
            $this->agentService->addPoints($booking->agent_id, $points, $bookingId);
        }
    }
}