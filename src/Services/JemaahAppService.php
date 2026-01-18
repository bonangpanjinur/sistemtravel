<?php
// Folder: src/Services/
// File: JemaahAppService.php

namespace UmhMgmt\Services;

class JemaahAppService {
    private $wpdb;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    /**
     * Get Progress Timeline Lengkap
     * Menggabungkan data Booking, Pembayaran, dan Dokumen
     */
    public function getProgressTimeline($bookingId) {
        $booking = $this->wpdb->get_row($this->wpdb->prepare("SELECT * FROM {$this->wpdb->prefix}umh_bookings WHERE id = %d", $bookingId));
        
        if (!$booking) return [];

        // Definisi Step Standar
        $steps = [
            'registration' => [
                'title' => 'Pendaftaran',
                'desc' => 'Booking paket perjalanan',
                'status' => 'completed', // Selalu completed jika booking ada
                'date' => $booking->created_at
            ],
            'dp_payment' => [
                'title' => 'Pembayaran DP',
                'desc' => 'Pembayaran uang muka',
                'status' => 'pending',
                'date' => null
            ],
            'documents' => [
                'title' => 'Dokumen & Paspor',
                'desc' => 'Upload Paspor dan KTP',
                'status' => 'pending',
                'date' => null
            ],
            'full_payment' => [
                'title' => 'Pelunasan',
                'desc' => 'Pelunasan sisa tagihan',
                'status' => 'pending',
                'date' => null
            ],
            'visa_processing' => [
                'title' => 'Proses Visa',
                'desc' => 'Visa sedang diajukan ke provider',
                'status' => 'pending', // pending, processing, completed
                'date' => null
            ],
            'departure_readiness' => [
                'title' => 'Siap Berangkat',
                'desc' => 'Perlengkapan, Manasik & Tiket',
                'status' => 'pending',
                'date' => null
            ]
        ];

        // 1. Cek Pembayaran
        $totalPaid = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT SUM(amount) FROM {$this->wpdb->prefix}umh_payments WHERE booking_id = %d AND status = 'verified'", 
            $bookingId
        ));

        if ($totalPaid > 0) {
            $steps['dp_payment']['status'] = 'completed';
        }

        if ($totalPaid >= $booking->total_price) {
            $steps['full_payment']['status'] = 'completed';
        }

        // 2. Cek Dokumen (Paspor)
        $passengers = $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT id, doc_verification_status FROM {$this->wpdb->prefix}umh_booking_passengers WHERE booking_id = %d", 
            $bookingId
        ));

        $allDocsVerified = true;
        foreach ($passengers as $pax) {
            if ($pax->doc_verification_status !== 'verified') {
                $allDocsVerified = false;
                break;
            }
        }
        
        if (!empty($passengers) && $allDocsVerified) {
            $steps['documents']['status'] = 'completed';
        } elseif (!empty($passengers)) {
            $steps['documents']['status'] = 'processing';
        }

        // 3. Cek Override Status dari Tabel Progress Khusus (jika admin update manual)
        $customProgress = $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT step_key, status, completed_at FROM {$this->wpdb->prefix}umh_jemaah_progress WHERE booking_id = %d",
            $bookingId
        ));

        foreach ($customProgress as $row) {
            if (isset($steps[$row->step_key])) {
                $steps[$row->step_key]['status'] = $row->status;
                $steps[$row->step_key]['date'] = $row->completed_at;
            }
        }

        return array_values($steps); // Return as indexed array for JSON
    }

    /**
     * Ambil Konten Panduan (Doa/Manasik)
     */
    public function getDigitalGuides($category = 'all') {
        $sql = "SELECT id, title, content, category, media_url FROM {$this->wpdb->prefix}umh_digital_guides WHERE is_active = 1";
        
        if ($category !== 'all') {
            $sql .= $this->wpdb->prepare(" AND category = %s", $category);
        }
        
        return $this->wpdb->get_results($sql);
    }
}