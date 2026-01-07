<?php
// File: InventoryScannerController.php
// Location: src/Controllers/Admin/InventoryScannerController.php

namespace UmhMgmt\Controllers\Admin;

use UmhMgmt\Utils\View;

class InventoryScannerController {

    public function __construct() {
        add_action('admin_menu', [$this, 'add_menu']);
        add_action('wp_ajax_umh_process_scan', [$this, 'process_scan_ajax']);
    }

    public function add_menu() {
        // Halaman Scanner di bawah menu Operasional (atau terpisah jika staff gudang beda role)
        add_submenu_page(
            'umh-dashboard',
            'Inventory Scanner',
            'Scanner Gudang',
            'manage_options', // Bisa diganti capability khusus gudang
            'umh-inventory-scanner',
            [$this, 'render_scanner_page']
        );
    }

    public function render_scanner_page() {
        // Render halaman visual scanner
        View::render('admin/operations/inventory-scanner');
    }

    public function process_scan_ajax() {
        // 1. Security Check
        if (!current_user_can('edit_posts')) wp_send_json_error(['message' => 'Unauthorized']);
        
        $barcode = sanitize_text_field($_POST['barcode']);
        $mode = sanitize_text_field($_POST['mode']); // 'out' (keluar/ambil) atau 'in' (masuk/stok baru)
        $ref_id = sanitize_text_field($_POST['ref_id']); // Misal: ID Jemaah atau No Booking

        global $wpdb;

        // 2. Cari Barang berdasarkan Barcode
        $item = $wpdb->get_row($wpdb->prepare("
            SELECT i.*, c.item_name 
            FROM {$wpdb->prefix}umh_inventory_items i
            JOIN {$wpdb->prefix}umh_equipment_catalog c ON i.catalog_id = c.id
            WHERE c.barcode = %s
        ", $barcode));

        if (!$item) {
            wp_send_json_error(['message' => 'Barang tidak ditemukan! Cek barcode.']);
        }

        // 3. Logic Stok
        $qty_change = ($mode === 'out') ? -1 : 1;
        
        // Cek stok cukup?
        if ($mode === 'out' && $item->stock_qty <= 0) {
            wp_send_json_error(['message' => "Stok {$item->item_name} habis!"]);
        }

        // 4. Update Stok
        $wpdb->update(
            $wpdb->prefix . 'umh_inventory_items',
            ['stock_qty' => $item->stock_qty + $qty_change],
            ['id' => $item->id]
        );

        // 5. Catat Log
        $wpdb->insert(
            $wpdb->prefix . 'umh_inventory_logs',
            [
                'item_id' => $item->id,
                'qty_change' => $qty_change,
                'transaction_type' => ($mode === 'out' ? 'scan_out_jemaah' : 'scan_in_stock'),
                'reference_id' => $ref_id,
                'user_id' => get_current_user_id(),
                'notes' => 'Scan via Dashboard'
            ]
        );

        // 6. Return Success
        wp_send_json_success([
            'item_name' => $item->item_name,
            'new_stock' => $item->stock_qty + $qty_change,
            'message' => ($mode === 'out' ? 'Barang Keluar: ' : 'Stok Masuk: ') . $item->item_name
        ]);
    }
}