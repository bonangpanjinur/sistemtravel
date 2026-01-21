<?php
// Path: src/Controllers/Admin/BookingController.php

namespace UmrahManagement\Controllers\Admin; // PENTING: Namespace harus ini

use UmrahManagement\Repositories\BookingRepository;
use UmrahManagement\Utils\View;

class BookingController {
    private $bookingRepo;

    public function __construct(BookingRepository $bookingRepo) {
        $this->bookingRepo = $bookingRepo;
    }

    public function index() {
        $filters = [];
        if (isset($_GET['status'])) {
            $filters['status'] = sanitize_text_field($_GET['status']);
        }

        // Mengambil data menggunakan repository yang sudah direfactor
        $bookings = $this->bookingRepo->findAllWithDetails(); 

        echo View::render('admin/bookings/list', [
            'bookings' => $bookings
        ]);
    }
}