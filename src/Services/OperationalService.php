<?php
// File: src/Services/OperationalService.php

namespace UmhMgmt\Services;

use Exception;

class OperationalService {
    private $wpdb;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    /**
     * Mendistribusikan perlengkapan ke jemaah (Scan Barcode)
     */
    public function distributeItem($passengerId, $itemCode, $staffId) {
        $this->wpdb->query('START TRANSACTION');

        try {
            // 1. Cari Item berdasarkan Code/Barcode
            $item = $this->wpdb->get_row($this->wpdb->prepare(
                "SELECT id, item_name, stock_qty FROM {$this->wpdb->prefix}umh_inventory_items WHERE item_code = %s FOR UPDATE",
                $itemCode
            ));

            if (!$item) {
                throw new Exception("Barang dengan kode '$itemCode' tidak ditemukan.");
            }

            if ($item->stock_qty <= 0) {
                throw new Exception("Stok barang '$item->item_name' habis.");
            }

            // 2. Cek apakah penumpang sudah menerima barang ini (Optional: Cegah double, kecuali barang consumable)
            $alreadyTaken = $this->wpdb->get_var($this->wpdb->prepare(
                "SELECT id FROM {$this->wpdb->prefix}umh_passenger_equipment WHERE passenger_id = %d AND item_id = %d",
                $passengerId, $item->id
            ));

            if ($alreadyTaken) {
                throw new Exception("Jemaah ini sudah menerima barang '$item->item_name'.");
            }

            // 3. Catat Distribusi
            $inserted = $this->wpdb->insert($this->wpdb->prefix . 'umh_passenger_equipment', [
                'passenger_id' => $passengerId,
                'item_id' => $item->id,
                'status' => 'taken',
                'taken_at' => current_time('mysql'),
                'staff_id' => $staffId
            ]);

            if (!$inserted) throw new Exception("Gagal mencatat distribusi.");

            // 4. Kurangi Stok
            $this->wpdb->query($this->wpdb->prepare(
                "UPDATE {$this->wpdb->prefix}umh_inventory_items SET stock_qty = stock_qty - 1 WHERE id = %d",
                $item->id
            ));

            // 5. Log Inventory Movement
            $this->wpdb->insert($this->wpdb->prefix . 'umh_inventory_logs', [
                'item_id' => $item->id,
                'qty_change' => -1,
                'transaction_type' => 'distribution',
                'reference_id' => $passengerId, // Ref ke ID Penumpang
                'user_id' => $staffId,
                'notes' => "Distribusi ke Penumpang ID: $passengerId"
            ]);

            $this->wpdb->query('COMMIT');

            return [
                'success' => true,
                'message' => "Berhasil mendistribusikan $item->item_name",
                'item_name' => $item->item_name,
                'remaining_stock' => $item->stock_qty - 1
            ];

        } catch (Exception $e) {
            $this->wpdb->query('ROLLBACK');
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}