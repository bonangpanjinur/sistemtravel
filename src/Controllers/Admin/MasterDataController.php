<?php
// Folder: src/Controllers/Admin/
// File: MasterDataController.php

namespace UmhMgmt\Controllers\Admin;

use UmhMgmt\Utils\View;
use UmhMgmt\Repositories\MasterDataRepository;
use UmhMgmt\Config\Constants;

class MasterDataController {
    private $repo;

    public function __construct() {
        $this->repo = new MasterDataRepository();
        add_action('admin_menu', [$this, 'add_submenu_page']);
        add_action('admin_post_umh_save_hotel', [$this, 'handle_save_hotel']);
        add_action('admin_post_umh_delete_hotel', [$this, 'handle_delete_hotel']);
        // ... action airlines ...
        
        // UPGRADE: Enqueue WordPress Media Uploader Script
        add_action('admin_enqueue_scripts', [$this, 'enqueue_media_uploader']);
    }

    public function enqueue_media_uploader() {
        if (isset($_GET['page']) && $_GET['page'] === 'umh-master') {
            wp_enqueue_media(); // Wajib untuk fitur upload gambar WP
        }
    }

    public function handle_save_hotel() {
        check_admin_referer('umh_master_nonce');
        if (!current_user_can('manage_options')) wp_die('Unauthorized');

        // UPGRADE: Menangani input baru
        $data = [
            'name' => sanitize_text_field($_POST['name']),
            'location' => sanitize_text_field($_POST['location']),
            'rating' => absint($_POST['rating']),
            'description' => sanitize_textarea_field($_POST['description']),
            'image_url' => esc_url_raw($_POST['image_url']),
            // Izinkan tag iframe untuk Google Maps
            'map_embed_code' => wp_kses_post($_POST['map_embed_code']), 
        ];
        
        if (!empty($_POST['id'])) $data['id'] = absint($_POST['id']);

        $this->repo->saveHotel($data);
        wp_redirect(admin_url('admin.php?page=umh-master&tab=hotels'));
        exit;
    }

    // ... (Sisa fungsi delete dan airline sama seperti sebelumnya) ...
    
    public function handle_delete_hotel() {
        check_admin_referer('umh_master_nonce');
        if (!current_user_can('manage_options')) wp_die('Unauthorized');
        $id = absint($_GET['id']);
        $this->repo->deleteHotel($id);
        wp_redirect(admin_url('admin.php?page=umh-master&tab=hotels'));
        exit;
    }
    
    // ... Airline handlers ...
    public function handle_save_airline() {
         check_admin_referer('umh_master_nonce');
         // ... existing code ...
         $data = [
            'name' => sanitize_text_field($_POST['name']),
            'code' => sanitize_text_field($_POST['code']),
        ];
        if (!empty($_POST['id'])) $data['id'] = absint($_POST['id']);

        $this->repo->saveAirline($data);
        wp_redirect(admin_url('admin.php?page=umh-master&tab=airlines'));
        exit;
    }
    
     public function handle_delete_airline() {
        check_admin_referer('umh_master_nonce');
        // ... existing code ...
        $id = absint($_GET['id']);
        $this->repo->deleteAirline($id);
        wp_redirect(admin_url('admin.php?page=umh-master&tab=airlines'));
        exit;
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

        if ($active_tab === 'hotels') {
            $data['hotels'] = $this->repo->getHotels();
        } elseif ($active_tab === 'airlines') {
            $data['airlines'] = $this->repo->getAirlines();
        }

        View::render('admin/master-data', $data);
    }
}