<?php
namespace UmhMgmt\Controllers\Admin;

use UmhMgmt\Utils\View;
use UmhMgmt\Repositories\OperationalRepository;

class OperationalController {
    private $repo;

    public function __construct() {
        $this->repo = new OperationalRepository();
        add_action('admin_menu', [$this, 'add_submenu_page']);
    }

    public function add_submenu_page() {
        add_submenu_page(
            'umh-dashboard',
            'Operational',
            'Operational',
            'manage_options',
            'umh-operational',
            [$this, 'render_page']
        );
    }

    public function render_page() {
        $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'rooming';
        $data = ['active_tab' => $active_tab];

        if ($active_tab === 'rooming') {
            $data['departures'] = $this->repo->getUpcomingDepartures();
        } elseif ($active_tab === 'logistics') {
            $data['inventory'] = $this->repo->getInventoryItems();
        }

        View::render('admin/operational', $data);
    }
}
