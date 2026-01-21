<?php
// Path: src/Controllers/Api/PackageApiController.php

namespace App\Controllers\Api;

use App\Repositories\PackageRepository;
use WP_REST_Request;
use WP_REST_Response;

class PackageApiController {
    private $packageRepository;

    public function __construct(PackageRepository $packageRepository) {
        $this->packageRepository = $packageRepository;
    }

    /**
     * Endpoint: /wp-json/umh/v1/calculate-price
     * Method: POST
     */
    public function calculatePrice(WP_REST_Request $request) {
        $packageId = $request->get_param('package_id');
        $roomType = $request->get_param('room_type'); // quad, triple, double
        $paxCount = $request->get_param('pax_count') ?: 1;
        $addons = $request->get_param('addons') ?: [];

        if (!$packageId || !$roomType) {
            return new WP_REST_Response(['error' => 'Data tidak lengkap'], 400);
        }

        // 1. Ambil Base Price Paket
        $pricing = $this->packageRepository->getPricing($packageId);
        $basePrice = isset($pricing[$roomType]) ? floatval($pricing[$roomType]) : 0;

        if ($basePrice == 0) {
            return new WP_REST_Response(['error' => 'Harga tidak ditemukan untuk tipe kamar ini'], 404);
        }

        // 2. Hitung Total Dasar
        $subtotal = $basePrice * $paxCount;

        // 3. Hitung Addons (Contoh logika sederhana, idealnya ambil harga dari DB)
        $addonTotal = 0;
        // Simulasi harga addon (nanti bisa diganti query DB sebenarnya)
        $addonPrices = [
            1 => 500000, // ID Addon 1 (Misal: Kereta Cepat)
            2 => 1500000 // ID Addon 2 (Misal: Upgrade Hotel)
        ];

        foreach ($addons as $addonId) {
            if (isset($addonPrices[$addonId])) {
                $addonTotal += ($addonPrices[$addonId] * $paxCount);
            }
        }

        $grandTotal = $subtotal + $addonTotal;

        return new WP_REST_Response([
            'success' => true,
            'data' => [
                'base_price' => $basePrice,
                'pax_count' => $paxCount,
                'subtotal' => $subtotal,
                'addon_total' => $addonTotal,
                'grand_total' => $grandTotal,
                'formatted_total' => 'Rp ' . number_format($grandTotal, 0, ',', '.')
            ]
        ], 200);
    }
}