<?php
namespace UmhMgmt\Controllers\Admin;

use UmhMgmt\Repositories\PackageRepository;
use UmhMgmt\Utils\View;

class PackageController {
    private $repo;

    public function __construct() {
        $this->repo = new PackageRepository();
        add_action('admin_menu', [$this, 'add_submenu_page']);
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

    public function render_packages() {
        $packages = $this->repo->all();
        View::render('admin/packages/list', ['packages' => $packages]);
    }
}
