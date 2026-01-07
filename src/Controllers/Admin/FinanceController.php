<?php
namespace UmhMgmt\Controllers\Admin;

use UmhMgmt\Utils\View;
use UmhMgmt\Repositories\FinanceRepository;

class FinanceController {
    private $repo;

    public function __construct() {
        $this->repo = new FinanceRepository();
        add_action('admin_menu', [$this, 'add_submenu_page']);
    }

    public function add_submenu_page() {
        add_submenu_page(
            'umh-dashboard',
            'Finance',
            'Finance',
            'manage_options',
            'umh-finance',
            [$this, 'render_page']
        );
    }

    public function render_page() {
        $data = [
            'pending_payments' => $this->repo->getPendingPayments()
        ];
        View::render('admin/finance', $data);
    }
}
