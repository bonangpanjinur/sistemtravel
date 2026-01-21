<?php
// Path: src/Interfaces/DatabaseInterface.php

namespace UmrahManagement\Interfaces;

interface DatabaseInterface {
    /**
     * Mengambil banyak baris hasil query.
     */
    public function get_results(string $query, string $output = 'OBJECT');

    /**
     * Mengambil satu baris hasil query.
     */
    public function get_row(string $query, string $output = 'OBJECT', int $y = 0);

    /**
     * Mengambil satu variabel/nilai dari hasil query.
     */
    public function get_var(string $query, int $column_offset = 0, int $row_offset = 0);

    /**
     * Menjalankan query insert.
     */
    public function insert(string $table, array $data, array $format = null);

    /**
     * Menjalankan query update.
     */
    public function update(string $table, array $data, array $where, array $format = null, array $where_format = null);

    /**
     * Menjalankan query delete.
     */
    public function delete(string $table, array $where, array $where_format = null);

    /**
     * Menyiapkan query statement (mencegah SQL Injection).
     */
    public function prepare(string $query, ...$args);

    /**
     * Menjalankan query general (CREATE, ALTER, dll).
     */
    public function query(string $query);

    /**
     * Mengambil ID terakhir yang di-insert.
     */
    public function last_insert_id();

    /**
     * Mengambil prefix tabel database.
     */
    public function prefix(): string;
    
    /**
     * Memulai transaksi database.
     */
    public function begin_transaction();

    /**
     * Commit transaksi.
     */
    public function commit();

    /**
     * Rollback transaksi.
     */
    public function rollback();
}