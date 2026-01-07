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
    }

    public function handle_save_hotel() {
        check_admin_referer('umh_master_nonce');
        if (!current_user_can('manage_options')) wp_die('Unauthorized');

        $data = [
            'name' => sanitize_text_field($_POST['name']),
            'location' => sanitize_text_field($_POST['location']),
            'rating' => absint($_POST['rating']),
        ];
        if (!empty($_POST['id'])) $data['id'] = absint($_POST['id']);

        $this->repo->saveHotel($data);
        wp_redirect(admin_url('admin.php?page=umh-master&tab=hotels'));
        exit;
    }

    public function handle_delete_hotel() {
        check_admin_referer('umh_master_nonce');
        if (!current_user_can('manage_options')) wp_die('Unauthorized');

        $id = absint($_GET['id']);
        $this->repo->deleteHotel($id);
        wp_redirect(admin_url('admin.php?page=umh-master&tab=hotels'));
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
        wp_redirect(admin_url('admin.php?page=umh-master&tab=airlines'));
        exit;
    }

    public function handle_delete_airline() {
        check_admin_referer('umh_master_nonce');
        if (!current_user_can('manage_options')) wp_die('Unauthorized');

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
