<?php
// Path: src/Controllers/Admin/FinanceController.php
namespace UmrahManagement\Controllers\Admin;
use UmrahManagement\Repositories\FinanceRepository;
use UmrahManagement\Utils\View;

class FinanceController {
    private $financeRepo;
    public function __construct(FinanceRepository $financeRepo) { $this->financeRepo = $financeRepo; }
    public function index() {
        echo View::render('admin/finance', ['transactions' => $this->financeRepo->getRecentTransactions()]);
    }
}