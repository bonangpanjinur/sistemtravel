<?php
namespace UmhMgmt\Repositories;

class CRMRepository {
    private $wpdb;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    public function getLeads() {
        return $this->wpdb->get_results("SELECT * FROM {$this->wpdb->prefix}umh_leads ORDER BY created_at DESC");
    }

    public function updateLeadStatus($id, $status) {
        return $this->wpdb->update(
            "{$this->wpdb->prefix}umh_leads",
            ['status' => $status],
            ['id' => $id]
        );
    }
}
