<?php
/**
 * File: src/Repositories/PackageRepository.php
 */
namespace UmhMgmt\Repositories;

class PackageRepository {
    private $table;
    private $wpdb;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table = $wpdb->prefix . 'umh_packages';
    }

    /**
     * Get all packages with related Hotel and Airline names (JOIN)
     */
    public function all() {
        $sql = "
            SELECT p.*, 
                   hm.name as hotel_mekkah_name, 
                   hma.name as hotel_madinah_name, 
                   a.name as airline_name
            FROM {$this->table} p
            LEFT JOIN {$this->wpdb->prefix}umh_master_hotels hm ON p.hotel_mekkah_id = hm.id
            LEFT JOIN {$this->wpdb->prefix}umh_master_hotels hma ON p.hotel_madinah_id = hma.id
            LEFT JOIN {$this->wpdb->prefix}umh_master_airlines a ON p.airline_id = a.id
            WHERE p.status != 'archived'
            ORDER BY p.created_at DESC
        ";
        return $this->wpdb->get_results($sql);
    }

    /**
     * Find single package by ID
     */
    public function find($id) {
        return $this->wpdb->get_row($this->wpdb->prepare("SELECT * FROM {$this->table} WHERE id = %d", $id));
    }

    /**
     * Get Pricing Tiers
     * Note: This assumes you handle pricing in umh_packages table columns 
     * (base_price_quad, base_price_triple, etc.) as per the full schema, 
     * or you can map them to an array if you prefer a unified interface.
     */
    public function getPricing($package_id) {
        // Since the full schema stores prices directly in the packages table,
        // we can extract them from the package object itself or query specifically.
        // If you still want to use a separate logic or table, adjust here.
        // For now, let's fetch from the package row for consistency with full schema.
        
        $package = $this->find($package_id);
        if (!$package) return [];

        return [
            'quad' => $package->base_price_quad,
            'triple' => $package->base_price_triple,
            'double' => $package->base_price_double
        ];
    }

    /**
     * Get Itinerary
     */
    public function getItinerary($package_id) {
        return $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT * FROM {$this->wpdb->prefix}umh_package_itineraries WHERE package_id = %d ORDER BY day_number ASC", 
            $package_id
        ));
    }

    /**
     * Get Facilities
     */
    public function getFacilities($package_id) {
        return $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT * FROM {$this->wpdb->prefix}umh_package_facilities WHERE package_id = %d", 
            $package_id
        ));
    }

    /**
     * Delete Package (Soft Delete / Archive)
     */
    public function delete($id) {
        return $this->wpdb->update($this->table, ['status' => 'archived'], ['id' => $id]);
    }
}