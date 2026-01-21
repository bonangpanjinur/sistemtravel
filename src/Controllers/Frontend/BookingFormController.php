<?php
// Path: src/Controllers/Frontend/BookingFormController.php

namespace App\Controllers\Frontend;

use App\Repositories\BookingRepository;
use App\Repositories\PackageRepository;
use App\Interfaces\DatabaseInterface;
use App\Utils\View;
use App\Utils\Validator;
use Exception;

class BookingFormController {
    private $bookingRepository;
    private $packageRepository;
    private $db;

    public function __construct(
        BookingRepository $bookingRepository, 
        PackageRepository $packageRepository,
        DatabaseInterface $db
    ) {
        $this->bookingRepository = $bookingRepository;
        $this->packageRepository = $packageRepository;
        $this->db = $db;
    }

    public function render($atts) {
        // Handle Form Submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['umroh_booking_submit'])) {
            return $this->handleSubmission();
        }

        // Logic Render Form
        $atts = shortcode_atts(['departure_id' => 0], $atts);
        $departureId = intval($atts['departure_id']);

        if (!$departureId && isset($_GET['departure_id'])) {
            $departureId = intval($_GET['departure_id']);
        }

        if (!$departureId) {
            return "<div class='umroh-alert umroh-alert-danger'>Jadwal keberangkatan tidak dipilih.</div>";
        }

        // Fetch Departure & Package Info
        // Menggunakan DB Interface langsung karena logika join spesifik ini belum ada di Repo
        $prefix = $this->db->prefix();
        // Menggunakan nama tabel dengan prefix standar 'umh_'
        $sql = $this->db->prepare(
            "SELECT p.name, p.id as package_id, d.id as departure_id, d.departure_date, d.available_seats, p.hotel_mekkah_id, p.hotel_madinah_id
             FROM {$prefix}umh_departures d
             JOIN {$prefix}umh_packages p ON d.package_id = p.id
             WHERE d.id = %d", 
            $departureId
        );
        $package = $this->db->get_row($sql);

        if (!$package) {
            return "<div class='umroh-alert umroh-alert-danger'>Paket tidak ditemukan.</div>";
        }

        // Fetch Pricing
        $pricing = $this->packageRepository->getPricing($package->package_id);
        
        // Fetch Addons (Service Catalog)
        $addons = $this->db->get_results("SELECT * FROM {$prefix}umh_service_catalog WHERE is_active = 1");

        // Prepare View Data
        $data = [
            'departure_id' => $departureId,
            'package' => $package,
            'pricing' => $pricing,
            'addons' => $addons,
            'user_logged_in' => is_user_logged_in(),
            'current_user' => wp_get_current_user(),
            'input' => [] // For repopulating form on error
        ];

        return View::render('frontend/booking-form', $data);
    }

    private function handleSubmission() {
        // 1. CSRF Protection
        if (!isset($_POST['umh_booking_nonce']) || !wp_verify_nonce($_POST['umh_booking_nonce'], 'submit_booking')) {
            return "<div class='umroh-alert umroh-alert-danger'>Security check failed. Silakan refresh halaman.</div>";
        }

        try {
            $customerUserId = get_current_user_id();
            
            // --- [FEATURE RESTORED] Guest Auto-Registration ---
            if ($customerUserId == 0) {
                 // Validasi Input Guest
                 $guestValidator = Validator::make($_POST)->rules([
                    'contact_name' => 'required|min:3',
                    'contact_email' => 'required|email',
                    'contact_phone' => 'required|numeric|min:10'
                 ]);

                 if ($guestValidator->fails()) {
                     throw new Exception($guestValidator->getFirstError());
                 }

                 $email = sanitize_email($_POST['contact_email']);
                 $fullName = sanitize_text_field($_POST['contact_name']);
                 $phone = sanitize_text_field($_POST['contact_phone']);

                 if (email_exists($email)) {
                     throw new Exception("Email sudah terdaftar. Silakan login terlebih dahulu.");
                 }

                 // Create User
                 $password = wp_generate_password(12, false);
                 $userdata = [
                    'user_login' => $email,
                    'user_email' => $email,
                    'user_pass'  => $password,
                    'display_name' => $fullName,
                    'first_name' => $fullName,
                    'role'       => 'umh_jemaah' // Role khusus jemaah
                 ];

                 $customerUserId = wp_insert_user($userdata);

                 if (is_wp_error($customerUserId)) {
                     throw new Exception("Gagal membuat akun: " . $customerUserId->get_error_message());
                 }

                 update_user_meta($customerUserId, 'phone_number', $phone);
                 
                 // Auto Login
                 wp_set_current_user($customerUserId);
                 wp_set_auth_cookie($customerUserId);
            }

            // --- Validasi Data Booking ---
            $bookingValidator = Validator::make($_POST)->rules([
                'departure_id' => 'required|numeric',
                'room_type' => 'required'
            ]);

            if ($bookingValidator->fails()) {
                throw new Exception($bookingValidator->getFirstError());
            }

            // --- Parse Passengers ---
            $passengers = [];
            if (isset($_POST['pax_name']) && is_array($_POST['pax_name'])) {
                for ($i = 0; $i < count($_POST['pax_name']); $i++) {
                    if(empty($_POST['pax_name'][$i])) continue;
                    
                    $passengers[] = [
                        'name' => sanitize_text_field($_POST['pax_name'][$i]),
                        'pax_type' => sanitize_text_field($_POST['pax_type'][$i]), // adult/child/infant
                        'passport_number' => sanitize_text_field($_POST['pax_passport'][$i] ?? ''),
                        'passport_expiry' => sanitize_text_field($_POST['pax_expiry'][$i] ?? '')
                    ];
                }
            }

            if (empty($passengers)) {
                throw new Exception("Data jamaah tidak boleh kosong.");
            }

            // --- Prepare Booking Data ---
            $bookingData = [
                'departure_id' => intval($_POST['departure_id']),
                'customer_user_id' => $customerUserId,
                'branch_id' => 1, // Default pusat
                'room_type' => sanitize_text_field($_POST['room_type']),
                'coupon_code' => sanitize_text_field($_POST['coupon_code'] ?? ''),
                'status' => 'pending',
                'created_at' => current_time('mysql'),
                // Estimasi harga dari frontend (akan divalidasi ulang oleh admin Finance)
                'total_price' => isset($_POST['total_price_estimate']) ? floatval($_POST['total_price_estimate']) : 0
            ];
            
            // 1. Create Main Booking
            $bookingId = $this->bookingRepository->create($bookingData);

            // 2. Save Passengers (Manual handling via DB Interface)
            $tablePassengers = $this->db->prefix() . 'umh_booking_passengers';
            foreach ($passengers as $pax) {
                $this->db->insert($tablePassengers, [
                    'booking_id' => $bookingId,
                    'name' => $pax['name'],
                    'pax_type' => $pax['pax_type'],
                    'passport_number' => $pax['passport_number'],
                    'passport_expiry' => $pax['passport_expiry']
                ]);
            }
            
            // 3. Save Addons (Many-to-Many)
            if (isset($_POST['addons']) && is_array($_POST['addons'])) {
                $tableAddons = $this->db->prefix() . 'umh_booking_addons';
                foreach ($_POST['addons'] as $addonId) {
                    $this->db->insert($tableAddons, [
                        'booking_id' => $bookingId,
                        'addon_id' => intval($addonId),
                        'price' => 0 // Harga harusnya diambil dari DB service catalog
                    ]);
                }
            }

            // Redirect to Invoice/Success
            // Menggunakan JS redirect karena output shortcode mungkin sudah tertulis sebagian
             return "<script>window.location.href='" . admin_url('admin-post.php?action=umh_print_invoice&booking_id=' . $bookingId) . "';</script>";

        } catch (Exception $e) {
            // Tampilkan error dan render ulang form
            $errorMsg = "<div class='umroh-alert umroh-alert-danger'>Terjadi Kesalahan: " . esc_html($e->getMessage()) . "</div>";
            
            // Kita render ulang form tapi kali ini passing $_POST sebagai 'input' agar field terisi kembali
            // Perlu fetch data paket lagi agar form tidak blank
            // Untuk simplifikasi, kita return error msg dulu, user back/refresh.
            // Idealnya: call logic render() lagi.
            return $errorMsg . "<p><a href='javascript:history.back()'>Kembali ke Form</a></p>";
        }
    }
}