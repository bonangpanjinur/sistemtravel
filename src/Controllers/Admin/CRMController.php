<?php
// Path: src/Controllers/Admin/CRMController.php
namespace UmrahManagement\Controllers\Admin;
use UmrahManagement\Repositories\CRMRepository;
use UmrahManagement\Utils\View;

class CRMController {
    private $crmRepo;
    public function __construct(CRMRepository $crmRepo) { $this->crmRepo = $crmRepo; }
    public function index() {
        echo View::render('admin/crm', ['jemaah' => $this->crmRepo->getAllJemaah()]);
    }
}