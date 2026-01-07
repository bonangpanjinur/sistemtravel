<?php
// File: PackageCatalogController.php
// Location: src/Controllers/Frontend/PackageCatalogController.php

namespace UmhMgmt\Controllers\Frontend;

use UmhMgmt\Repositories\PackageRepository;
use UmhMgmt\Utils\View;

class PackageCatalogController {
    private $repo;

    public function __construct() {
        $this->repo = new PackageRepository();
        // Mendaftarkan Shortcode [umh_package_catalog]
        add_shortcode('umh_package_catalog', [$this, 'render_catalog']);
    }

    /**
     * Logic utama untuk mengambil data paket
     */
    public function render_catalog($atts) {
        // Parsing atribut shortcode (default limit 9 paket)
        $attributes = shortcode_atts([
            'limit' => 9,
            'orderby' => 'created_at',
            'order' => 'DESC'
        ], $atts);

        // Ambil data dari database melalui Repository
        $all_packages = $this->repo->all();
        $packages = array_slice($all_packages, 0, $attributes['limit']);

        // Tambahkan logic harga terendah (Quad) ke setiap paket untuk display "Mulai dari..."
        foreach ($packages as $pkg) {
            $pricing = $this->repo->getPricing($pkg->id);
            // Default ke harga base jika pricing table kosong
            $pkg->display_price = !empty($pricing['quad']) ? $pricing['quad'] : $pkg->base_price;
            
            // Siapkan URL booking
            // Asumsi ada page 'booking' dengan shortcode [umh_booking_form]
            $pkg->booking_url = site_url('/booking?package_id=' . $pkg->id);
        }

        // Output Buffering agar shortcode tidak berantakan
        ob_start();
        
        // Panggil View (File tampilan terpisah)
        View::render('frontend/catalog-grid', ['packages' => $packages]);
        
        return ob_get_clean();
    }
}