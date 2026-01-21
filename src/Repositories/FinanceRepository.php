<?php

namespace App\Controllers\Admin;

use App\Utils\View;
use App\Repositories\FinanceRepository;

class FinanceController
{
    private $repository;

    public function __construct(FinanceRepository $repository)
    {
        $this->repository = $repository;
    }

    public function index()
    {
        $transactions = $this->repository->getRecentTransactions();
        $summary = $this->repository->getSummary();

        // Fix: Changed view path from 'admin/finance/reports' to 'admin/finance'
        // karena file yang ada adalah templates/admin/finance.php
        View::render('admin/finance', [
            'title' => 'Finance Dashboard',
            'transactions' => $transactions,
            'summary' => $summary
        ]);
    }
}