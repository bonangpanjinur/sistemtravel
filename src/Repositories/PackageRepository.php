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

    public function save($data) {
        global $wpdb;
        if (isset($data['id']) && !empty($data['id'])) {
            $id = $data['id'];
            unset($data['id']);
            return $wpdb->update($this->table, $data, ['id' => $id]);
        }
        return $wpdb->insert($this->table, $data);
    }

    public function delete($id) {
        global $wpdb;
        return $wpdb->update($this->table, ['deleted_at' => current_time('mysql')], ['id' => $id]);
    }
}
