<?php
// Folder: src/Repositories/
// File: MasterDataRepository.php

namespace UmhMgmt\Repositories;

class MasterDataRepository {
    private $wpdb;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    // --- HOTELS ---
    public function getHotels() {
        return $this->wpdb->get_results("SELECT * FROM {$this->wpdb->prefix}umh_hotels ORDER BY name ASC");
    }
    public function saveHotel($data) {
        if (!empty($data['id'])) return $this->wpdb->update("{$this->wpdb->prefix}umh_hotels", $data, ['id' => $data['id']]);
        return $this->wpdb->insert("{$this->wpdb->prefix}umh_hotels", $data);
    }
    public function deleteHotel($id) {
        return $this->wpdb->delete("{$this->wpdb->prefix}umh_hotels", ['id' => $id]);
    }

    // --- AIRLINES ---
    public function getAirlines() {
        return $this->wpdb->get_results("SELECT * FROM {$this->wpdb->prefix}umh_airlines ORDER BY name ASC");
    }
    public function saveAirline($data) {
        if (!empty($data['id'])) return $this->wpdb->update("{$this->wpdb->prefix}umh_airlines", $data, ['id' => $data['id']]);
        return $this->wpdb->insert("{$this->wpdb->prefix}umh_airlines", $data);
    }
    public function deleteAirline($id) {
        return $this->wpdb->delete("{$this->wpdb->prefix}umh_airlines", ['id' => $id]);
    }

    // --- [NEW] MUTHAWIFS ---
    public function getMuthawifs() {
        return $this->wpdb->get_results("SELECT * FROM {$this->wpdb->prefix}umh_muthawifs ORDER BY name ASC");
    }
    public function saveMuthawif($data) {
        if (!empty($data['id'])) return $this->wpdb->update("{$this->wpdb->prefix}umh_muthawifs", $data, ['id' => $data['id']]);
        return $this->wpdb->insert("{$this->wpdb->prefix}umh_muthawifs", $data);
    }
    public function deleteMuthawif($id) {
        return $this->wpdb->delete("{$this->wpdb->prefix}umh_muthawifs", ['id' => $id]);
    }

    // --- [NEW] BUS PROVIDERS ---
    public function getBusProviders() {
        return $this->wpdb->get_results("SELECT * FROM {$this->wpdb->prefix}umh_bus_providers ORDER BY company_name ASC");
    }
    public function saveBusProvider($data) {
        if (!empty($data['id'])) return $this->wpdb->update("{$this->wpdb->prefix}umh_bus_providers", $data, ['id' => $data['id']]);
        return $this->wpdb->insert("{$this->wpdb->prefix}umh_bus_providers", $data);
    }
    public function deleteBusProvider($id) {
        return $this->wpdb->delete("{$this->wpdb->prefix}umh_bus_providers", ['id' => $id]);
    }

    // --- [NEW] AIRPORTS ---
    public function getAirports() {
        return $this->wpdb->get_results("SELECT * FROM {$this->wpdb->prefix}umh_airports ORDER BY iata_code ASC");
    }
    public function saveAirport($data) {
        if (!empty($data['id'])) return $this->wpdb->update("{$this->wpdb->prefix}umh_airports", $data, ['id' => $data['id']]);
        return $this->wpdb->insert("{$this->wpdb->prefix}umh_airports", $data);
    }
    public function deleteAirport($id) {
        return $this->wpdb->delete("{$this->wpdb->prefix}umh_airports", ['id' => $id]);
    }
}