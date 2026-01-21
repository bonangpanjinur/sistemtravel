<?php
// Path: src/Repositories/OperationalRepository.php

namespace UmrahManagement\Repositories;

use UmrahManagement\Interfaces\DatabaseInterface;

class OperationalRepository {
    private $db;
    private $table_manifest;
    private $table_departures;
    private $table_packages;
    private $table_inventory_items;
    private $table_inventory_log;

    public function __construct(DatabaseInterface $db) {
        $this->db = $db;
        $prefix = $this->db->prefix();

        // Menggunakan prefix 'umh_' sesuai kode legacy
        $this->table_manifest = $prefix . 'umh_manifest';
        $this->table_departures = $prefix . 'umh_departures';
        $this->table_packages = $prefix . 'umh_packages';
        $this->table_inventory_items = $prefix . 'umh_inventory_items';
        $this->table_inventory_log = $prefix . 'umh_inventory_log';
    }

    /**
     * --- MANIFEST & VISA SECTION ---
     */

    public function getManifestByDeparture($departureId) {
        $sql = $this->db->prepare("SELECT * FROM {$this->table_manifest} WHERE departure_id = %d ORDER BY seat_number ASC", $departureId);
        return $this->db->get_results($sql);
    }

    public function updateVisaStatus($manifestId, $status, $visaNumber = null) {
        $data = ['visa_status' => $status];
        if ($visaNumber) {
            $data['visa_number'] = $visaNumber;
        }
        
        return $this->db->update(
            $this->table_manifest,
            $data,
            ['id' => $manifestId]
        );
    }

    public function assignRoom($manifestId, $roomNumber, $hotelName) {
        return $this->db->update(
            $this->table_manifest,
            [
                'room_number' => $roomNumber,
                'hotel_name' => $hotelName
            ],
            ['id' => $manifestId]
        );
    }
    
    /**
     * --- DEPARTURES SECTION (Restored from Legacy) ---
     */

    public function getUpcomingDepartures($limit = 5) {
        $sql = $this->db->prepare("
            SELECT d.*, p.name as package_name 
            FROM {$this->table_departures} d
            JOIN {$this->table_packages} p ON d.package_id = p.id
            WHERE d.status IN ('open', 'departed')
            ORDER BY d.departure_date ASC
            LIMIT %d
        ", $limit);

        return $this->db->get_results($sql);
    }

    /**
     * --- INVENTORY SECTION (Restored from Legacy & New Logic) ---
     */

    public function getInventoryItems() {
        // Dari kode legacy
        return $this->db->get_results("SELECT * FROM {$this->table_inventory_items} ORDER BY item_name ASC");
    }

    public function getInventoryLog($departureId) {
        // Fitur baru yang sebelumnya sudah ada di draft saya
        $sql = $this->db->prepare("SELECT * FROM {$this->table_inventory_log} WHERE departure_id = %d", $departureId);
        return $this->db->get_results($sql);
    }
}