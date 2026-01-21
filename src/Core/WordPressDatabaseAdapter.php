<?php
// Path: src/Core/WordPressDatabaseAdapter.php

namespace App\Core;

use App\Interfaces\DatabaseInterface;

class WordPressDatabaseAdapter implements DatabaseInterface {
    private $wpdb;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    public function get_results(string $query, string $output = 'OBJECT') {
        return $this->wpdb->get_results($query, $output);
    }

    public function get_row(string $query, string $output = 'OBJECT', int $y = 0) {
        return $this->wpdb->get_row($query, $output, $y);
    }

    public function get_var(string $query, int $column_offset = 0, int $row_offset = 0) {
        return $this->wpdb->get_var($query, $column_offset, $row_offset);
    }

    public function insert(string $table, array $data, array $format = null) {
        return $this->wpdb->insert($table, $data, $format);
    }

    public function update(string $table, array $data, array $where, array $format = null, array $where_format = null) {
        return $this->wpdb->update($table, $data, $where, $format, $where_format);
    }

    public function delete(string $table, array $where, array $where_format = null) {
        return $this->wpdb->delete($table, $where, $where_format);
    }

    public function prepare(string $query, ...$args) {
        return $this->wpdb->prepare($query, ...$args);
    }

    public function query(string $query) {
        return $this->wpdb->query($query);
    }

    public function last_insert_id() {
        return $this->wpdb->insert_id;
    }

    public function prefix(): string {
        return $this->wpdb->prefix;
    }

    public function begin_transaction() {
        $this->wpdb->query('START TRANSACTION');
    }

    public function commit() {
        $this->wpdb->query('COMMIT');
    }

    public function rollback() {
        $this->wpdb->query('ROLLBACK');
    }
}