<?php
// src/Controllers/Frontend/JemaahDashboardController.php

namespace UmhMgmt\Controllers\Frontend;

use UmhMgmt\Core\Container;
use UmhMgmt\Repositories\BookingRepository;
use UmhMgmt\Services\JemaahAppService;
use UmhMgmt\Utils\View;

/**
 * Class JemaahDashboardController
 * Mengatur logika tampilan dashboard jemaah.
 * Refactored: Uses Container & Repositories.
 *
 * @package UmhMgmt\Controllers\Frontend
 */
class JemaahDashboardController {
    
    /**
     * @var BookingRepository
     */
    private $bookingRepo;

    /**
     * @var JemaahAppService
     */
    private $jemaahService;

    public function __construct() {
        // REFACTOR: Menggunakan Container untuk Dependency Injection
        $this->bookingRepo = Container::get(BookingRepository::class);
        
        // Inject Service untuk fitur Timeline & Guides (Sesuai kode lama)
        // Kita gunakan Container agar jika JemaahAppService butuh dependency lain, bisa dihandle otomatis nanti
        $this->jemaahService = Container::get(JemaahAppService::class);

        // Register Shortcode agar bisa dipasang di Page WordPress
        // Shortcode: [umh_jemaah_dashboard]
        add_shortcode('umh_jemaah_dashboard', [$this, 'index']);
    }

    /**
     * Menampilkan halaman dashboard utama jemaah.
     * Mengembalikan string HTML (Output Buffering) untuk shortcode.
     */
    public function index() {
        // 1. Security Check
        if (!is_user_logged_in()) {
            return '<div class="umh-alert umh-alert-warning">Silakan login untuk mengakses area jamaah.</div>';
        }

        $userId = get_current_user_id();
        $user = wp_get_current_user();

        // 2. Data Fetching via Repository (Cleaner than raw SQL)
        // Menggantikan logic query manual di kode lama
        $activeBooking = $this->bookingRepo->findActiveBooking($userId);
        $unpaidBills = $this->bookingRepo->getUnpaidBills($userId);

        // 3. Data Fetching via Service (Restore Timeline & Guides features)
        $timeline = [];
        $guides = [];

        if ($activeBooking) {
            // Mengambil data progress timeline & panduan digital dari Service
            // Pastikan method ini ada di JemaahAppService
            if (method_exists($this->jemaahService, 'getProgressTimeline')) {
                $timeline = $this->jemaahService->getProgressTimeline($activeBooking->id);
            }
            if (method_exists($this->jemaahService, 'getDigitalGuides')) {
                $guides = $this->jemaahService->getDigitalGuides('all');
            }
        }

        // 4. Render View
        // Menggunakan Output Buffering karena shortcode butuh return string, bukan echo langsung
        ob_start();
        
        View::render('frontend/jemaah-dashboard', [
            'user' => $user,
            'activeBooking' => $activeBooking,
            'unpaidBills' => $unpaidBills,
            'timeline' => $timeline, // Data tambahan untuk fitur Timeline
            'guides' => $guides      // Data tambahan untuk fitur Panduan
        ]);
        
        return ob_get_clean();
    }
}