<?php
namespace UmhMgmt\Controllers\Admin;

use UmhMgmt\Repositories\PackageRepository;
use UmhMgmt\Utils\View;

class PackageController {
    private $repo;

    public function __construct() {
        $this->repo = new PackageRepository();
        add_action('admin_menu', [$this, 'add_submenu_page']);
        add_action('admin_post_umh_save_package', [$this, 'handle_save_package']);
        add_action('admin_post_umh_delete_package', [$this, 'handle_delete_package']);
    }

    public function add_submenu_page() {
        add_submenu_page(
            'umh-dashboard',
            'Manage Packages',
            'Packages',
            'manage_options',
            'umh-packages',
            [$this, 'render_packages']
        );
    }

    public function handle_save_package() {
        check_admin_referer('umh_package_nonce');
        if (!current_user_can('manage_options')) wp_die('Unauthorized');

        $data = [
            'name' => sanitize_text_field($_POST['name']),
            'description' => sanitize_textarea_field($_POST['description']),
            'base_price' => floatval($_POST['base_price']),
            'duration_days' => absint($_POST['duration_days']),
        ];
        if (!empty($_POST['id'])) $data['id'] = absint($_POST['id']);

        $this->repo->save($data);
        wp_redirect(admin_url('admin.php?page=umh-packages'));
        exit;
    }

    public function handle_delete_package() {
        check_admin_referer('umh_package_nonce');
        if (!current_user_can('manage_options')) wp_die('Unauthorized');

        $id = absint($_GET['id']);
        $this->repo->delete($id);
        wp_redirect(admin_url('admin.php?page=umh-packages'));
        exit;
    }

    public function render_packages() {
        $packages = $this->repo->all();
        View::render('admin/packages/list', ['packages' => $packages]);
    }
}
