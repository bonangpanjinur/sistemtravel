<?php
// File: RefundService.php
// Location: src/Services/RefundService.php

namespace UmhMgmt\Services;

use Exception;

class RefundService {
    private $wpdb;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    /**
     * Membuat Request Refund Baru
     */
    public function createRefundRequest($bookingId, $amount, $reason, $userId) {
        // 1. Validasi Booking
        $booking = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM {$this->wpdb->prefix}umh_bookings WHERE id = %d", 
            $bookingId
        ));

        if (!$booking) throw new Exception("Booking tidak ditemukan.");

        // 2. Hitung Estimasi Fee Pembatalan Otomatis
        // Logic: H-30 (25%), H-14 (50%), H-7 (75%), H-3 (100%)
        $fee = $this->calculateCancellationFee($booking->departure_id, $amount);

        // 3. Insert Request
        $this->wpdb->insert("{$this->wpdb->prefix}umh_refunds", [
            'booking_id' => $bookingId,
            'reason' => $reason,
            'amount_requested' => $amount,
            'cancellation_fee' => $fee,
            'amount_approved' => 0, // Belum disetujui
            'status' => 'requested',
            'requested_by' => $userId,
            'created_at' => current_time('mysql')
        ]);

        return $this->wpdb->insert_id;
    }

    /**
     * Helper: Hitung Penalty Fee berdasarkan tanggal berangkat
     */
    private function calculateCancellationFee($departureId, $amountRequested) {
        $departure = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT departure_date FROM {$this->wpdb->prefix}umh_departures WHERE id = %d",
            $departureId
        ));

        if (!$departure) return 0;

        $daysRemaining = (strtotime($departure->departure_date) - time()) / (60 * 60 * 24);

        if ($daysRemaining <= 3) return $amountRequested * 1.0; // 100% (Hangus)
        if ($daysRemaining <= 7) return $amountRequested * 0.75; // 75%
        if ($daysRemaining <= 14) return $amountRequested * 0.50; // 50%
        if ($daysRemaining <= 30) return $amountRequested * 0.25; // 25%
        
        return 0; // > 30 hari Full Refund (Opsional biaya admin)
    }

    /**
     * Approve Refund (Admin Finance Only)
     */
    public function approveRefund($refundId, $approvedAmount, $adminId) {
        $this->wpdb->query('START TRANSACTION');

        try {
            // Update Refund Status
            $updated = $this->wpdb->update("{$this->wpdb->prefix}umh_refunds", [
                'amount_approved' => $approvedAmount,
                'status' => 'approved',
                'approved_by' => $adminId,
                'updated_at' => current_time('mysql')
            ], ['id' => $refundId]);

            if ($updated === false) throw new Exception("Gagal update data refund.");

            // Update Status Booking jadi Cancelled/Refunded
            $refund = $this->wpdb->get_row("SELECT booking_id FROM {$this->wpdb->prefix}umh_refunds WHERE id = $refundId");
            $this->wpdb->update("{$this->wpdb->prefix}umh_bookings", 
                ['status' => 'refunded'], 
                ['id' => $refund->booking_id]
            );

            // TODO: Integrasi ke Accounting Service (Credit Bank, Debit Retur Penjualan)
            // AccountingService::recordRefundTransaction($refundId, $approvedAmount);

            $this->wpdb->query('COMMIT');
            return true;
        } catch (Exception $e) {
            $this->wpdb->query('ROLLBACK');
            throw $e;
        }
    }
}