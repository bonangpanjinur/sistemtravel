<?php
// Path: src/Controllers/Admin/MasterDataController.php

namespace UmrahManagement\Controllers\Admin;

use UmrahManagement\Repositories\MasterDataRepository;
use UmrahManagement\Utils\View;

class MasterDataController {
    private $masterRepo;

    public function __construct(MasterDataRepository $masterRepo) {
        $this->masterRepo = $masterRepo;
    }

    public function index() {
        echo View::render('admin/master-data', [
            'hotels' => $this->masterRepo->getHotels(),
            'airlines' => $this->masterRepo->getAirlines(),
            'airports' => $this->masterRepo->getAirports(),
            'muthawifs' => $this->masterRepo->getMuthawifs()
        ]);
    }
}