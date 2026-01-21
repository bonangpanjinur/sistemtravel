<?php

namespace App\Controllers\Admin;

use App\Utils\View;
use App\Interfaces\DatabaseInterface;

class AgentsHRController
{
    private $db;

    public function __construct(DatabaseInterface $db)
    {
        $this->db = $db;
    }

    /**
     * Menampilkan halaman utama Agents & HR
     * Memperbaiki error: Call to undefined method App\Controllers\Admin\AgentsHRController::index()
     */
    public function index()
    {
        // Ambil data agent/staff jika diperlukan
        $agents = $this->db->fetchAll('users', ['role' => 'agent']); // Contoh query sederhana
        $employees = $this->db->fetchAll('employees'); // Asumsi tabel employees ada

        View::render('admin/agents-hr', [
            'title' => 'Agents & HR Management',
            'agents' => $agents,
            'employees' => $employees
        ]);
    }

    public function commissions()
    {
        View::render('admin/agents/commissions', [
            'title' => 'Agent Commissions'
        ]);
    }

    public function employees()
    {
        View::render('admin/hr/employee-list', [
            'title' => 'Employee List'
        ]);
    }
}