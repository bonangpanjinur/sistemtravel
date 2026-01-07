<?php
// Folder: src/Controllers/Admin/
// File: MasterDataController.php

namespace UmhMgmt\Controllers\Admin;

use UmhMgmt\Utils\View;
use UmhMgmt\Repositories\MasterDataRepository;

class MasterDataController {
    private $repo;

    public function __construct() {
        $this->repo = new MasterDataRepository();
        add_action('admin_menu', [$this, 'add_submenu_page']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);

        // Hotel & Airline Handlers (Existing)
        add_action('admin_post_umh_save_hotel', [$this, 'handle_save_hotel']);
        add_action('admin_post_umh_delete_hotel', [$this, 'handle_delete_hotel']);
        add_action('admin_post_umh_save_airline', [$this, 'handle_save_airline']);
        add_action('admin_post_umh_delete_airline', [$this, 'handle_delete_airline']);

        // [NEW] Handlers
        add_action('admin_post_umh_save_muthawif', [$this, 'handle_save_muthawif']);
        add_action('admin_post_umh_delete_muthawif', [$this, 'handle_delete_muthawif']);
        
        add_action('admin_post_umh_save_bus', [$this, 'handle_save_bus']);
        add_action('admin_post_umh_delete_bus', [$this, 'handle_delete_bus']);

        add_action('admin_post_umh_save_airport', [$this, 'handle_save_airport']);
        add_action('admin_post_umh_delete_airport', [$this, 'handle_delete_airport']);
    }

    public function enqueue_admin_scripts($hook) {
        if (isset($_GET['page']) && $_GET['page'] === 'umh-master') {
            wp_enqueue_media(); 
        }
    }

    public function add_submenu_page() {
        add_submenu_page(
            'umh-dashboard',
            'Master Data',
            'Master Data',
            'manage_options',
            'umh-master',
            [$this, 'render_page']
        );
    }

    public function render_page() {
        $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'hotels';
        $data = ['active_tab' => $active_tab];

        // Load data based on tab to optimize performance
        switch ($active_tab) {
            case 'hotels':
                $data['hotels'] = $this->repo->getHotels();
                break;
            case 'airlines':
                $data['airlines'] = $this->repo->getAirlines();
                break;
            case 'muthawifs':
                $data['muthawifs'] = $this->repo->getMuthawifs();
                break;
            case 'bus':
                $data['bus_providers'] = $this->repo->getBusProviders();
                break;
            case 'airports':
                $data['airports'] = $this->repo->getAirports();
                break;
        }

        View::render('admin/master-data', $data);
    }

    // --- HANDLERS (Simpan & Hapus) ---

    // ... (Hotel & Airline Handlers sama seperti sebelumnya) ...
    public function handle_save_hotel() { /* ... kode lama ... */ 
        check_admin_referer('umh_master_nonce');
        // ... (copy logic from previous step)
        // Shortened for brevity, please keep your existing hotel logic
        $data = [
            'name' => sanitize_text_field($_POST['name']),
            'location' => sanitize_text_field($_POST['location']),
            'rating' => absint($_POST['rating']),
            'description' => sanitize_textarea_field($_POST['description']),
            'image_url' => esc_url_raw($_POST['image_url']),
            'map_embed_code' => wp_kses_post($_POST['map_embed_code'])
        ];
        if (!empty($_POST['id'])) $data['id'] = absint($_POST['id']);
        $this->repo->saveHotel($data);
        wp_redirect(admin_url('admin.php?page=umh-master&tab=hotels'));
        exit;
    }
    public function handle_delete_hotel() {
        check_admin_referer('umh_master_nonce');
        $this->repo->deleteHotel(absint($_GET['id']));
        wp_redirect(admin_url('admin.php?page=umh-master&tab=hotels'));
        exit;
    }
    
    public function handle_save_airline() {
        check_admin_referer('umh_master_nonce');
        $data = ['name' => sanitize_text_field($_POST['name']), 'code' => sanitize_text_field($_POST['code'])];
        if (!empty($_POST['id'])) $data['id'] = absint($_POST['id']);
        $this->repo->saveAirline($data);
        wp_redirect(admin_url('admin.php?page=umh-master&tab=airlines'));
        exit;
    }
    public function handle_delete_airline() {
        check_admin_referer('umh_master_nonce');
        $this->repo->deleteAirline(absint($_GET['id']));
        wp_redirect(admin_url('admin.php?page=umh-master&tab=airlines'));
        exit;
    }

    // [NEW] Muthawif Handler
    public function handle_save_muthawif() {
        check_admin_referer('umh_master_nonce');
        if (!current_user_can('manage_options')) wp_die('Unauthorized');

        $data = [
            'name' => sanitize_text_field($_POST['name']),
            'phone_saudi' => sanitize_text_field($_POST['phone_saudi']),
            'phone_indo' => sanitize_text_field($_POST['phone_indo']),
            'certification' => sanitize_text_field($_POST['certification']),
        ];
        if (!empty($_POST['id'])) $data['id'] = absint($_POST['id']);

        $this->repo->saveMuthawif($data);
        wp_redirect(admin_url('admin.php?page=umh-master&tab=muthawifs&message=saved'));
        exit;
    }

    public function handle_delete_muthawif() {
        check_admin_referer('umh_master_nonce');
        if (!current_user_can('manage_options')) wp_die('Unauthorized');
        $this->repo->deleteMuthawif(absint($_GET['id']));
        wp_redirect(admin_url('admin.php?page=umh-master&tab=muthawifs&message=deleted'));
        exit;
    }

    // [NEW] Bus Handler
    public function handle_save_bus() {
        check_admin_referer('umh_master_nonce');
        if (!current_user_can('manage_options')) wp_die('Unauthorized');

        $data = [
            'company_name' => sanitize_text_field($_POST['company_name']),
            'bus_type' => sanitize_text_field($_POST['bus_type']),
            'seat_capacity' => absint($_POST['seat_capacity']),
            'contact_person' => sanitize_text_field($_POST['contact_person']),
        ];
        if (!empty($_POST['id'])) $data['id'] = absint($_POST['id']);

        $this->repo->saveBusProvider($data);
        wp_redirect(admin_url('admin.php?page=umh-master&tab=bus&message=saved'));
        exit;
    }

    public function handle_delete_bus() {
        check_admin_referer('umh_master_nonce');
        $this->repo->deleteBusProvider(absint($_GET['id']));
        wp_redirect(admin_url('admin.php?page=umh-master&tab=bus&message=deleted'));
        exit;
    }

    // [NEW] Airport Handler
    public function handle_save_airport() {
        check_admin_referer('umh_master_nonce');
        if (!current_user_can('manage_options')) wp_die('Unauthorized');

        $data = [
            'iata_code' => strtoupper(sanitize_text_field($_POST['iata_code'])),
            'airport_name' => sanitize_text_field($_POST['airport_name']),
            'city' => sanitize_text_field($_POST['city']),
        ];
        if (!empty($_POST['id'])) $data['id'] = absint($_POST['id']);

        $this->repo->saveAirport($data);
        wp_redirect(admin_url('admin.php?page=umh-master&tab=airports&message=saved'));
        exit;
    }

    public function handle_delete_airport() {
        check_admin_referer('umh_master_nonce');
        $this->repo->deleteAirport(absint($_GET['id']));
        wp_redirect(admin_url('admin.php?page=umh-master&tab=airports&message=deleted'));
        exit;
    }
}