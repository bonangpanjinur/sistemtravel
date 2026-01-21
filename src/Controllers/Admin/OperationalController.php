<?php
namespace App\Controllers\Admin;

use App\Utils\View;
use App\Repositories\OperationalRepository;

class OperationalController {
    private $repo;

    public function __construct(OperationalRepository $repo) {
        $this->repo = $repo;
    }

    public function index() {
        $tab = $_GET['tab'] ?? 'departures';

        $tabs = [
            ['id' => 'departures', 'label' => 'Keberangkatan', 'url' => admin_url('admin.php?page=travel-sys-ops&tab=departures')],
            ['id' => 'jemaah', 'label' => 'Jamaah', 'url' => admin_url('admin.php?page=travel-sys-ops&tab=jemaah')],
            ['id' => 'manifest', 'label' => 'Manifest', 'url' => admin_url('admin.php?page=travel-sys-ops&tab=manifest')],
            ['id' => 'inventory', 'label' => 'Inventory', 'url' => admin_url('admin.php?page=travel-sys-ops&tab=inventory')],
        ];

        echo '<div class="wrap">';
        echo '<h1>Operasional</h1>';
        View::renderTabs($tabs, $tab);

        switch ($tab) {
            case 'jemaah':
                echo View::render('admin/jemaah/list');
                break;
            case 'manifest':
                echo View::render('admin/manifest/list');
                break;
            case 'inventory':
                echo View::render('admin/inventory/list');
                break;
            case 'departures':
            default:
                echo View::render('admin/departures/list');
                break;
        }
        echo '</div>';
    }
}
