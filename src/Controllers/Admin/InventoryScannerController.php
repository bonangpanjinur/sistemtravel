<?php
// Folder: src/Controllers/Admin/
// File: InventoryScannerController.php

namespace UmhMgmt\Controllers\Admin;

use UmhMgmt\Utils\View;
use UmhMgmt\Services\OperationalService;

class InventoryScannerController {
    private $operationalService;
    private $wpdb;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->operationalService = new OperationalService();

        // Mengembalikan Hook Admin Menu agar menu muncul di dashboard
        add_action('admin_menu', [$this, 'add_menu']);
        
        // Mengembalikan AJAX Handler (Support nama action lama & baru)
        add_action('wp_ajax_umh_process_scan', [$this, 'handle_scan_ajax']); 
    }

    public function add_menu() {
        add_submenu_page(
            'umh-dashboard', // Slug parent menu
            'Scanner & Operasional',
            'Scanner App',
            'manage_options', // Capability (bisa disesuaikan role)
            'umh-inventory-scanner',
            [$this, 'index']
        );
    }

    public function index() {
        // Render View UI Scanner
        // Pastikan view ini support input 'mode' (Inventory/Attendance/Luggage)
        View::render('admin/operational/scanner_ui', []); 
    }

    /**
     * Unified Scan Handler
     * Menangani: Absensi, Bagasi, DAN Stok Gudang
     */
    public function handle_scan_ajax() {
        // 1. Security & Input Sanitization
        // Cek capability minimal (edit_posts) atau nonce jika ada
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }

        // Support parameter 'qr_data' (baru) atau 'barcode' (lama)
        $codeData = sanitize_text_field($_POST['qr_data'] ?? $_POST['barcode']);
        
        // Support parameter 'scan_mode' (baru) atau 'mode' (lama)
        $mode = sanitize_text_field($_POST['scan_mode'] ?? $_POST['mode']); 
        
        $checkpoint = sanitize_text_field($_POST['checkpoint'] ?? 'default');
        $refId = sanitize_text_field($_POST['ref_id'] ?? '');
        $userId = get_current_user_id();

        if (empty($codeData) || empty($mode)) {
            wp_send_json_error(['message' => 'Data scan tidak lengkap']);
        }

        try {
            // --- A. LOGIC OPERASIONAL (Absensi & Bagasi) ---
            if ($mode === 'attendance') {
                $result = $this->operationalService->recordAttendance($codeData, $checkpoint, $userId);
                
                if ($result['success']) {
                    wp_send_json_success([
                        'message' => 'Absensi Berhasil: ' . $result['pax_name'],
                        'data' => $result
                    ]);
                } else {
                    wp_send_json_error(['message' => $result['message']]);
                }
            } 
            
            elseif ($mode === 'luggage') {
                // Logic Tagging Bagasi (Simulasi sukses dulu, nanti hubungkan ke Service)
                wp_send_json_success(['message' => 'Bagasi Tercatat: ' . $codeData]);
            }

            // --- B. LOGIC GUDANG/INVENTORY (Dikembalikan dari kode lama) ---
            elseif ($mode === 'in' || $mode === 'out') {
                $this->processInventoryStock($codeData, $mode, $refId, $userId);
            } 
            
            else {
                wp_send_json_error(['message' => 'Mode scan tidak dikenal: ' . $mode]);
            }

        } catch (\Exception $e) {
            wp_send_json_error(['message' => 'System Error: ' . $e->getMessage()]);
        }
    }

    /**
     * Logic Private untuk Inventory Gudang
     * (Diadaptasi dari kode lama Anda agar tetap jalan)
     */
    private function processInventoryStock($barcode, $mode, $refId, $userId) {
        // 1. Cari Barang
        // Join ke tabel catalog untuk dapat info barang
        $item = $this->wpdb->get_row($this->wpdb->prepare("
            SELECT i.*, c.item_name, c.sku 
            FROM {$this->wpdb->prefix}umh_inventory_items i
            LEFT JOIN {$this->wpdb->prefix}umh_equipment_catalog c ON i.catalog_id = c.id
            WHERE c.sku = %s OR i.item_code = %s
        ", $barcode, $barcode));

        if (!$item) {
            // Fallback: Coba cari by item_code langsung jika catalog belum link
            $item = $this->wpdb->get_row($this->wpdb->prepare("
                SELECT * FROM {$this->wpdb->prefix}umh_inventory_items WHERE item_code = %s
            ", $barcode));
        }

        if (!$item) {
            wp_send_json_error(['message' => 'Barang tidak ditemukan di database!']);
        }

        // 2. Cek Stok (Untuk mode keluar)
        $qtyChange = ($mode === 'out') ? -1 : 1;
        
        if ($mode === 'out' && $item->stock_qty <= 0) {
            wp_send_json_error(['message' => "Stok {$item->item_name} Habis! (0)"]);
        }

        // 3. Update Stok
        $this->wpdb->update(
            $this->wpdb->prefix . 'umh_inventory_items',
            ['stock_qty' => $item->stock_qty + $qtyChange],
            ['id' => $item->id]
        );

        // 4. Catat Log (Audit Trail)
        // Pastikan tabel umh_inventory_logs ada (sudah ditambahkan di Schema baru)
        $this->wpdb->insert(
            $this->wpdb->prefix . 'umh_inventory_logs',
            [
                'item_id' => $item->id,
                'qty_change' => $qtyChange,
                'transaction_type' => ($mode === 'out' ? 'scan_out' : 'scan_in'),
                'reference_id' => $refId, // Bisa ID Jamaah atau No DO
                'user_id' => $userId,
                'notes' => 'Scan via Dashboard App',
                'created_at' => current_time('mysql')
            ]
        );

        // 5. Response Sukses
        wp_send_json_success([
            'item_name' => $item->item_name ?? $item->item_code,
            'new_stock' => $item->stock_qty + $qtyChange,
            'message' => ($mode === 'out' ? 'Barang Keluar: ' : 'Stok Masuk: ') . ($item->item_name ?? 'Item')
        ]);
    }
}