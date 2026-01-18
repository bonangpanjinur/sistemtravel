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

        // Shortcode
        add_shortcode('umh_booking_form', [$this, 'render_booking_form']);

        // Handler Submit
        add_action('admin_post_umh_submit_booking', [$this, 'handle_submit_booking']);
        add_action('admin_post_nopriv_umh_submit_booking', [$this, 'handle_submit_booking']); // Allow guest submission
    }

    public function render_booking_form($atts) {
        $atts = shortcode_atts(['departure_id' => 0], $atts);
        $departureId = intval($atts['departure_id']);

        if (!$departureId && isset($_GET['departure_id'])) {
            $departureId = intval($_GET['departure_id']);
        }

        if (!$departureId) return "<p class='umh-error'>Jadwal keberangkatan tidak dipilih.</p>";

        global $wpdb;
        $package = $wpdb->get_row($wpdb->prepare(
            "SELECT p.name, p.id, d.departure_date, d.available_seats 
             FROM {$wpdb->prefix}umh_departures d
             JOIN {$wpdb->prefix}umh_packages p ON d.package_id = p.id
             WHERE d.id = %d", 
            $departureId
        ));

        if (!$package) return "<p class='umh-error'>Paket tidak ditemukan.</p>";

        $pricing = $this->packageRepo->getPricing($package->id);
        $addons = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}umh_service_catalog WHERE is_active = 1");

        ob_start();
        View::render('frontend/booking-form', [
            'departure_id' => $departureId,
            'package' => $package,
            'pricing' => $pricing,
            'addons' => $addons,
            'user_logged_in' => is_user_logged_in(),
            'current_user' => wp_get_current_user()
        ]);
        return ob_get_clean();
    }

    public function handle_submit_booking() {
        if (!isset($_POST['umh_booking_nonce']) || !wp_verify_nonce($_POST['umh_booking_nonce'], 'submit_booking')) {
            wp_die('Security check failed');
        }

        try {
            $customerUserId = get_current_user_id();

            // --- [FEATURE UPGRADE] Guest Auto-Registration ---
            if ($customerUserId == 0) {
                $email = sanitize_email($_POST['contact_email']);
                $fullName = sanitize_text_field($_POST['contact_name']);
                $phone = sanitize_text_field($_POST['contact_phone']);

                if (!is_email($email)) throw new Exception("Email kontak tidak valid.");

                // Cek apakah email sudah ada
                if (email_exists($email)) {
                    // Opsional: Paksa login atau attach ke user existing (disini kita throw error demi keamanan)
                    throw new Exception("Email sudah terdaftar. Silakan login terlebih dahulu.");
                }

                // Buat User Baru
                $password = wp_generate_password(12, false);
                $userdata = [
                    'user_login' => $email,
                    'user_email' => $email,
                    'user_pass'  => $password,
                    'display_name' => $fullName,
                    'first_name' => $fullName,
                    'role'       => 'umh_jemaah' // Pastikan role ini ada
                ];

                $customerUserId = wp_insert_user($userdata);

                if (is_wp_error($customerUserId)) {
                    throw new Exception("Gagal membuat akun: " . $customerUserId->get_error_message());
                }

                // Simpan No HP ke User Meta
                update_user_meta($customerUserId, 'phone_number', $phone);

                // Auto Login user baru
                wp_set_current_user($customerUserId);
                wp_set_auth_cookie($customerUserId);
                
                // TODO: Kirim email notifikasi username/password ke user baru
            }
            // ------------------------------------------------

            // 1. Persiapkan Data Booking
            $bookingData = [
                'departure_id' => intval($_POST['departure_id']),
                'customer_user_id' => $customerUserId,
                'branch_id' => 1, // Default pusat
                'room_type' => sanitize_text_field($_POST['room_type']),
                'coupon_code' => sanitize_text_field($_POST['coupon_code'] ?? ''),
                'addons' => isset($_POST['addons']) ? array_map('intval', $_POST['addons']) : [],
                'passengers' => []
            ];

            // 2. Parse Passengers
            if (isset($_POST['pax_name']) && is_array($_POST['pax_name'])) {
                for ($i = 0; $i < count($_POST['pax_name']); $i++) {
                    if(empty($_POST['pax_name'][$i])) continue;
                    
                    $bookingData['passengers'][] = [
                        'name' => sanitize_text_field($_POST['pax_name'][$i]),
                        'pax_type' => sanitize_text_field($_POST['pax_type'][$i]),
                        'passport_number' => sanitize_text_field($_POST['pax_passport'][$i] ?? ''),
                        'passport_expiry' => sanitize_text_field($_POST['pax_expiry'][$i] ?? '')
                    ];
                }
            }

            if (empty($bookingData['passengers'])) {
                throw new Exception("Data jamaah tidak boleh kosong.");
            }

            // 3. Create Booking
            $bookingId = $this->bookingService->createBooking($bookingData);

            // 4. Redirect ke Halaman Invoice/Success
            $url = admin_url('admin-post.php?action=umh_print_invoice&booking_id=' . $bookingId);
            wp_redirect($url);
            exit;

        } catch (Exception $e) {
            wp_die("Terjadi Kesalahan: " . $e->getMessage() . "<br><a href='javascript:history.back()'>Kembali</a>");
        }
    }
}