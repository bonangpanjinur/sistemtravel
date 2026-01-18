<?php
// Folder: src/Services/
// File: OperationalService.php

namespace UmhMgmt\Services;

class OperationalService {
    private $wpdb;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    /**
     * Generate & Simpan Kode Bagasi Unik
     */
    public function generateLuggageTag($passengerId, $departureId) {
        // Format: UMH-[DEP_ID]-[PAX_ID]-[RAND]
        // Contoh: UMH-102-55-AF3D
        $randomStr = strtoupper(substr(md5(uniqid()), 0, 4));
        $tagCode = sprintf("UMH-%d-%d-%s", $departureId, $passengerId, $randomStr);
        
        // Cek jika sudah ada
        $existing = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT tag_code FROM {$this->wpdb->prefix}umh_luggage WHERE passenger_id = %d", 
            $passengerId
        ));

        if ($existing) return $existing;

        $this->wpdb->insert(
            $this->wpdb->prefix . 'umh_luggage',
            [
                'passenger_id' => $passengerId,
                'tag_code' => $tagCode,
                'status' => 'printed'
            ]
        );
        
        return $tagCode;
    }

    /**
     * Scan QR Absensi (Bandara/Bus/Hotel)
     */
    public function recordAttendance($qrData, $checkpoint, $scannerUserId) {
        // Asumsi QR Data formatnya: "UMH:PAX_ID" atau Tag Code Bagasi
        // Kita parsing sederhana
        $passengerId = 0;

        if (strpos($qrData, 'UMH-') === 0) {
            // Ini scan koper, ambil pax id dari tag code
            $luggage = $this->wpdb->get_row($this->wpdb->prepare("SELECT passenger_id FROM {$this->wpdb->prefix}umh_luggage WHERE tag_code = %s", $qrData));
            if ($luggage) $passengerId = $luggage->passenger_id;
        } else {
            // Asumsi QR ID Card: "UMH:123:Jemaah"
            $parts = explode(':', $qrData);
            if (isset($parts[1])) $passengerId = intval($parts[1]);
        }

        if (!$passengerId) {
            return ['success' => false, 'message' => 'QR Code tidak dikenali'];
        }

        // Ambil info keberangkatan aktif
        $departure = $this->getActiveDepartureInfo($passengerId);
        if (!$departure) {
            return ['success' => false, 'message' => 'Jamaah tidak dalam jadwal keberangkatan aktif'];
        }

        // Catat Absensi
        $this->wpdb->insert(
            $this->wpdb->prefix . 'umh_attendance',
            [
                'passenger_id' => $passengerId,
                'departure_id' => $departure->id,
                'checkpoint_name' => $checkpoint,
                'scanned_by' => $scannerUserId,
                'scanned_at' => current_time('mysql')
            ]
        );

        return [
            'success' => true, 
            'message' => 'Absensi Berhasil',
            'pax_name' => $departure->pax_name
        ];
    }

    private function getActiveDepartureInfo($paxId) {
        return $this->wpdb->get_row($this->wpdb->prepare("
            SELECT d.id, pax.name as pax_name
            FROM {$this->wpdb->prefix}umh_booking_passengers pax
            JOIN {$this->wpdb->prefix}umh_bookings b ON pax.booking_id = b.id
            JOIN {$this->wpdb->prefix}umh_departures d ON b.departure_id = d.id
            WHERE pax.id = %d AND d.status IN ('open', 'departed')
        ", $paxId));
    }
}