<?php

namespace App\Controllers\Admin;

use App\Utils\View;
use App\Interfaces\DatabaseInterface;

class DepartureController
{
    private $db;

    public function __construct(DatabaseInterface $db)
    {
        $this->db = $db;
    }

    public function index()
    {
        // Fetch departures logic here
        $departures = []; 

        // Fix: Changed view path from 'admin/departures/list' to 'admin/departures'
        // karena file yang ada adalah templates/admin/departures.php
        View::render('admin/departures', [
            'title' => 'Departures Management',
            'departures' => $departures
        ]);
    }
}