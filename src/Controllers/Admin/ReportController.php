<?php

namespace App\Controllers\Admin;

use App\Utils\View;

class ReportController
{
    public function index()
    {
        // Fixes error: Call to undefined method App\Controllers\Admin\ReportController::index()
        // Mengarahkan ke template laporan keuangan sebagai default
        View::render('admin/reports/financial-report', [
            'title' => 'Financial Reports'
        ]);
    }
}