<?php
// File: DepartureController.php
// Location: src/Controllers/Admin/DepartureController.php

namespace UmhMgmt\Controllers\Admin;

use UmhMgmt\Utils\View;
use UmhMgmt\Repositories\OperationalRepository;
use UmhMgmt\Repositories\PackageRepository;
use UmhMgmt\Repositories\MasterDataRepository;

class DepartureController {
    private $repo;
    private $packageRepo;
    private $masterRepo;

    public function __construct() {
        $this->repo = new OperationalRepository();
        $this->packageRepo = new PackageRepository();
        // Memanggil Master Data untuk dropdown Muthawif & Bus
        $this->masterRepo = new MasterDataRepository();

        add_action('admin_menu', [$this, 'add_submenu_page']);
        add_action('admin_post_umh_save_departure', [$this, 'handle_save_departure']);
        add_action('admin_post_umh_delete_departure', [$this, 'handle_delete_departure']);
    }

    public function add_submenu_page() {
        add_submenu_page(
            'umh-dashboard',
            'Jadwal Keberangkatan',
            'Departures',
            'manage_options',
            'umh-departures',
            [$this, 'render_page']
        );
    }

    public function handle_save_departure() {
        // 1. Security: Cek Nonce & Permission
        check_admin_referer('umh_departure_nonce');
        if (!current_user_can('manage_options')) wp_die('Unauthorized access');

        global $wpdb;
        $table = $wpdb->prefix . 'umh_departures';

        // 2. Sanitization: Bersihkan input sebelum masuk DB
        $data = [
            'package_id'      => absint($_POST['package_id']),
            'departure_date'  => sanitize_text_field($_POST['departure_date']),
            'total_seats'     => absint($_POST['total_seats']),
            'status'          => sanitize_text_field($_POST['status']),
            'muthawif_id'     => !empty($_POST['muthawif_id']) ? absint($_POST['muthawif_id']) : null,
            'bus_provider_id' => !empty($_POST['bus_provider_id']) ? absint($_POST['bus_provider_id']) : null,
        ];

        // 3. Logic: Insert atau Update
        if (empty($_POST['id'])) {
            // Jika baru, available seats = total seats
            $data['available_seats'] = $data['total_seats'];
            $wpdb->insert($table, $data);
        } else {
            $id = absint($_POST['id']);
            // Update data (Available seats tidak di-reset di sini agar tidak merusak data booking yg berjalan)
            $wpdb->update($table, $data, ['id' => $id]);
        }

        wp_redirect(admin_url('admin.php?page=umh-departures&message=saved'));
        exit;
    }

    public function handle_delete_departure() {
        check_admin_referer('umh_departure_nonce');
        if (!current_user_can('manage_options')) wp_die('Unauthorized');

        global $wpdb;
        $id = absint($_GET['id']);
        
        // Safety Check: Jangan hapus jika sudah ada booking
        $booking_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}umh_bookings WHERE departure_id = %d", $id));
        
        if ($booking_count > 0) {
            wp_die('Error: Tidak bisa menghapus jadwal yang sudah ada pendaftar (Booking). Silakan ubah status menjadi Closed/Arsip.');
        }

        $wpdb->delete($wpdb->prefix . 'umh_departures', ['id' => $id]);
        wp_redirect(admin_url('admin.php?page=umh-departures&message=deleted'));
        exit;
    }

    public function render_page() {
        // Ambil data dari Repository (Logic Layer)
        $departures = $this->repo->getUpcomingDepartures(100); 
        $packages   = $this->packageRepo->all();
        $muthawifs  = $this->masterRepo->getMuthawifs();
        $buses      = $this->masterRepo->getBusProviders();

        // Lempar ke View (Presentation Layer)
        View::render('admin/departures', [
            'departures' => $departures,
            'packages'   => $packages,
            'muthawifs'  => $muthawifs,
            'buses'      => $buses
        ]);
    }
}