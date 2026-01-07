<?php
namespace UmhMgmt\Repositories;

class OperationalRepository {
    private $wpdb;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    public function getUpcomingDepartures() {
        return $this->wpdb->get_results("
            SELECT d.*, p.name as package_name 
            FROM {$this->wpdb->prefix}umh_departures d
            JOIN {$this->wpdb->prefix}umh_packages p ON d.package_id = p.id
            WHERE d.status IN ('open', 'departed')
            ORDER BY d.departure_date ASC
        ");
    }

    public function getInventoryItems() {
        return $this->wpdb->get_results("SELECT * FROM {$this->wpdb->prefix}umh_inventory_items ORDER BY item_name ASC");
    }
}
