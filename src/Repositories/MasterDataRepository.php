<?php
// Path: src/Repositories/MasterDataRepository.php

namespace App\Repositories;

use App\Interfaces\DatabaseInterface;

class MasterDataRepository {
    private $db;
    private $table_hotels;
    private $table_airlines;
    private $table_muthawifs;
    private $table_bus_providers;
    private $table_airports;

    public function __construct(DatabaseInterface $db) {
        $this->db = $db;
        $prefix = $this->db->prefix();

        // Definisi nama tabel dengan prefix standar 'umh_'
        $this->table_hotels = $prefix . 'umh_hotels';
        $this->table_airlines = $prefix . 'umh_airlines';
        $this->table_muthawifs = $prefix . 'umh_muthawifs';
        $this->table_bus_providers = $prefix . 'umh_bus_providers';
        $this->table_airports = $prefix . 'umh_airports';
    }

    // --- HOTELS ---
    public function getHotels($city = null) {
        $sql = "SELECT * FROM {$this->table_hotels}";
        if ($city) {
            $sql .= $this->db->prepare(" WHERE city = %s", $city);
        }
        $sql .= " ORDER BY name ASC";
        return $this->db->get_results($sql);
    }

    public function saveHotel($data) {
        if (!empty($data['id'])) {
            return $this->db->update($this->table_hotels, $data, ['id' => $data['id']]);
        }
        return $this->db->insert($this->table_hotels, $data);
    }

    public function deleteHotel($id) {
        return $this->db->delete($this->table_hotels, ['id' => $id]);
    }

    // --- AIRLINES ---
    public function getAirlines() {
        return $this->db->get_results("SELECT * FROM {$this->table_airlines} ORDER BY name ASC");
    }

    public function saveAirline($data) {
        if (!empty($data['id'])) {
            return $this->db->update($this->table_airlines, $data, ['id' => $data['id']]);
        }
        return $this->db->insert($this->table_airlines, $data);
    }

    public function deleteAirline($id) {
        return $this->db->delete($this->table_airlines, ['id' => $id]);
    }

    // --- MUTHAWIFS (Pembimbing Ibadah) ---
    public function getMuthawifs() {
        return $this->db->get_results("SELECT * FROM {$this->table_muthawifs} ORDER BY name ASC");
    }

    public function saveMuthawif($data) {
        if (!empty($data['id'])) {
            return $this->db->update($this->table_muthawifs, $data, ['id' => $data['id']]);
        }
        return $this->db->insert($this->table_muthawifs, $data);
    }

    public function deleteMuthawif($id) {
        return $this->db->delete($this->table_muthawifs, ['id' => $id]);
    }

    // --- BUS PROVIDERS ---
    public function getBusProviders() {
        return $this->db->get_results("SELECT * FROM {$this->table_bus_providers} ORDER BY company_name ASC");
    }

    public function saveBusProvider($data) {
        if (!empty($data['id'])) {
            return $this->db->update($this->table_bus_providers, $data, ['id' => $data['id']]);
        }
        return $this->db->insert($this->table_bus_providers, $data);
    }

    public function deleteBusProvider($id) {
        return $this->db->delete($this->table_bus_providers, ['id' => $id]);
    }

    // --- AIRPORTS ---
    public function getAirports() {
        return $this->db->get_results("SELECT * FROM {$this->table_airports} ORDER BY iata_code ASC");
    }

    public function saveAirport($data) {
        if (!empty($data['id'])) {
            return $this->db->update($this->table_airports, $data, ['id' => $data['id']]);
        }
        return $this->db->insert($this->table_airports, $data);
    }

    public function deleteAirport($id) {
        return $this->db->delete($this->table_airports, ['id' => $id]);
    }
}