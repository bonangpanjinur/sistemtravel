<?php
// File: src/Repositories/PackageRepository.php

namespace UmhMgmt\Repositories;

class PackageRepository {
    private $wpdb;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    /**
     * Mengambil detail paket beserta opsi harga
     */
    public function getPackageDetail($id) {
        // Ambil data dasar paket
        $package = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT p.*, 
                    h1.name as hotel_mekkah, h2.name as hotel_madinah, 
                    a.name as airline_name
             FROM {$this->wpdb->prefix}umh_packages p
             LEFT JOIN {$this->wpdb->prefix}umh_hotels h1 ON p.hotel_mekkah_id = h1.id
             LEFT JOIN {$this->wpdb->prefix}umh_hotels h2 ON p.hotel_madinah_id = h2.id
             LEFT JOIN {$this->wpdb->prefix}umh_airlines a ON p.airline_id = a.id
             WHERE p.id = %d AND p.deleted_at IS NULL",
            $id
        ));

        if (!$package) return null;

        // Ambil daftar harga per tipe kamar (Quad, Triple, Double)
        $pricing = $this->getPricing($id);
        $package->pricing = $pricing;

        return $package;
    }

    /**
     * [BARU] Mengambil map harga paket untuk kalkulasi booking
     * Returns: ['quad' => 25jt, 'triple' => 27jt, 'double' => 30jt]
     */
    public function getPricing($packageId) {
        $results = $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT room_type, price FROM {$this->wpdb->prefix}umh_package_pricing WHERE package_id = %d",
            $packageId
        ));

        $prices = [];
        foreach ($results as $row) {
            $prices[$row->room_type] = (float)$row->price;
        }

        // Tambahkan default logic untuk Infant & Child No Bed jika tidak ada di database
        // Biasanya Infant = 20% dari Quad, Child No Bed = 85% dari Quad
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

    public function decreaseQuota($departureId, $qty) {
        return $this->wpdb->query($this->wpdb->prepare(
            "UPDATE {$this->wpdb->prefix}umh_departures 
             SET available_seats = available_seats - %d 
             WHERE id = %d AND available_seats >= %d",
            $qty, $departureId, $qty
        ));
    }
}