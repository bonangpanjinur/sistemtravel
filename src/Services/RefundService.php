<?php
// File: src/Services/RefundService.php

namespace UmhMgmt\Services;

use Exception;

class RefundService {
    private $wpdb;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    /**
     * Menghitung estimasi refund dan penalti pembatalan
     */
    public function calculateRefundEstimation($bookingId) {
        // Ambil data booking dan tanggal keberangkatan
        $query = "
            SELECT b.id, b.total_price, b.status, d.departure_date 
            FROM {$this->wpdb->prefix}umh_bookings b
            JOIN {$this->wpdb->prefix}umh_departures d ON b.departure_id = d.id
            WHERE b.id = %d
        ";
        $booking = $this->wpdb->get_row($this->wpdb->prepare($query, $bookingId));

        if (!$booking) {
            throw new Exception("Booking tidak ditemukan.");
        }

        // Hitung selisih hari (H-Sekian)
        $departureDate = new \DateTime($booking->departure_date);
        $today = new \DateTime();
        $interval = $today->diff($departureDate);
        $daysUntilDeparture = (int)$interval->format('%r%a'); // %r untuk tanda negatif jika sudah lewat

        $penaltyPercent = 0;
        $penaltyAmount = 0;
        $notes = "";

        // Logika Penalti (Contoh standar travel)
        if ($daysUntilDeparture < 0) {
            // Sudah berangkat / lewat tanggal
            $penaltyPercent = 100;
            $notes = "Pembatalan setelah tanggal keberangkatan (No Show).";
        } elseif ($daysUntilDeparture <= 14) {
            // H-14
            $penaltyPercent = 100;
            $notes = "Pembatalan kurang dari 14 hari sebelum keberangkatan.";
        } elseif ($daysUntilDeparture <= 30) {
            // H-30 s/d H-15
            $penaltyPercent = 50;
            $notes = "Pembatalan antara 30-15 hari sebelum keberangkatan.";
        } elseif ($daysUntilDeparture <= 45) {
            // H-45 s/d H-31
            $penaltyPercent = 25;
            $notes = "Pembatalan antara 45-31 hari sebelum keberangkatan.";
        } else {
            // Lebih dari H-45 (Hanya biaya admin)
            $penaltyPercent = 10; 
            $notes = "Biaya administrasi pembatalan.";
        }

        $penaltyAmount = $booking->total_price * ($penaltyPercent / 100);
        $refundAmount = max(0, $booking->total_price - $penaltyAmount);

        return [
            'booking_id' => $bookingId,
            'total_paid' => $booking->total_price, // Asumsi lunas untuk basis perhitungan
            'days_until_departure' => $daysUntilDeparture,
            'penalty_percent' => $penaltyPercent,
            'penalty_amount' => $penaltyAmount,
            'estimated_refund' => $refundAmount,
            'reason_note' => $notes
        ];
    }

    public function createRefundRequest($bookingId, $reason, $userId) {
        $estimation = $this->calculateRefundEstimation($bookingId);

        $data = [
            'booking_id' => $bookingId,
            'reason' => sanitize_textarea_field($reason),
            'amount_requested' => $estimation['estimated_refund'],
            'cancellation_fee' => $estimation['penalty_amount'],
            'status' => 'requested',
            'requested_by' => $userId,
            'created_at' => current_time('mysql')
        ];

        $this->wpdb->insert($this->wpdb->prefix . 'umh_refunds', $data);
        
        // Update status booking agar tidak bisa diedit sembarangan
        $this->wpdb->update(
            $this->wpdb->prefix . 'umh_bookings',
            ['status' => 'refund_requested'],
            ['id' => $bookingId]
        );

        return $this->wpdb->insert_id;
    }
}