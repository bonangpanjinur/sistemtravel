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

    public function saveHotel($data) {
        if (isset($data['id']) && !empty($data['id'])) {
            return $this->wpdb->update("{$this->wpdb->prefix}umh_hotels", $data, ['id' => $data['id']]);
        }
        return $this->wpdb->insert("{$this->wpdb->prefix}umh_hotels", $data);
    }

    public function deleteHotel($id) {
        return $this->wpdb->delete("{$this->wpdb->prefix}umh_hotels", ['id' => $id]);
    }

    public function saveAirline($data) {
        if (isset($data['id']) && !empty($data['id'])) {
            return $this->wpdb->update("{$this->wpdb->prefix}umh_airlines", $data, ['id' => $data['id']]);
        }
        return $this->wpdb->insert("{$this->wpdb->prefix}umh_airlines", $data);
    }

    public function deleteAirline($id) {
        return $this->wpdb->delete("{$this->wpdb->prefix}umh_airlines", ['id' => $id]);
    }
}
