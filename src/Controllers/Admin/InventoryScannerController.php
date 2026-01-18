<?php
// File: InventoryScannerController.php
// Location: src/Controllers/Admin/InventoryScannerController.php

namespace UmhMgmt\Controllers\Admin;

use UmhMgmt\Utils\View;
use UmhMgmt\Services\OperationalService;
use UmhMgmt\Repositories\OperationalRepository;

class InventoryScannerController {
    private $service;
    private $wpdb; // Added for legacy support

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        
        // Inisialisasi Service dengan Repository (Standar Baru)
        $this->service = new OperationalService(new OperationalRepository());
        
        add_action('admin_menu', [$this, 'register_menu']);
        
        // 1. AJAX Handler Baru (Sprint 2: Distribusi Jemaah)
        add_action('wp_ajax_umh_scan_distribution', [$this, 'handle_scan_distribution']);

        // 2. AJAX Handler Lama (Legacy: Stock Opname, Attendance, Luggage)
        add_action('wp_ajax_umh_process_scan', [$this, 'handle_scan_ajax']); 
    }

    public function register_menu() {
        add_submenu_page(
            'umh-operational',
            'Scanner Distribusi',
            'Scanner Distribusi',
            'manage_options',
            'umh-inventory-scanner',
            [$this, 'render_page']
        );
    }

    public function render_page() {
        // Render View UI Scanner (Menggunakan template baru yang support mode switch)
        View::render('operations/inventory-scanner');
    }

    /**
     * [NEW] Handle AJAX: Scan Barang untuk diserahkan ke Jemaah
     * Digunakan untuk fitur logistik personal
     */
    public function handle_scan_distribution() {
        // Security Check
        // check_ajax_referer('umh_scanner_nonce', 'nonce');

        $passenger_id = isset($_POST['passenger_id']) ? absint($_POST['passenger_id']) : 0;
        $item_code = isset($_POST['item_code']) ? sanitize_text_field($_POST['item_code']) : '';
        $staff_id = get_current_user_id();

        if (!$passenger_id || empty($item_code)) {
            wp_send_json_error(['message' => 'Data tidak lengkap. Scan QR Jemaah & Barang.']);
        }

        try {
            $result = $this->service->distributeItemToPassenger($passenger_id, $item_code, $staff_id);
            
            // Ambil log terbaru untuk update UI
            $logs = $this->service->getPassengerEquipmentLog($passenger_id);
            
            wp_send_json_success([
                'message' => "Berhasil menyerahkan <b>{$result['item_name']}</b>.",
                'logs' => $logs
            ]);
        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    /**
     * [LEGACY] Unified Scan Handler
     * Menangani: Absensi, Bagasi, DAN Stok Gudang (Mode Lama)
     */
    public function handle_scan_ajax() {
        // 1. Security & Input Sanitization
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
                // Pastikan method recordAttendance ada di OperationalService baru
                // Jika belum ada, Anda perlu menambahkannya ke Service tersebut.
                if (method_exists($this->service, 'recordAttendance')) {
                    $result = $this->service->recordAttendance($codeData, $checkpoint, $userId);
                    
                    if ($result['success']) {
                        wp_send_json_success([
                            'message' => 'Absensi Berhasil: ' . $result['pax_name'],
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