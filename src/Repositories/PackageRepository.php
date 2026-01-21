<?php
// Path: src/Repositories/PackageRepository.php

namespace UmrahManagement\Repositories;

use UmrahManagement\Interfaces\DatabaseInterface;

class PackageRepository {
    private $db;
    private $table;
    private $table_pricing;
    private $table_hotels;
    private $table_airlines;
    private $table_departures;

    // Dependency Injection: DatabaseInterface di-inject via constructor
    public function __construct(DatabaseInterface $db) {
        $this->db = $db;
        
        // Mengambil prefix DB (biasanya wp_) dan menggabungkan dengan nama tabel 'umh_'
        $prefix = $this->db->prefix();
        $this->table = $prefix . 'umh_packages';
        $this->table_pricing = $prefix . 'umh_package_pricing';
        $this->table_hotels = $prefix . 'umh_hotels';
        $this->table_airlines = $prefix . 'umh_airlines';
        $this->table_departures = $prefix . 'umh_departures';
    }

    /**
     * Mengambil semua paket aktif (belum dihapus)
     */
    public function getAll($limit = 10, $offset = 0, $search = '') {
        $query = "SELECT * FROM {$this->table} WHERE deleted_at IS NULL";
        
        if (!empty($search)) {
            $search_term = '%' . $search . '%';
            $query .= $this->db->prepare(" AND (name LIKE %s OR description LIKE %s)", $search_term, $search_term);
        }

        $query .= " ORDER BY created_at DESC LIMIT $limit OFFSET $offset";

        return $this->db->get_results($query);
    }

    /**
     * Mengambil detail paket beserta data hotel, maskapai, dan harga
     */
    public function getById($id) {
        $sql = "SELECT p.*, 
                       h1.name as hotel_mekkah, h2.name as hotel_madinah, 
                       a.name as airline_name
                FROM {$this->table} p
                LEFT JOIN {$this->table_hotels} h1 ON p.hotel_mekkah_id = h1.id
                LEFT JOIN {$this->table_hotels} h2 ON p.hotel_madinah_id = h2.id
                LEFT JOIN {$this->table_airlines} a ON p.airline_id = a.id
                WHERE p.id = %d AND p.deleted_at IS NULL";
        
        $query = $this->db->prepare($sql, $id);
        $package = $this->db->get_row($query);

        if (!$package) return null;

        // Ambil daftar harga per tipe kamar
        $package->pricing = $this->getPricing($id);

        return $package;
    }

    /**
     * Mengambil map harga paket & logika default untuk infant/child
     */
    public function getPricing($packageId) {
        $query = $this->db->prepare(
            "SELECT room_type, price FROM {$this->table_pricing} WHERE package_id = %d",
            $packageId
        );
        
        $results = $this->db->get_results($query);
        $prices = [];
        
        foreach ($results as $row) {
            $prices[$row->room_type] = (float)$row->price;
        }

        // Tambahkan default logic untuk Infant & Child No Bed jika tidak ada di database
        if (isset($prices['quad'])) {
            if (!isset($prices['infant'])) {
                $prices['infant'] = $prices['quad'] * 0.20; 
            }
            if (!isset($prices['child_no_bed'])) {
                $prices['child_no_bed'] = $prices['quad'] * 0.85;
            }
        }

        return $prices;
    }

    /**
     * Menyimpan paket baru
     */
    public function create($data) {
        if (!isset($data['created_at'])) {
            $data['created_at'] = date('Y-m-d H:i:s');
        }
        $this->db->insert($this->table, $data);
        return $this->db->last_insert_id();
    }

    /**
     * Update data paket
     */
    public function update($id, $data) {
        return $this->db->update(
            $this->table, 
            $data, 
            ['id' => $id]
        );
    }

    /**
     * Soft delete paket
     */
    public function delete($id) {
        return $this->db->update(
            $this->table,
            ['deleted_at' => date('Y-m-d H:i:s')],
            ['id' => $id],
            ['%s'], // Format value
            ['%d']  // Format where
        );
    }

    public function countTotal($search = '') {
        $query = "SELECT COUNT(*) FROM {$this->table} WHERE deleted_at IS NULL";
        
        if (!empty($search)) {
            $search_term = '%' . $search . '%';
            $query .= $this->db->prepare(" AND name LIKE %s", $search_term);
        }

        return $this->db->get_var($query);
    }
    
    /**
     * Mengurangi kuota seat keberangkatan (Concurrency Safe)
     */
    public function decreaseQuota($departureId, $qty) {
        $sql = $this->db->prepare(
            "UPDATE {$this->table_departures} 
             SET available_seats = available_seats - %d 
             WHERE id = %d AND available_seats >= %d",
            $qty, $departureId, $qty
        );
        
        return $this->db->query($sql);
    }
}