<?php

namespace App\Core;

/**
 * Class DB
 * * Wrapper sederhana untuk WordPress database ($wpdb).
 * Berfungsi sebagai Query Builder untuk menghindari penulisan raw SQL manual
 * dan memastikan semua query melewati proses prepare (sanitasi) otomatis.
 * * @package App\Core
 */
class DB {
    private $table;
    private $where = [];
    private $params = [];
    private $limit = null;
    private $offset = null;
    private $orderBy = null;

    /**
     * Memulai query untuk tabel tertentu.
     * Otomatis menambahkan prefix database WordPress.
     *
     * @param string $table Nama tabel (tanpa prefix WordPress). Contoh: 'umh_bookings'
     * @return self
     */
    public static function table($table) {
        global $wpdb;
        $instance = new self();
        // Cek jika tabel sudah memiliki prefix wp_, jika belum tambahkan
        if (strpos($table, $wpdb->prefix) === 0) {
            $instance->table = $table;
        } else {
            $instance->table = $wpdb->prefix . $table;
        }
        return $instance;
    }

    /**
     * Menambahkan kondisi WHERE.
     * Mendukung otomatis deteksi tipe data untuk prepare statement (%s, %d, %f).
     *
     * @param string $column Nama kolom
     * @param string $operator Operator (=, >, <, LIKE, etc) atau Value jika operator dihilangkan
     * @param mixed $value Nilai yang dicari
     * @return self
     */
    public function where($column, $operator, $value = null) {
        // Jika hanya 2 parameter, asumsikan operatornya '='
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }
        
        // Deteksi tipe data untuk placeholder yang aman
        $placeholder = '%s';
        if (is_int($value)) {
            $placeholder = '%d';
        } elseif (is_float($value)) {
            $placeholder = '%f';
        }
        
        $this->where[] = "$column $operator $placeholder";
        $this->params[] = $value;
        return $this;
    }

    /**
     * Mengatur urutan data (ORDER BY).
     *
     * @param string $column Nama kolom
     * @param string $direction ASC atau DESC
     * @return self
     */
    public function orderBy($column, $direction = 'ASC') {
        $this->orderBy = "$column $direction";
        return $this;
    }

    /**
     * Membatasi jumlah hasil (LIMIT & OFFSET).
     *
     * @param int $limit Jumlah data
     * @param int $offset Mulai dari data ke berapa
     * @return self
     */
    public function limit($limit, $offset = 0) {
        $this->limit = $limit;
        $this->offset = $offset;
        return $this;
    }

    /**
     * Eksekusi query dan ambil semua hasil (get_results).
     *
     * @return array Array of objects
     */
    public function get() {
        global $wpdb;
        $sql = "SELECT * FROM {$this->table}";

        if (!empty($this->where)) {
            $sql .= " WHERE " . implode(' AND ', $this->where);
        }

        if ($this->orderBy) {
            $sql .= " ORDER BY {$this->orderBy}";
        }

        if ($this->limit) {
            $sql .= " LIMIT {$this->limit}";
            if ($this->offset) {
                $sql .= " OFFSET {$this->offset}";
            }
        }

        // Lakukan prepare statement otomatis jika ada parameter
        if (!empty($this->params)) {
            $sql = $wpdb->prepare($sql, $this->params);
        }

        return $wpdb->get_results($sql);
    }

    /**
     * Eksekusi query dan ambil satu baris data (get_row).
     *
     * @return object|null
     */
    public function first() {
        $this->limit(1);
        $results = $this->get();
        return !empty($results) ? $results[0] : null;
    }
}