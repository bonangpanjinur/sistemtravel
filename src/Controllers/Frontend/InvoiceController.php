<?php
// src/Controllers/Frontend/InvoiceController.php

namespace App\Controllers\Frontend;

use App\Core\Container;
use App\Repositories\BookingRepository;
use App\Utils\PdfGenerator;

class InvoiceController {
    
    private $bookingRepo;

    public function __construct() {
        $this->bookingRepo = Container::get(BookingRepository::class);
        
        // Hook untuk menangkap request download invoice
        // URL: /?umh_action=print_invoice&id=123
        // Endpoint khusus untuk cetak invoice (tanpa masuk menu admin)
        add_action('init', [$this, 'handleInvoiceRequest']);
        add_action('admin_post_umh_print_invoice', [$this, 'handleInvoiceRequestLegacy']); // Legacy support
    }

    public function handleInvoiceRequest() {
        if (isset($_GET['umh_action']) && $_GET['umh_action'] == 'print_invoice') {
            $this->processInvoice();
        }
    }

    public function handleInvoiceRequestLegacy() {
        $this->processInvoice();
    }

    private function processInvoice() {
        if (!is_user_logged_in()) {
            wp_die('Silakan login terlebih dahulu.', 'Akses Ditolak', ['response' => 403]);
        }

        // Support both 'id' and 'booking_id' parameters
        $bookingId = isset($_GET['id']) ? intval($_GET['id']) : (isset($_GET['booking_id']) ? intval($_GET['booking_id']) : 0);
        
        if (!$bookingId) wp_die('Booking ID tidak valid.');

        $this->generatePdf($bookingId);
    }

    public function generatePdf($bookingId) {
        // 1. Ambil Data Booking dengan Detail Lengkap (menggunakan method legacy yang sudah di-refactor di repo)
        // Method findAllWithDetails di repo mengembalikan array, kita filter manual atau buat method findWithDetails di repo
        // Untuk efisiensi, kita bisa pakai DB wrapper atau method khusus di repo.
        // Disini kita pakai find() basic lalu enrich, atau idealnya repo punya findWithDetails($id)
        
        // Menggunakan find() dari repo yang sudah ada (basic info)
        $booking = $this->bookingRepo->find($bookingId);
        
        if (!$booking) wp_die('Data booking tidak ditemukan.');

        // 2. Security Check: Pastikan user berhak melihat invoice ini
        $currentUserId = get_current_user_id();
        $isAgentOrStaff = current_user_can('edit_posts'); 
        
        if ($booking->customer_user_id != $currentUserId && !$isAgentOrStaff) {
            wp_die('Anda tidak memiliki akses ke invoice ini.');
        }

        // Enrich Booking Data (karena find() basic mungkin belum join package/departure)
        // Kita ambil data lengkap manual jika repo find() belum support join, 
        // atau kita asumsikan template butuh data detail.
        // Untuk amannya, kita panggil method di repo jika ada, atau gunakan DB wrapper disini untuk join spesifik.
        // Sesuai kode lama, kita butuh package_name, departure_date, branch_name.
        // Kita bisa update $booking object ini.
        
        // Alternatif: Gunakan BookingRepository::findAllWithDetails tapi filter by ID (inefisien tapi cepat implementasi)
        // Lebih baik query spesifik.
        global $wpdb;
        $details = $wpdb->get_row($wpdb->prepare(
            "SELECT d.departure_date, p.name as package_name, br.name as branch_name 
             FROM {$wpdb->prefix}umh_bookings b
             LEFT JOIN {$wpdb->prefix}umh_departures d ON b.departure_id = d.id
             LEFT JOIN {$wpdb->prefix}umh_packages p ON d.package_id = p.id
             LEFT JOIN {$wpdb->prefix}umh_branches br ON b.branch_id = br.id
             WHERE b.id = %d",
            $bookingId
        ));
        
        if ($details) {
            $booking->package_name = $details->package_name;
            $booking->departure_date = $details->departure_date;
            $booking->branch_name = $details->branch_name;
        }

        // 3. Ambil Data Penumpang
        $passengers = $this->bookingRepo->getPassengers($bookingId);

        // 4. Ambil Data Payments
        $payments = $this->bookingRepo->getPayments($bookingId);
        
        // Filter verified payments untuk hitung total
        $total_paid = 0;
        foreach ($payments as $pay) {
            if ($pay->status == 'verified' || $pay->status == 'settlement') {
                $total_paid += $pay->amount;
            }
        }
        $due_amount = $booking->total_price - $total_paid;

        // 5. Data Perusahaan (From Settings or Fallback)
        $companySettings = get_option('umh_general_settings', []);
        $company = [
            'name' => isset($companySettings['company_name']) ? $companySettings['company_name'] : get_bloginfo('name'),
            'address' => isset($companySettings['company_address']) ? $companySettings['company_address'] : 'Alamat belum diatur',
            'phone' => isset($companySettings['company_phone']) ? $companySettings['company_phone'] : '-',
            'email' => get_bloginfo('admin_email'),
            'logo' => isset($companySettings['company_logo']) ? $companySettings['company_logo'] : ''
        ];

        // 6. Ambil Add-ons (Jika ada tabelnya, sesuai kode lama)
        $addons = $wpdb->get_results($wpdb->prepare(
            "SELECT ba.*, s.service_name 
             FROM {$wpdb->prefix}umh_booking_addons ba
             JOIN {$wpdb->prefix}umh_service_catalog s ON ba.service_id = s.id
             WHERE ba.booking_id = %d",
            $bookingId
        ));

        // Render Template HTML ke Variable (Output Buffering)
        ob_start();
        
        // Variable ini akan bisa diakses di dalam file template
        $data = [
            'booking' => $booking,
            'passengers' => $passengers,
            'payments' => $payments,
            'addons' => $addons,
            'company' => $company,
            'total_paid' => $total_paid,
            'due_amount' => $due_amount
        ];
        
        // Load file template invoice khusus PDF (HTML bersih tanpa CSS Admin WP)
        // Kita asumsikan file ini ada di templates/frontend/invoice-print.php
        // Pastikan path ini benar sesuai struktur folder
        $templatePath = UMH_PLUGIN_DIR . 'templates/frontend/invoice-print.php';
        if (file_exists($templatePath)) {
            include $templatePath;
        } else {
            echo "Template invoice tidak ditemukan: $templatePath";
        }
        
        $html = ob_get_clean();

        // Generate PDF
        $filename = 'Invoice-' . ($booking->code ?? $bookingId) . '.pdf';
        PdfGenerator::generate($html, $filename);
    }
}