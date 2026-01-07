<?php
// File: BookingFormController.php
// Location: src/Controllers/Frontend/BookingFormController.php

namespace UmhMgmt\Controllers\Frontend;

use UmhMgmt\Services\BookingService;
use UmhMgmt\Repositories\BookingRepository;
use UmhMgmt\Utils\View;

class BookingFormController {
    private $service;

    public function __construct() {
        $this->service = new BookingService(new BookingRepository());
        add_shortcode('umh_booking_form', [$this, 'render_form']);
        add_action('admin_post_nopriv_umh_submit_booking', [$this, 'handle_form_submission']);
        add_action('admin_post_umh_submit_booking', [$this, 'handle_form_submission']);
    }

    public function render_form($atts) {
        // [NEW] Ambil parameter dari URL untuk pre-fill form
        $prefill = [
            'departure_id' => isset($_GET['departure_id']) ? absint($_GET['departure_id']) : '',
            'package_id'   => isset($_GET['package_id']) ? absint($_GET['package_id']) : '',
            'room_type'    => isset($_GET['room_type']) ? sanitize_text_field($_GET['room_type']) : 'quad',
        ];

        ob_start();
        // Kirim data prefill ke view
        View::render('frontend/booking-form', ['atts' => $atts, 'prefill' => $prefill]);
        return ob_get_clean();
    }

    public function handle_form_submission() {
        if (!isset($_POST['umh_booking_nonce']) || !wp_verify_nonce($_POST['umh_booking_nonce'], 'umh_booking_nonce')) {
            wp_die('Security check failed');
        }

        // ... (Kode submission tetap sama, hanya pastikan validasi) ...
        // Pastikan user login jika ingin booking tersimpan ke akun mereka
        $user_id = get_current_user_id();

        $sanitized_data = [
            'departure_id' => isset($_POST['departure_id']) ? absint($_POST['departure_id']) : 0,
            'customer_user_id' => $user_id, // Link ke user yang login
            'passengers' => []
        ];

        if (isset($_POST['passengers']) && is_array($_POST['passengers'])) {
            foreach ($_POST['passengers'] as $passenger) {
                $sanitized_data['passengers'][] = [
                    'name' => isset($passenger['name']) ? sanitize_text_field($passenger['name']) : '',
                    'passport_number' => isset($passenger['passport_number']) ? sanitize_text_field($passenger['passport_number']) : '',
                    'passport_expiry' => isset($passenger['passport_expiry']) ? sanitize_text_field($passenger['passport_expiry']) : '',
                ];
            }
        }
        
        // Hitung total harga berdasarkan room type (Logic ini harus ada di Service sebenarnya)
        // Untuk sekarang kita set dummy atau ambil dari hidden field (kurang aman)
        // Idealnya: Ambil harga paket dari DB berdasarkan departure_id -> package_id -> room type
        $sanitized_data['total_price'] = 0; // Nanti dihitung di service

        try {
            $booking_id = $this->service->createBooking($sanitized_data);
            
            // Redirect ke Dashboard Jemaah atau Halaman Sukses
            $redirect_url = home_url('/member-area?status=success'); 
            wp_redirect($redirect_url);
            exit;
        } catch (\Exception $e) {
            wp_die($e->getMessage());
        }
    }
}