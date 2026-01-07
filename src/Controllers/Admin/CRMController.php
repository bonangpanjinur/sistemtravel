<?php
namespace UmhMgmt\Controllers\Admin;

use UmhMgmt\Utils\View;
use UmhMgmt\Repositories\CRMRepository;

class CRMController {
    private $repo;

    public function __construct() {
        $this->repo = new CRMRepository();
        add_action('admin_menu', [$this, 'add_submenu_page']);
    }

    public function add_submenu_page() {
        add_submenu_page(
            'umh-dashboard',
            'CRM & Leads',
            'CRM & Leads',
            'manage_options',
            'umh-crm',
            [$this, 'render_page']
        );
    }

    public function render_page() {
        $data = [
            'leads' => $this->repo->getLeads()
        ];
        View::render('admin/crm', $data);
    }
}
