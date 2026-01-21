<?php
// Folder: src/Services/
// File: BookingService.php

namespace App\Services;

use App\Repositories\BookingRepository;
use App\Repositories\PackageRepository;
use Exception;

class BookingService {
    private $repo;
    private $packageRepo;
    private $wpdb;

    public function __construct(BookingRepository $repo) {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->repo = $repo;
        $this->packageRepo = new PackageRepository();
    }

    public function createBooking($data) {
        // 1. Validasi Dokumen (Audit: Document Tracking)
        if (isset($data['passport_expiry']) && isset($data['departure_date'])) {
            if (DocumentService::isPassportExpiringSoon($data['passport_expiry'], $data['departure_date'])) {
                throw new Exception("Paspor harus berlaku minimal 6 bulan sebelum keberangkatan.");
            }
        }

        // 2. Transaksi Database (Audit: Race Condition)
        $this->wpdb->query('START TRANSACTION');

        try {
            // 3. LOCK ROW: Cek Stok dengan 'FOR UPDATE'
            $departure = $this->wpdb->get_row($this->wpdb->prepare(
                "SELECT id, package_id, available_seats FROM {$this->wpdb->prefix}umh_departures WHERE id = %d FOR UPDATE", 
                $data['departure_id']
            ));
            
            if (!$departure) throw new Exception("Jadwal tidak ditemukan.");
            
            // Hitung jumlah seat yang dibutuhkan (Infant biasanya tidak butuh seat bus/pesawat, tapi opsional)
            $seats_needed = 0;
            foreach ($data['passengers'] as $pax) {
                if ($pax['pax_type'] !== 'infant') $seats_needed++;
            }

            if ($departure->available_seats < $seats_needed) {
                throw new Exception("Mohon maaf, kursi tidak cukup. Tersisa: " . $departure->available_seats);
            }

            // 4. Kalkulasi Harga Dasar Paket (NEW LOGIC)
            $pricing = $this->packageRepo->getPricing($departure->package_id);
            $total_price_package = 0;
            $passengers_with_price = [];

            foreach ($data['passengers'] as $pax) {
                $type = $pax['pax_type']; // adult, child, infant
                $room_type = $data['room_type']; // quad, triple, double

                // Tentukan harga satuan
                $unit_price = 0;
                
                if ($type === 'infant') {
                    // Harga khusus bayi (biasanya flat)
                    $unit_price = isset($pricing['infant']) ? $pricing['infant'] : ($pricing['quad'] * 0.2); // Default 20% jika tak ada setting
                } elseif ($type === 'child_no_bed') {
                    // Harga anak tanpa bed
                    $unit_price = isset($pricing['child_no_bed']) ? $pricing['child_no_bed'] : ($pricing['quad'] * 0.85); // Default 85%
                } else {
                    // Dewasa atau Anak dengan Bed (Ikut harga Room Type)
                    if (isset($pricing[$room_type])) {
                        $unit_price = $pricing[$room_type];
                    } else {
                        throw new Exception("Harga untuk tipe kamar '$room_type' belum diatur.");
                    }
                }

                $total_price_package += $unit_price;
                $passengers_with_price[] = array_merge($pax, ['unit_price' => $unit_price]);
            }

            // 5. Kalkulasi & Simpan Add-ons (Layanan Tambahan) [BARU]
            $addons_total = 0;
            $addons_data = [];
            
            if (isset($data['addons']) && is_array($data['addons'])) {
                foreach ($data['addons'] as $addon_id) {
                    $service = $this->wpdb->get_row($this->wpdb->prepare(
                        "SELECT id, service_name, price FROM {$this->wpdb->prefix}umh_service_catalog WHERE id = %d AND is_active = 1",
                        $addon_id
                    ));
                    
                    if ($service) {
                        $addons_total += $service->price;
                        $addons_data[] = [
                            'service_id' => $service->id,
                            'price' => $service->price,
                            'name' => $service->service_name
                        ];
                    }
                }
            }

            // Gabungkan Total
            $grand_total = $total_price_package + $addons_total;

            // 6. Apply Kupon (Jika ada)
            $discount_total = 0;
            if (!empty($data['coupon_code'])) {
                $discount_total = $this->validateAndCalculateCoupon($data['coupon_code'], $grand_total);
            }
            
            // Final Total
            $final_total = max(0, $grand_total - $discount_total);

            // 7. Simpan Booking Utama
            $booking_data = [
                'departure_id' => $data['departure_id'],
                'branch_id' => $data['branch_id'] ?? 0,
                'customer_user_id' => $data['customer_user_id'],
                'total_price' => $final_total,
                'discount_total' => $discount_total,
                'coupon_code' => $data['coupon_code'] ?? null,
                'status' => 'pending'
            ];
            
            // Insert Booking
            $this->wpdb->insert($this->wpdb->prefix . 'umh_bookings', $booking_data);
            $bookingId = $this->wpdb->insert_id;

            // 8. Simpan Penumpang
            foreach ($passengers_with_price as $pax) {
                $this->wpdb->insert($this->wpdb->prefix . 'umh_booking_passengers', [
                    'booking_id' => $bookingId,
                    'name' => $pax['name'],
                    'pax_type' => $pax['pax_type'],
                    'passport_number' => $pax['passport_number'] ?? null,
                    'passport_expiry' => $pax['passport_expiry'] ?? null,
                    'assigned_room_type' => ($pax['pax_type'] === 'infant') ? 'infant' : $data['room_type']
                ]);
            }

            // 9. Simpan Relasi Add-ons [BARU]
            foreach ($addons_data as $addon) {
                $this->wpdb->insert($this->wpdb->prefix . 'umh_booking_addons', [
                    'booking_id' => $bookingId,
                    'service_id' => $addon['service_id'],
                    'quantity' => 1, // Default 1, bisa dikembangkan jika ada quantity selector
                    'total_price' => $addon['price']
                ]);
            }

            // 10. Kurangi Stok
            $this->repo->decreaseQuota($data['departure_id'], $seats_needed);

            // 11. Update Penggunaan Kupon
            if (!empty($data['coupon_code'])) {
                $this->incrementCouponUsage($data['coupon_code']);
            }

            // 12. Commit
            $this->wpdb->query('COMMIT');

            // 13. Notifikasi
            do_action('umh_booking_created', $bookingId);

            return $bookingId;

        } catch (Exception $e) {
            $this->wpdb->query('ROLLBACK');
            throw $e;
        }
    }

    private function validateAndCalculateCoupon($code, $cartTotal) {
        $coupon = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM {$this->wpdb->prefix}umh_coupons WHERE code = %s AND (expiry_date >= CURDATE() OR expiry_date IS NULL)", 
            $code
        ));

        if (!$coupon) return 0; // Kupon tidak valid/expired

        // Cek limit penggunaan
        if ($coupon->usage_limit > 0 && $coupon->used_count >= $coupon->usage_limit) {
            return 0; // Habis
        }

        // Hitung diskon
        if ($coupon->discount_type === 'percent') {
            return $cartTotal * ($coupon->amount / 100);
        } else {
            return min($coupon->amount, $cartTotal); // Fixed amount, jangan lebih dari total
        }
    }

    private function incrementCouponUsage($code) {
        $this->wpdb->query($this->wpdb->prepare(
            "UPDATE {$this->wpdb->prefix}umh_coupons SET used_count = used_count + 1 WHERE code = %s",
            $code
        ));
    }
}