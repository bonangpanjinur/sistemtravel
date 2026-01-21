<?php
// Path: src/Controllers/Admin/CRMController.php

namespace App\Controllers\Admin;

use App\Repositories\CRMRepository;
use App\Utils\View;

class CRMController {
    private $crmRepo;

    public function __construct(CRMRepository $crmRepo) {
        $this->crmRepo = $crmRepo;
    }

    public function index() {
        // Mengambil data jemaah
        $jemaah = $this->crmRepo->getAllJemaah();
        
        echo View::render('admin/crm', [
            'jemaah' => $jemaah
        ]);
    }
}