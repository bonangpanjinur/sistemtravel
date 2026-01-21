<?php
// Path: src/Controllers/Admin/FinanceController.php

namespace App\Controllers\Admin;

use App\Repositories\FinanceRepository;
use App\Utils\View;

class FinanceController {
    private $financeRepo;

    public function __construct(FinanceRepository $financeRepo) {
        $this->financeRepo = $financeRepo;
    }

    public function index() {
        $tab = $_GET['tab'] ?? 'reports';

        $tabs = [
            ['id' => 'reports', 'label' => 'Laporan', 'url' => admin_url('admin.php?page=travel-sys-finance-group&tab=reports')],
            ['id' => 'invoices', 'label' => 'Tagihan', 'url' => admin_url('admin.php?page=travel-sys-finance-group&tab=invoices')],
            ['id' => 'savings', 'label' => 'Tabungan', 'url' => admin_url('admin.php?page=travel-sys-finance-group&tab=savings')],
            ['id' => 'commissions', 'label' => 'Komisi', 'url' => admin_url('admin.php?page=travel-sys-finance-group&tab=commissions')],
        ];

        echo '<div class="wrap">';
        echo '<h1>Keuangan</h1>';
        View::renderTabs($tabs, $tab);

        switch ($tab) {
            case 'invoices':
                echo View::render('admin/finance/invoices');
                break;
            case 'savings':
                echo View::render('admin/savings/list');
                break;
            case 'commissions':
                echo View::render('admin/finance/commissions');
                break;
            case 'reports':
            default:
                $transactions = $this->financeRepo->getRecentTransactions();
                echo View::render('admin/finance/reports', [
                    'transactions' => $transactions
                ]);
                break;
        }
        echo '</div>';
    }
}
