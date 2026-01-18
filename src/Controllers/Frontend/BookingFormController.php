<?php
// File: src/Controllers/Frontend/BookingFormController.php

namespace UmhMgmt\Controllers\Frontend;

use UmhMgmt\Services\BookingService;
use UmhMgmt\Repositories\BookingRepository;
use UmhMgmt\Repositories\PackageRepository;
use UmhMgmt\Utils\View;
use Exception;

class BookingFormController {
    private $bookingService;
    private $packageRepo;

    public function __construct() {
        $this->bookingService = new BookingService(new BookingRepository());
        $this->packageRepo = new PackageRepository();

        // Shortcode untuk menampilkan form di halaman depan
        add_shortcode('umh_booking_form', [$this, 'render_booking_form']);

        // Handler Submit Form
        add_action('admin_post_umh_submit_booking', [$this, 'handle_submit_booking']);
        add_action('admin_post_nopriv_umh_submit_booking', [$this, 'handle_submit_booking']);
    }

    /**
     * Menampilkan Form Booking (Shortcode)
     * Usage: [umh_booking_form departure_id="123"]
     */
    public function render_booking_form($atts) {
        $atts = shortcode_atts(['departure_id' => 0], $atts);
        $departureId = intval($atts['departure_id']);

        if (!$departureId && isset($_GET['departure_id'])) {
            $departureId = intval($_GET['departure_id']);
        }

        if (!$departureId) return "<p class='error'>Jadwal keberangkatan tidak dipilih.</p>";

        // Ambil Data Paket untuk ditampilkan di ringkasan
        global $wpdb;
        $package = $wpdb->get_row($wpdb->prepare(
            "SELECT p.name, p.id, d.departure_date, d.available_seats 
             FROM {$wpdb->prefix}umh_departures d
             JOIN {$wpdb->prefix}umh_packages p ON d.package_id = p.id
             WHERE d.id = %d", 
            $departureId
        ));

        if (!$package) return "<p class='error'>Paket tidak ditemukan.</p>";

        // Ambil Pricing untuk JS Calculator
        $pricing = $this->packageRepo->getPricing($package->id);

        // Ambil Katalog Layanan Tambahan (Add-ons)
        $addons = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}umh_service_catalog WHERE is_active = 1");

        ob_start();
        View::render('frontend/booking-form', [
            'departure_id' => $departureId,
            'package' => $package,
            'pricing' => $pricing,
            'addons' => $addons,
            'user_logged_in' => is_user_logged_in()
        ]);
        return ob_get_clean();
    }

    /**
     * Handle POST Submission
     */
    public function handle_submit_booking() {
        if (!isset($_POST['umh_booking_nonce']) || !wp_verify_nonce($_POST['umh_booking_nonce'], 'submit_booking')) {
            wp_die('Security check failed');
        }

        try {
            // 1. Persiapkan Data
            $bookingData = [
                'departure_id' => intval($_POST['departure_id']),
                'customer_user_id' => get_current_user_id(), // 0 jika guest (perlu logic registrasi guest idealnya)
                'branch_id' => 1, // Default branch pusat dulu
                'room_type' => sanitize_text_field($_POST['room_type']),
                'coupon_code' => sanitize_text_field($_POST['coupon_code'] ?? ''),
                'addons' => isset($_POST['addons']) ? array_map('intval', $_POST['addons']) : [],
                'passengers' => []
            ];

            // 2. Parse Passengers
            if (isset($_POST['pax_name']) && is_array($_POST['pax_name'])) {
                for ($i = 0; $i < count($_POST['pax_name']); $i++) {
                    $bookingData['passengers'][] = [
                        'name' => sanitize_text_field($_POST['pax_name'][$i]),
                        'pax_type' => sanitize_text_field($_POST['pax_type'][$i]), // adult, child, infant
                        'passport_number' => sanitize_text_field($_POST['pax_passport'][$i] ?? ''),
                        'passport_expiry' => sanitize_text_field($_POST['pax_expiry'][$i] ?? '')
                    ];
                }
            }

            if (empty($bookingData['passengers'])) {
                throw new Exception("Data penumpang tidak boleh kosong.");
            }

            // 3. Jika user belum login, idealnya buat user baru disini atau paksa login
            if ($bookingData['customer_user_id'] == 0) {
                 // Sederhana: Redirect login dulu
                 wp_redirect(wp_login_url(add_query_arg($_POST, wp_get_referer())));
                 exit;
            }

            // 4. Panggil Service
            $bookingId = $this->bookingService->createBooking($bookingData);

            // 5. Redirect ke Invoice / Payment
            $url = admin_url('admin-post.php?action=umh_print_invoice&booking_id=' . $bookingId);
            // Atau halaman terima kasih
            wp_redirect($url);
            exit;

        } catch (Exception $e) {
            wp_die("Terjadi Kesalahan: " . $e->getMessage());
        }
    }
}