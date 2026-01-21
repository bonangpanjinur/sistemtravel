<?php
// Path: src/Controllers/Admin/CRMController.php

namespace UmrahManagement\Controllers\Admin;

use UmrahManagement\Repositories\CRMRepository;
use UmrahManagement\Utils\View;

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