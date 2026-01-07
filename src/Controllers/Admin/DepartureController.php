<?php
namespace UmhMgmt\Controllers\Admin;

use UmhMgmt\Utils\View;
use UmhMgmt\Repositories\OperationalRepository;
use UmhMgmt\Repositories\PackageRepository;

class DepartureController {
    private $repo;
    private $packageRepo;

    public function __construct() {
        $this->repo = new OperationalRepository();
        $this->packageRepo = new PackageRepository();
        add_action('admin_menu', [$this, 'add_submenu_page']);
        add_action('admin_post_umh_save_departure', [$this, 'handle_save_departure']);
    }

    public function add_submenu_page() {
        add_submenu_page(
            'umh-dashboard',
            'Departures',
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
        $data = [
            'package_id' => absint($_POST['package_id']),
            'departure_date' => sanitize_text_field($_POST['departure_date']),
            'total_seats' => absint($_POST['total_seats']),
            'available_seats' => absint($_POST['total_seats']),
            'status' => 'open',
        ];

        $wpdb->insert($wpdb->prefix . 'umh_departures', $data);
        wp_redirect(admin_url('admin.php?page=umh-departures'));
        exit;
    }

    public function render_page() {
        $departures = $this->repo->getUpcomingDepartures(100);
        $packages = $this->packageRepo->all();
        View::render('admin/departures', [
            'departures' => $departures,
            'packages' => $packages
        ]);
    }
}
