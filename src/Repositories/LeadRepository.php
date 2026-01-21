<?php
// Path: src/Repositories/LeadRepository.php

namespace App\Repositories;

use App\Interfaces\DatabaseInterface;

class LeadRepository {
    private $db;
    private $table;

    public function __construct(DatabaseInterface $db) {
        $this->db = $db;
        $this->table = $this->db->prefix() . 'umh_leads';
    }

    public function getAll($limit = 20, $offset = 0, $filters = []) {
        $sql = "SELECT * FROM {$this->table} WHERE 1=1";
        
        if (!empty($filters['status'])) {
            $sql .= $this->db->prepare(" AND status = %s", $filters['status']);
        }

        if (!empty($filters['search'])) {
            $term = '%' . $filters['search'] . '%';
            $sql .= $this->db->prepare(" AND (name LIKE %s OR phone LIKE %s)", $term, $term);
        }

        $sql .= " ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
        return $this->db->get_results($sql);
    }

    public function getById($id) {
        $sql = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = %d", $id);
        return $this->db->get_row($sql);
    }

    public function create($data) {
        $this->db->insert($this->table, $data);
        return $this->db->last_insert_id();
    }

    public function update($id, $data) {
        return $this->db->update($this->table, $data, ['id' => $id]);
    }

    public function delete($id) {
        return $this->db->delete($this->table, ['id' => $id]);
    }

    public function getStats() {
        return $this->db->get_results("SELECT status, COUNT(*) as count FROM {$this->table} GROUP BY status");
    }
}