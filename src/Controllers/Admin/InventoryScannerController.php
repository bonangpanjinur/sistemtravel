<?php
// File: src/Controllers/Admin/InventoryScannerController.php

namespace App\Controllers\Admin;

use App\Services\OperationalService;
use App\Utils\View;

class InventoryScannerController {
    private $opService;
    private $wpdb;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        
        // Inisialisasi Service (Versi Baru tidak butuh Inject Repository di constructor)
        $this->opService = new OperationalService();
        
        // Halaman Menu
        add_action('admin_menu', [$this, 'add_menu_page']);

        // 1. AJAX Handler Baru (Sprint 2: Distribusi Jemaah via Scanner Baru)
        add_action('wp_ajax_umh_scan_distribution', [$this, 'handle_scan_distribution']);

        // 2. AJAX Handler Lama (Legacy: Stock Opname, Attendance, Luggage via Scanner Lama)
        add_action('wp_ajax_umh_process_scan', [$this, 'handle_scan_ajax']); 
    }

    public function add_menu_page() {
        add_submenu_page(
            'umh-operational',
            'Scanner Distribusi',
            'Scanner Distribusi',
            'manage_options', 
            'umh-scanner',
            [$this, 'render_page']
        );
    }

    public function render_page() {
        View::render('operations/inventory-scanner');
    }

    /**
     * [NEW] Handle AJAX: Scan Barang untuk diserahkan ke Jemaah
     * Endpoint: umh_scan_distribution
     */
    public function handle_scan_distribution() {
        check_ajax_referer('umh_scanner_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }

        $passengerId = isset($_POST['passenger_id']) ? intval($_POST['passenger_id']) : 0;
        $itemCode = isset($_POST['item_code']) ? sanitize_text_field($_POST['item_code']) : '';
        $staffId = get_current_user_id();

        if (!$passengerId || empty($itemCode)) {
            wp_send_json_error(['message' => 'Data tidak lengkap. Scan ID Jemaah dan Barang.']);
        }

        // Panggil Service Baru (method: distributeItem)
        // Perbedaan nama method ditangani disini: distributeItem vs distributeItemToPassenger
        $result = $this->opService->distributeItem($passengerId, $itemCode, $staffId);

        if ($result['success']) {
            // Cek jika service memiliki method untuk ambil log (Backward Compatibility)
            $logs = method_exists($this->opService, 'getPassengerEquipmentLog') 
                ? $this->opService->getPassengerEquipmentLog($passengerId) 
                : [];

            wp_send_json_success(array_merge($result, ['logs' => $logs]));
        } else {
            wp_send_json_error($result);
        }
    }

    /**
     * [LEGACY] Unified Scan Handler
     * Menangani: Absensi, Bagasi, DAN Stok Gudang (Mode Lama)
     * Endpoint: umh_process_scan
     */
    public function handle_scan_ajax() {
        // 1. Security & Input Sanitization
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }

        // Support parameter 'qr_data' (baru) atau 'barcode' (lama)
        $codeData = sanitize_text_field($_POST['qr_data'] ?? $_POST['barcode'] ?? '');
        
        // Support parameter 'scan_mode' (baru) atau 'mode' (lama)
        $mode = sanitize_text_field($_POST['scan_mode'] ?? $_POST['mode'] ?? ''); 
        
        $checkpoint = sanitize_text_field($_POST['checkpoint'] ?? 'default');
        $refId = sanitize_text_field($_POST['ref_id'] ?? '');
        $userId = get_current_user_id();

        if (empty($codeData) || empty($mode)) {
            wp_send_json_error(['message' => 'Data scan tidak lengkap']);
        }

        try {
            // --- A. LOGIC OPERASIONAL (Absensi & Bagasi) ---
            if ($mode === 'attendance') {
                // Pastikan method recordAttendance ada di OperationalService baru
                if (method_exists($this->opService, 'recordAttendance')) {
                    $result = $this->opService->recordAttendance($codeData, $checkpoint, $userId);
                    
                    if ($result['success']) {
                        wp_send_json_success([
                            'message' => 'Absensi Berhasil: ' . ($result['pax_name'] ?? 'Jemaah'),
                            'data' => $result
                        ]);
                    } else {
                        wp_send_json_error(['message' => $result['message']]);
                    }
                } else {
                    wp_send_json_error(['message' => 'Fitur Absensi belum diaktifkan di Service baru.']);
                }
            } 
            
            elseif ($mode === 'luggage') {
                // Logic Tagging Bagasi (Simulasi sukses dulu, nanti hubungkan ke Service)
                wp_send_json_success(['message' => 'Bagasi Tercatat: ' . $codeData]);
            }

            // --- B. LOGIC GUDANG/INVENTORY (Legacy Direct DB) ---
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
     * [LEGACY] Logic Private untuk Inventory Gudang
     * Memproses Stock In/Out secara langsung
     */
    private function processInventoryStock($barcode, $mode, $refId, $userId) {
        // 1. Cari Barang
        // Join ke tabel catalog untuk dapat info barang
        $item = $this->wpdb->get_row($this->wpdb->prepare("
            SELECT i.* FROM {$this->wpdb->prefix}umh_inventory_items i
            WHERE i.item_code = %s
        ", $barcode));

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
        $this->wpdb->insert(
            $this->wpdb->prefix . 'umh_inventory_logs',
            [
                'item_id' => $item->id,
                'qty_change' => $qtyChange,
                'transaction_type' => ($mode === 'out' ? 'scan_out' : 'scan_in'),
                'reference_id' => $refId, // Bisa ID Jamaah atau No DO
                'user_id' => $userId,
                'notes' => 'Scan via Dashboard App (Legacy Mode)',
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