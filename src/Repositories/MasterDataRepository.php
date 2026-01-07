<?php
namespace UmhMgmt\Repositories;

class MasterDataRepository {
    private $wpdb;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    public function getHotels() {
        return $this->wpdb->get_results("SELECT * FROM {$this->wpdb->prefix}umh_hotels ORDER BY name ASC");
    }

    public function getAirlines() {
        return $this->wpdb->get_results("SELECT * FROM {$this->wpdb->prefix}umh_airlines ORDER BY name ASC");
    }
}
