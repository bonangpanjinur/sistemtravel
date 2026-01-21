<?php
// Path: src/Controllers/Admin/PackageController.php

namespace App\Controllers\Admin;

use App\Repositories\PackageRepository;
use App\Repositories\MasterDataRepository;
use App\Utils\View;
use App\Utils\Validator;

class PackageController {
    private $packageRepository;
    private $masterDataRepository; // Added Dependency

    // Updated Constructor to accept MasterDataRepository
    public function __construct(PackageRepository $packageRepository, MasterDataRepository $masterDataRepository) {
        $this->packageRepository = $packageRepository;
        $this->masterDataRepository = $masterDataRepository;
    }

    public function index() {
        $tab = $_GET['tab'] ?? 'packages';
        $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';

        // Render Tabs
        $tabs = [
            ['id' => 'packages', 'label' => 'Paket Umrah', 'url' => admin_url('admin.php?page=travel-sys-sales&tab=packages')],
            ['id' => 'bookings', 'label' => 'Booking', 'url' => admin_url('admin.php?page=travel-sys-sales&tab=bookings')],
            ['id' => 'leads', 'label' => 'Leads', 'url' => admin_url('admin.php?page=travel-sys-sales&tab=leads')],
            ['id' => 'catalog', 'label' => 'Katalog', 'url' => admin_url('admin.php?page=travel-sys-sales&tab=catalog')],
        ];

        echo '<div class="wrap">';
        echo '<h1>Penjualan</h1>';
        View::renderTabs($tabs, $tab);

        switch ($tab) {
            case 'bookings':
                // Idealnya panggil BookingController atau method di sini
                echo View::render('admin/bookings/list'); 
                break;
            case 'leads':
                echo View::render('admin/leads/list');
                break;
            case 'packages':
            default:
                $packages = $this->packageRepository->getAll(10, 0, $search);
                echo View::render('admin/packages/list', [
                    'packages' => $packages,
                    'search' => $search
                ]);
                break;
        }
        echo '</div>';
    }

    public function create() {
        // Fetch Master Data for Dropdowns
        $data = [
            'package' => null,
            'hotels' => $this->masterDataRepository->getHotels(),
            'airlines' => $this->masterDataRepository->getAirlines(),
            'pricing' => [],
            'itinerary' => [],
            'facilities' => ['included' => [], 'excluded' => []]
        ];

        echo View::render('admin/packages/form', $data);
    }

    public function edit() {
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $package = $this->packageRepository->getById($id);

        if (!$package) {
            echo '<div class="notice notice-error"><p>Paket tidak ditemukan.</p></div>';
            return;
        }

        // Fetch Master Data & Related Package Data
        $data = [
            'package' => $package,
            'hotels' => $this->masterDataRepository->getHotels(),
            'airlines' => $this->masterDataRepository->getAirlines(),
            // Assuming Repository methods for these exist or will be added. 
            // If they don't exist in the current refactored repo, we need to add them or handle retrieval here.
            // For now, let's assume getPricing is there (it was in the refactored repo).
            // Itinerary & Facilities need to be handled.
            'pricing' => $this->packageRepository->getPricing($id),
            'itinerary' => $this->packageRepository->getItinerary($id) ?? [], // Fallback to empty array
            'facilities' => $this->packageRepository->getFacilities($id) ?? ['included' => [], 'excluded' => []]
        ];

        echo View::render('admin/packages/form', $data);
    }

    public function save() {
        // 1. CSRF Protection (Check Nonce)
        if (!isset($_POST['umroh_package_nonce']) || !wp_verify_nonce($_POST['umroh_package_nonce'], 'save_package_action')) {
            wp_die('Security check failed (Invalid Nonce)');
        }

        if (!current_user_can('manage_options')) wp_die('Unauthorized');

        // 2. Input Validation
        $validator = Validator::make($_POST)->rules([
            'name' => 'required|min:3',
            // 'price_quad' => 'required|numeric', // Removed as mandatory, handled in pricing loop
            'hotel_mekkah_id' => 'required|numeric',
            'hotel_madinah_id' => 'required|numeric',
            'airline_id' => 'required|numeric',
            // 'duration_days' => 'required|numeric' // If used
        ]);

        if ($validator->fails()) {
            foreach ($validator->getErrors() as $error) {
                echo '<div class="notice notice-error"><p>' . esc_html($error[0]) . '</p></div>';
            }
            // Re-render form with old data (Simplified for now, ideally pass $_POST)
            $this->create(); // Or edit() logic
            return;
        }

        // 3. Sanitization & Preparation
        $packageData = [
            'name' => sanitize_text_field($_POST['name']),
            'description' => wp_kses_post($_POST['description']),
            'hotel_mekkah_id' => intval($_POST['hotel_mekkah_id']),
            'hotel_madinah_id' => intval($_POST['hotel_madinah_id']),
            'airline_id' => intval($_POST['airline_id']),
            'departure_airport' => sanitize_text_field($_POST['departure_airport']),
            'package_image_url' => esc_url_raw($_POST['package_image_url'] ?? ''),
            // 'duration_days' => intval($_POST['duration_days']), // Add if schema supports it
        ];

        // 4. Save Core Package Data
        if (!empty($_POST['id'])) {
            $packageId = intval($_POST['id']);
            $this->packageRepository->update($packageId, $packageData);
            $message = 'Paket berhasil diperbarui.';
        } else {
            $packageId = $this->packageRepository->create($packageData);
            $message = 'Paket berhasil ditambahkan.';
        }

        // 5. Save Relational Data (Pricing, Itinerary, Facilities)
        // Note: Repository needs to expose methods for these, or we handle it here via DB Interface?
        // Best practice: Delegate to Repository. 
        // We will assume PackageRepository has methods like `savePricing`, `saveItinerary`, `saveFacilities`
        // or a consolidated `saveRelations` method. 
        // Since I cannot edit Repository in this specific response block, I will assume the Repository has been updated 
        // to include the methods `savePricing`, `saveItinerary`, `saveFacilities` as per the logic in the legacy controller.
        
        // --- PRICING ---
        $pricingData = [];
        $pricingTypes = ['quad', 'triple', 'double'];
        foreach ($pricingTypes as $type) {
            if (isset($_POST['price_' . $type])) {
                $pricingData[] = [
                    'package_id' => $packageId,
                    'room_type' => $type,
                    'price' => floatval($_POST['price_' . $type])
                ];
            }
        }
        $this->packageRepository->savePricing($packageId, $pricingData);

        // --- ITINERARY ---
        $itineraryData = [];
        if (isset($_POST['itinerary']) && is_array($_POST['itinerary'])) {
            foreach ($_POST['itinerary'] as $index => $item) {
                if (empty($item['title'])) continue;
                $itineraryData[] = [
                    'package_id' => $packageId,
                    'day_number' => $index + 1,
                    'title' => sanitize_text_field($item['title']),
                    'description' => sanitize_textarea_field($item['description']),
                    'location' => sanitize_text_field($item['location'])
                ];
            }
        }
        $this->packageRepository->saveItinerary($packageId, $itineraryData);

        // --- FACILITIES ---
        $facilitiesData = [];
        // Included
        if (isset($_POST['facilities']['included']) && is_array($_POST['facilities']['included'])) {
            foreach ($_POST['facilities']['included'] as $facility) {
                if (empty($facility)) continue;
                $facilitiesData[] = [
                    'package_id' => $packageId,
                    'facility_name' => sanitize_text_field($facility),
                    'type' => 'included'
                ];
            }
        }
        // Excluded
        if (isset($_POST['facilities']['excluded']) && is_array($_POST['facilities']['excluded'])) {
            foreach ($_POST['facilities']['excluded'] as $facility) {
                if (empty($facility)) continue;
                $facilitiesData[] = [
                    'package_id' => $packageId,
                    'facility_name' => sanitize_text_field($facility),
                    'type' => 'excluded'
                ];
            }
        }
        $this->packageRepository->saveFacilities($packageId, $facilitiesData);

        // Redirect
        echo '<script>window.location.href="admin.php?page=umroh-packages&message=' . urlencode($message) . '";</script>';
        exit;
    }

    public function delete() {
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'delete_package')) {
            wp_die('Security check failed');
        }

        if (!current_user_can('manage_options')) wp_die('Unauthorized');

        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $this->packageRepository->delete($id);

        echo '<script>window.location.href="admin.php?page=umroh-packages&message=deleted";</script>';
        exit;
    }
}