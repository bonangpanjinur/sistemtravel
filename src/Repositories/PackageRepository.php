<?php
namespace UmhMgmt\Repositories;

class PackageRepository {
    private $table;

    public function __construct() {
        global $wpdb;
        $this->table = $wpdb->prefix . 'umh_packages';
    }

    public function all() {
        global $wpdb;
        return $wpdb->get_results("SELECT * FROM {$this->table} WHERE deleted_at IS NULL");
    }

    public function find($id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table} WHERE id = %d AND deleted_at IS NULL", $id));
    }
}
