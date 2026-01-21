<?php
// Path: src/Controllers/Admin/BookingController.php

namespace UmrahManagement\Controllers\Admin;

use UmrahManagement\Repositories\BookingRepository;
use UmrahManagement\Utils\View;

class BookingController {
    private $bookingRepo;

    public function __construct(BookingRepository $bookingRepo) {
        $this->bookingRepo = $bookingRepo;
    }

    public function index() {
        // Ambil filter dari URL jika ada
        $filters = [];
        if (isset($_GET['status'])) {
            $filters['status'] = sanitize_text_field($_GET['status']);
        }

        $bookings = $this->bookingRepo->findAllWithDetails(); // Menggunakan method yang sudah ada di repo refactored

        echo View::render('admin/bookings/list', [
            'bookings' => $bookings
        ]);
    }
}