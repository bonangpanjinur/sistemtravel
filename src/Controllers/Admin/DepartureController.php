<?php
namespace UmhMgmt\Controllers\Admin;

use UmhMgmt\Utils\View;
use UmhMgmt\Repositories\OperationalRepository;
use UmhMgmt\Repositories\PackageRepository;
use UmhMgmt\Repositories\MasterDataRepository; // Added for Muthawif & Bus

class DepartureController {
    private $repo;
    private $packageRepo;
    private $masterRepo;

    public function __construct() {
        $this->repo = new OperationalRepository();
        $this->packageRepo = new PackageRepository();
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
        check_admin_referer('umh_departure_nonce');
        if (!current_user_can('manage_options')) wp_die('Unauthorized');

        global $wpdb;
        $table = $wpdb->prefix . 'umh_departures';

        $data = [
            'package_id' => absint($_POST['package_id']),
            'departure_date' => sanitize_text_field($_POST['departure_date']),
            'total_seats' => absint($_POST['total_seats']),
            'status' => sanitize_text_field($_POST['status']),
            'muthawif_id' => !empty($_POST['muthawif_id']) ? absint($_POST['muthawif_id']) : null,
            'bus_provider_id' => !empty($_POST['bus_provider_id']) ? absint($_POST['bus_provider_id']) : null,
        ];

        // Logic sederhana: Jika baru, available = total. Jika edit, hitung ulang (nanti bisa dipercanggih)
        if (empty($_POST['id'])) {
            $data['available_seats'] = $data['total_seats'];
            $wpdb->insert($table, $data);
        } else {
            $id = absint($_POST['id']);
            // Jangan reset available seats sembarangan saat edit, kecuali logic khusus
            // Di sini kita update data umum saja
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
        
        // Cek apakah ada booking terkait? Jika ada, block delete (Best Practice)
        $booking_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}umh_bookings WHERE departure_id = %d", $id));
        
        if ($booking_count > 0) {
            wp_die('Gagal menghapus: Jadwal ini sudah memiliki manifest jamaah. Silakan cancel atau arsipkan saja.');
        }

        $wpdb->delete($wpdb->prefix . 'umh_departures', ['id' => $id]);
        wp_redirect(admin_url('admin.php?page=umh-departures&message=deleted'));
        exit;
    }

    public function render_page() {
        // Ambil data untuk dropdown form
        $departures = $this->repo->getUpcomingDepartures(100); // 100 next departures
        $packages = $this->packageRepo->all();
        $muthawifs = $this->masterRepo->getMuthawifs();
        $buses = $this->masterRepo->getBusProviders();

        View::render('admin/departures', [
            'departures' => $departures,
            'packages' => $packages,
            'muthawifs' => $muthawifs,
            'buses' => $buses
        ]);
    }
}