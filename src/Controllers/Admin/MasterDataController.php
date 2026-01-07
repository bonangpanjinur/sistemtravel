<?php
namespace UmhMgmt\Controllers\Admin;

use UmhMgmt\Utils\View;
use UmhMgmt\Repositories\MasterDataRepository;

class MasterDataController {
    private $repo;

    public function __construct() {
        $this->repo = new MasterDataRepository();
        add_action('admin_menu', [$this, 'add_submenu_page']);
        add_action('admin_post_umh_save_hotel', [$this, 'handle_save_hotel']);
        add_action('admin_post_umh_delete_hotel', [$this, 'handle_delete_hotel']);
        add_action('admin_post_umh_save_airline', [$this, 'handle_save_airline']);
        add_action('admin_post_umh_delete_airline', [$this, 'handle_delete_airline']);
        
        // Fitur Tambahan: Enqueue Media Uploader (WP Core)
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
    }

    public function enqueue_admin_scripts($hook) {
        // Hanya load di halaman Master Data agar ringan
        if (isset($_GET['page']) && $_GET['page'] === 'umh-master') {
            wp_enqueue_media(); 
        }
    }

    public function handle_save_hotel() {
        check_admin_referer('umh_master_nonce');
        if (!current_user_can('manage_options')) wp_die('Unauthorized');

        // Sanitasi Khusus untuk Iframe Google Maps agar aman
        $map_code = '';
        if (isset($_POST['map_embed_code'])) {
            $raw_map = $_POST['map_embed_code'];
            // Hanya izinkan tag iframe dengan atribut tertentu
            $allowed_html = [
                'iframe' => [
                    'src' => [],
                    'width' => [],
                    'height' => [],
                    'style' => [],
                    'allowfullscreen' => [],
                    'loading' => [],
                    'referrerpolicy' => []
                ]
            ];
            $map_code = wp_kses($raw_map, $allowed_html);
        }

        $data = [
            'name' => sanitize_text_field($_POST['name']),
            'location' => sanitize_text_field($_POST['location']),
            'rating' => absint($_POST['rating']),
            'description' => sanitize_textarea_field($_POST['description']),
            'image_url' => esc_url_raw($_POST['image_url']),
            'map_embed_code' => $map_code
        ];

        if (!empty($_POST['id'])) $data['id'] = absint($_POST['id']);

        $this->repo->saveHotel($data);
        wp_redirect(admin_url('admin.php?page=umh-master&tab=hotels&message=saved'));
        exit;
    }

    public function handle_delete_hotel() {
        check_admin_referer('umh_master_nonce');
        if (!current_user_can('manage_options')) wp_die('Unauthorized');

        $id = absint($_GET['id']);
        $this->repo->deleteHotel($id);
        wp_redirect(admin_url('admin.php?page=umh-master&tab=hotels&message=deleted'));
        exit;
    }

    public function handle_save_airline() {
        check_admin_referer('umh_master_nonce');
        if (!current_user_can('manage_options')) wp_die('Unauthorized');

        $data = [
            'name' => sanitize_text_field($_POST['name']),
            'code' => sanitize_text_field($_POST['code']),
        ];
        if (!empty($_POST['id'])) $data['id'] = absint($_POST['id']);

        $this->repo->saveAirline($data);
        wp_redirect(admin_url('admin.php?page=umh-master&tab=airlines&message=saved'));
        exit;
    }

    public function handle_delete_airline() {
        check_admin_referer('umh_master_nonce');
        if (!current_user_can('manage_options')) wp_die('Unauthorized');

        $id = absint($_GET['id']);
        $this->repo->deleteAirline($id);
        wp_redirect(admin_url('admin.php?page=umh-master&tab=airlines&message=deleted'));
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