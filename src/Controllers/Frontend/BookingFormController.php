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
        add_action('admin_post_umh_submit_booking_ajax', [$this, 'handle_ajax_submission']); // Ubah ke AJAX handle
        add_action('wp_ajax_umh_check_coupon', [$this, 'check_coupon_ajax']); // Endpoint cek kupon
        add_action('wp_ajax_nopriv_umh_check_coupon', [$this, 'check_coupon_ajax']);
    }

    public function render_form($atts) {
        $prefill = [
            'departure_id' => isset($_GET['departure_id']) ? absint($_GET['departure_id']) : '',
            'package_id'   => isset($_GET['package_id']) ? absint($_GET['package_id']) : '',
            'room_type'    => isset($_GET['room_type']) ? sanitize_text_field($_GET['room_type']) : 'quad',
        ];

        // Ambil data harga paket untuk ditampilkan di JS (Estimasi)
        // Logic ini bisa dipindah ke View atau diload via AJAX
        $pricing_data = []; 
        if($prefill['package_id']) {
            $repo = new \UmhMgmt\Repositories\PackageRepository();
            $pricing_data = $repo->getPricing($prefill['package_id']);
        }

        ob_start();
        View::render('frontend/booking-form', ['atts' => $atts, 'prefill' => $prefill, 'pricing_data' => $pricing_data]);
        return ob_get_clean();
    }

    public function handle_ajax_submission() {
        // if (!isset($_POST['umh_booking_nonce']) || !wp_verify_nonce($_POST['umh_booking_nonce'], 'umh_booking_nonce')) {
        //     wp_send_json_error(['message' => 'Security check failed']);
        // }

        $user_id = get_current_user_id();
        if(!$user_id) {
             // Opsional: Auto-register user baru
             // Untuk sekarang wajib login
             wp_send_json_error(['message' => 'Silakan login terlebih dahulu.']);
        }

        $sanitized_data = [
            'departure_id' => isset($_POST['departure_id']) ? absint($_POST['departure_id']) : 0,
            'customer_user_id' => $user_id,
            'room_type' => sanitize_text_field($_POST['room_type']), // quad, triple, double
            'coupon_code' => sanitize_text_field($_POST['coupon_code']),
            'passengers' => []
        ];

        if (isset($_POST['passengers']) && is_array($_POST['passengers'])) {
            foreach ($_POST['passengers'] as $passenger) {
                $sanitized_data['passengers'][] = [
                    'name' => sanitize_text_field($passenger['name']),
                    'pax_type' => sanitize_text_field($passenger['pax_type']), // adult, child, infant
                    'passport_number' => sanitize_text_field($passenger['passport_number']),
                    'passport_expiry' => sanitize_text_field($passenger['passport_expiry']),
                ];
            }
        }

        try {
            $booking_id = $this->service->createBooking($sanitized_data);
            wp_send_json_success([
                'message' => 'Booking berhasil dibuat! ID #' . $booking_id,
                'redirect_url' => home_url('/member-area?booking_created=' . $booking_id)
            ]);
        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    public function check_coupon_ajax() {
        $code = sanitize_text_field($_POST['code']);
        global $wpdb;
        
        $coupon = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}umh_coupons WHERE code = %s AND (expiry_date >= CURDATE() OR expiry_date IS NULL)", 
            $code
        ));

        if ($coupon) {
            if ($coupon->usage_limit > 0 && $coupon->used_count >= $coupon->usage_limit) {
                wp_send_json_error(['message' => 'Kupon sudah habis digunakan.']);
            } else {
                wp_send_json_success([
                    'type' => $coupon->discount_type, // percent or fixed
                    'amount' => floatval($coupon->amount),
                    'message' => 'Kupon valid!'
                ]);
            }
        } else {
            wp_send_json_error(['message' => 'Kode kupon tidak valid.']);
        }
    }
}