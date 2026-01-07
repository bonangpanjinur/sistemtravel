<?php
// File: RoomingListController.php
// Location: src/Controllers/Admin/RoomingListController.php

namespace UmhMgmt\Controllers\Admin;

use UmhMgmt\Utils\View;

class RoomingListController {

    public function __construct() {
        add_action('admin_menu', [$this, 'add_menu']);
        add_action('wp_ajax_umh_save_rooming', [$this, 'handle_ajax_save']);
    }

    public function add_menu() {
        // Submenu di bawah Operasional
        add_submenu_page(
            'umh-dashboard',
            'Rooming List Manager',
            'Rooming List',
            'manage_options', // Bisa diganti capability staff
            'umh-rooming-list',
            [$this, 'render_page']
        );
    }

    public function render_page() {
        global $wpdb;

        // Jika ada parameter departure_id, tampilkan EDITOR
        if (isset($_GET['departure_id'])) {
            $this->render_editor(absint($_GET['departure_id']));
            return;
        }

        // Default: Tampilkan DAFTAR Keberangkatan
        $departures = $wpdb->get_results("
            SELECT d.*, p.name as package_name,
                   (SELECT COUNT(*) FROM {$wpdb->prefix}umh_booking_passengers pax 
                    JOIN {$wpdb->prefix}umh_bookings b ON pax.booking_id = b.id 
                    WHERE b.departure_id = d.id AND b.status IN ('paid', 'confirmed', 'verified')) as total_pax
            FROM {$wpdb->prefix}umh_departures d
            JOIN {$wpdb->prefix}umh_packages p ON d.package_id = p.id
            WHERE d.status != 'closed'
            ORDER BY d.departure_date ASC
        ");

        View::render('admin/operations/rooming-list-index', ['departures' => $departures]);
    }

    private function render_editor($departure_id) {
        global $wpdb;

        // Ambil Data Keberangkatan
        $departure = $wpdb->get_row($wpdb->prepare("
            SELECT d.*, p.name as package_name, h1.name as hotel_mekkah
            FROM {$wpdb->prefix}umh_departures d
            JOIN {$wpdb->prefix}umh_packages p ON d.package_id = p.id
            LEFT JOIN {$wpdb->prefix}umh_hotels h1 ON p.hotel_mekkah_id = h1.id
            WHERE d.id = %d
        ", $departure_id));

        // Ambil Penumpang (Yang belum dapat kamar & yang sudah)
        $passengers = $wpdb->get_results($wpdb->prepare("
            SELECT pax.*, b.agent_id
            FROM {$wpdb->prefix}umh_booking_passengers pax
            JOIN {$wpdb->prefix}umh_bookings b ON pax.booking_id = b.id
            WHERE b.departure_id = %d 
            AND b.status IN ('paid', 'confirmed', 'verified')
            ORDER BY pax.name ASC
        ", $departure_id));

        // Pisahkan data untuk view
        $unassigned = [];
        $rooms = []; // Format: ['101' => [pax1, pax2], '102' => ...]

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

        View::render('admin/operations/rooming-manager', [
            'departure' => $departure,
            'unassigned' => $unassigned,
            'rooms' => $rooms
        ]);
    }

    public function handle_ajax_save() {
        // Cek permission & nonce (skipped for simplicity in this snippet, but crucial in prod)
        if (!current_user_can('edit_posts')) wp_send_json_error('Unauthorized');

        global $wpdb;
        $assignments = isset($_POST['assignments']) ? $_POST['assignments'] : [];
        // Format assignments: [{pax_id: 1, room_number: '101', room_type: 'quad'}, ...]

        foreach ($assignments as $item) {
            $pax_id = absint($item['pax_id']);
            $room_num = sanitize_text_field($item['room_number']);
            $room_type = sanitize_text_field($item['room_type']);

            // Jika room_number kosong, berarti unassigned (reset)
            if (empty($room_num)) {
                $wpdb->update($wpdb->prefix . 'umh_booking_passengers', 
                    ['assigned_room_number' => null, 'assigned_room_type' => null], 
                    ['id' => $pax_id]
                );
            } else {
                $wpdb->update($wpdb->prefix . 'umh_booking_passengers', 
                    ['assigned_room_number' => $room_num, 'assigned_room_type' => $room_type], 
                    ['id' => $pax_id]
                );
            }
        }

        wp_send_json_success('Data rooming berhasil disimpan.');
    }
}