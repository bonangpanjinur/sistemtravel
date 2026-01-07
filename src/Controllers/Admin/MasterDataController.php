<?php
namespace UmhMgmt\Controllers\Admin;

use UmhMgmt\Utils\View;
use UmhMgmt\Repositories\MasterDataRepository;

class MasterDataController {
    private $repo;

    public function __construct() {
        $this->repo = new MasterDataRepository();
        add_action('admin_menu', [$this, 'add_submenu_page']);
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
