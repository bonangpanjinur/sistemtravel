<?php
// Folder: src/Controllers/Admin/
// File: RoomingListController.php

namespace UmhMgmt\Controllers\Admin;

use UmhMgmt\Utils\View;

class RoomingListController {
    private $wpdb;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        
        // Hooks dari Kode Lama
        add_action('admin_menu', [$this, 'add_menu']);
        add_action('wp_ajax_umh_save_rooming', [$this, 'handle_ajax_save']); // Handler Lama
        
        // Hooks Baru untuk Visual Editor
        add_action('wp_ajax_umh_get_visual_rooming', [$this, 'getVisualRoomingData']); // Handler Baru (API GET)
        add_action('wp_ajax_umh_save_visual_rooming', [$this, 'saveRoomAssignment']); // Handler Baru (API SAVE)
    }

    public function add_menu() {
        // Submenu di bawah Operasional (Kode Lama)
        add_submenu_page(
            'umh-dashboard',
            'Rooming List Manager',
            'Rooming List',
            'manage_options', // Bisa diganti capability staff
            'umh-rooming-list',
            [$this, 'render_page'] // Arahkan ke method render_page (gabungan index & logic lama)
        );
    }

    /**
     * Main Page Render
     * Menggabungkan logic 'index' baru dan 'render_page' lama
     */
    public function render_page() {
        $departureId = isset($_GET['departure_id']) ? absint($_GET['departure_id']) : 0;

        // Jika ada parameter departure_id, tampilkan EDITOR (Logic Lama & Baru digabung)
        if ($departureId > 0) {
            // Kita render tampilan editor yang bisa support Mode Table (Lama) dan Mode Visual (Baru)
            // Di view nanti bisa dibuat tab atau toggle
            $this->render_editor($departureId);
            return;
        }

        // Default: Tampilkan DAFTAR Keberangkatan (Logic Lama)
        $departures = $this->wpdb->get_results("
            SELECT d.*, p.name as package_name,
                   (SELECT COUNT(*) FROM {$this->wpdb->prefix}umh_booking_passengers pax 
                    JOIN {$this->wpdb->prefix}umh_bookings b ON pax.booking_id = b.id 
                    WHERE b.departure_id = d.id AND b.status IN ('paid', 'confirmed', 'verified')) as total_pax
            FROM {$this->wpdb->prefix}umh_departures d
            JOIN {$this->wpdb->prefix}umh_packages p ON d.package_id = p.id
            WHERE d.status != 'closed'
            ORDER BY d.departure_date ASC
        ");

        // Render view index daftar keberangkatan
        View::render('operations/rooming-list-index', ['departures' => $departures]);
    }

    /**
     * Render Halaman Editor Rooming (Detail per Keberangkatan)
     */
    private function render_editor($departure_id) {
        // Ambil Data Keberangkatan (Logic Lama)
        $departure = $this->wpdb->get_row($this->wpdb->prepare("
            SELECT d.*, p.name as package_name, h1.name as hotel_mekkah
            FROM {$this->wpdb->prefix}umh_departures d
            JOIN {$this->wpdb->prefix}umh_packages p ON d.package_id = p.id
            LEFT JOIN {$this->wpdb->prefix}umh_hotels h1 ON p.hotel_mekkah_id = h1.id
            WHERE d.id = %d
        ", $departure_id));

        if (!$departure) {
            echo '<div class="error"><p>Data keberangkatan tidak ditemukan.</p></div>';
            return;
        }

        // Ambil Penumpang (Logic Lama)
        $passengers = $this->wpdb->get_results($this->wpdb->prepare("
            SELECT pax.*, b.agent_id
            FROM {$this->wpdb->prefix}umh_booking_passengers pax
            JOIN {$this->wpdb->prefix}umh_bookings b ON pax.booking_id = b.id
            WHERE b.departure_id = %d 
            AND b.status IN ('paid', 'confirmed', 'verified')
            ORDER BY pax.name ASC
        ", $departure_id));

        // Format Data untuk View PHP Tradisional (Logic Lama)
        $unassigned = [];
        $rooms = []; 

        foreach ($passengers as $pax) {
            if (empty($pax->assigned_room_number)) {
                $unassigned[] = $pax;
            } else {
                $room_num = $pax->assigned_room_number;
                if (!isset($rooms[$room_num])) {
                    $rooms[$room_num] = [
                        'number' => $room_num,
                        'type' => $pax->assigned_room_type,
                        'occupants' => []
                    ];
                }
                $rooms[$room_num]['occupants'][] = $pax;
            }
        }

        // Render View Editor
        // View ini nanti bisa memanggil AJAX 'getVisualRoomingData' jika user switch ke Visual Mode
        View::render('operations/rooming-manager', [
            'departure' => $departure,
            'unassigned' => $unassigned,
            'rooms' => $rooms
        ]);
    }

    // --- API & AJAX HANDLERS (MODERN & LEGACY SUPPORT) ---

    /**
     * API Endpoint (BARU): Get Data untuk Visual Rooming Editor (Frontend React/JS)
     * Mengembalikan JSON struktur lantai & kamar
     */
    public function getVisualRoomingData() {
        // Security Check (Optional tapi recommended)
        if (!current_user_can('edit_posts')) wp_send_json_error(['message' => 'Unauthorized']);

        $departureId = isset($_GET['departure_id']) ? intval($_GET['departure_id']) : 0;
        
        if (!$departureId) wp_send_json_error(['message' => 'Missing Departure ID']);

        // 1. Ambil Semua Penumpang
        $passengers = $this->wpdb->get_results($this->wpdb->prepare("
            SELECT p.id, p.name, p.assigned_room_number, p.assigned_room_type 
            FROM {$this->wpdb->prefix}umh_booking_passengers p
            JOIN {$this->wpdb->prefix}umh_bookings b ON p.booking_id = b.id
            WHERE b.departure_id = %d AND b.status IN ('paid', 'confirmed', 'verified')
        ", $departureId));

        // 2. Susun Struktur Data Kamar JSON
        $rooms = [];
        $unassigned = [];

        foreach ($passengers as $pax) {
            if ($pax->assigned_room_number) {
                $roomNum = $pax->assigned_room_number;
                if (!isset($rooms[$roomNum])) {
                    $rooms[$roomNum] = [
                        'number' => $roomNum,
                        'type' => $pax->assigned_room_type, // quad, triple, double
                        'capacity' => $this->getCapacityFromType($pax->assigned_room_type),
                        'occupants' => []
                    ];
                }
                $rooms[$roomNum]['occupants'][] = [
                    'id' => $pax->id,
                    'name' => $pax->name
                ];
            } else {
                $unassigned[] = [
                    'id' => $pax->id,
                    'name' => $pax->name,
                    'pref_type' => $pax->assigned_room_type
                ];
            }
        }

        wp_send_json_success([
            'rooms' => array_values($rooms),
            'unassigned_passengers' => $unassigned,
            'stats' => [
                'total_pax' => count($passengers),
                'assigned' => count($passengers) - count($unassigned),
                'unassigned' => count($unassigned)
            ]
        ]);
    }

    /**
     * API Endpoint (BARU): Save Room Assignment (Single Drag & Drop Action)
     */
    public function saveRoomAssignment() {
        if (!current_user_can('edit_posts')) wp_send_json_error(['message' => 'Unauthorized']);

        $paxId = intval($_POST['pax_id']);
        $roomNum = sanitize_text_field($_POST['room_number']);
        // Optional: Room Type jika berubah saat drag
        
        $this->wpdb->update(
            "{$this->wpdb->prefix}umh_booking_passengers",
            ['assigned_room_number' => $roomNum],
            ['id' => $paxId]
        );

        wp_send_json_success(['message' => 'Moved']);
    }

    /**
     * AJAX Handler (LAMA): Save Bulk Rooming
     * Untuk support fitur "Save All" dari kode lama
     */
    public function handle_ajax_save() {
        if (!current_user_can('edit_posts')) wp_send_json_error('Unauthorized');

        $assignments = isset($_POST['assignments']) ? $_POST['assignments'] : [];
        // Format assignments: [{pax_id: 1, room_number: '101', room_type: 'quad'}, ...]

        foreach ($assignments as $item) {
            $pax_id = absint($item['pax_id']);
            $room_num = sanitize_text_field($item['room_number']);
            $room_type = sanitize_text_field($item['room_type']);

            $data = [];
            if (empty($room_num)) {
                $data = ['assigned_room_number' => null, 'assigned_room_type' => null];
            } else {
                $data = ['assigned_room_number' => $room_num, 'assigned_room_type' => $room_type];
            }

            $this->wpdb->update(
                $this->wpdb->prefix . 'umh_booking_passengers', 
                $data, 
                ['id' => $pax_id]
            );
        }

        wp_send_json_success('Data rooming berhasil disimpan.');
    }

    private function getCapacityFromType($type) {
        switch($type) {
            case 'quad': return 4;
            case 'triple': return 3;
            case 'double': return 2;
            default: return 4;
        }
    }
}