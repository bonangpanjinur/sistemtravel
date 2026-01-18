<?php
// File: OperationalService.php
// Location: src/Services/OperationalService.php

namespace UmhMgmt\Services;

use UmhMgmt\Repositories\OperationalRepository;
use Exception;

class OperationalService {
    private $repo;
    private $wpdb;

    public function __construct(OperationalRepository $repo) {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->repo = $repo;
    }

    /**
     * SPRINT 2: Distribusi Perlengkapan ke Jemaah
     * Mencatat barang yang diambil jemaah & mengurangi stok gudang
     */
    public function distributeItemToPassenger($passengerId, $itemCode, $staffUserId) {
        $this->wpdb->query('START TRANSACTION');

        try {
            // 1. Cari Item berdasarkan Kode/SKU
            $item = $this->wpdb->get_row($this->wpdb->prepare(
                "SELECT id, item_name, stock_qty FROM {$this->wpdb->prefix}umh_inventory_items WHERE item_code = %s FOR UPDATE",
                $itemCode
            ));

            if (!$item) {
                throw new Exception("Barang dengan kode '$itemCode' tidak ditemukan.");
            }

            if ($item->stock_qty <= 0) {
                throw new Exception("Stok barang '{$item->item_name}' habis.");
            }

            // 2. Cek apakah jemaah sudah mengambil barang ini sebelumnya?
            $alreadyTaken = $this->wpdb->get_var($this->wpdb->prepare(
                "SELECT id FROM {$this->wpdb->prefix}umh_passenger_equipment 
                 WHERE passenger_id = %d AND item_id = %d AND status = 'taken'",
                $passengerId, $item->id
            ));

            if ($alreadyTaken) {
                throw new Exception("Jemaah ini sudah mengambil barang '{$item->item_name}'.");
            }

            // 3. Catat Log Pengambilan Jemaah
            $this->wpdb->insert("{$this->wpdb->prefix}umh_passenger_equipment", [
                'passenger_id' => $passengerId,
                'item_id' => $item->id,
                'status' => 'taken',
                'taken_at' => current_time('mysql'),
                'staff_id' => $staffUserId
            ]);

            // 4. Kurangi Stok Gudang
            $this->wpdb->query($this->wpdb->prepare(
                "UPDATE {$this->wpdb->prefix}umh_inventory_items SET stock_qty = stock_qty - 1 WHERE id = %d",
                $item->id
            ));

            // 5. Catat Log Inventory Keluar
            $this->wpdb->insert("{$this->wpdb->prefix}umh_inventory_logs", [
                'item_id' => $item->id,
                'qty_change' => -1,
                'transaction_type' => 'distribution',
                'reference_id' => 'PAX-' . $passengerId,
                'user_id' => $staffUserId,
                'notes' => "Didistribusikan ke Jemaah ID: $passengerId"
            ]);

            $this->wpdb->query('COMMIT');
            return [
                'success' => true, 
                'item_name' => $item->item_name,
                'remaining_stock' => $item->stock_qty - 1
            ];

        } catch (Exception $e) {
            $this->wpdb->query('ROLLBACK');
            throw $e;
        }
    }

    /**
     * Get list barang yang sudah diambil jemaah tertentu
     */
    public function getPassengerEquipmentLog($passengerId) {
        return $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT pe.*, i.item_name, i.item_code 
             FROM {$this->wpdb->prefix}umh_passenger_equipment pe
             JOIN {$this->wpdb->prefix}umh_inventory_items i ON pe.item_id = i.id
             WHERE pe.passenger_id = %d
             ORDER BY pe.taken_at DESC",
            $passengerId
        ));
    }
}