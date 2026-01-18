<?php
// File: booking-form.php
// Location: templates/frontend/booking-form.php

/** @var array $prefill Data dari URL (departure_id, package_id, dll) */
/** @var array $pricing_data Data harga raw dari controller */

// Ambil detail keberangkatan
$selected_departure = null;
if (!empty($prefill['departure_id'])) {
    global $wpdb;
    $selected_departure = $wpdb->get_row($wpdb->prepare("
        SELECT d.*, p.name as package_name 
        FROM {$wpdb->prefix}umh_departures d
        JOIN {$wpdb->prefix}umh_packages p ON d.package_id = p.id
        WHERE d.id = %d
    ", $prefill['departure_id']));
}
?>

<div class="umh-booking-wrapper">
    <!-- Load Pricing Data ke JS Variable -->
    <script>
        var umhPricing = <?php echo json_encode($pricing_data); ?>;
    </script>

    <h2 style="text-align:center; margin-bottom:30px;">Formulir Pendaftaran Umroh</h2>

    <div id="umh-form-message" style="display:none; padding:15px; margin-bottom:20px; border-radius:4px;"></div>

    <?php if ($selected_departure): ?>
        <div class="umh-summary-box" style="background:#f0f9ff; padding:15px; border:1px solid #bde0fe; margin-bottom:20px;">
            <h4 style="margin-top:0;">📦 Paket: <?php echo esc_html($selected_departure->package_name); ?></h4>
            <p>📅 Tanggal: <strong><?php echo date('d F Y', strtotime($selected_departure->departure_date)); ?></strong></p>
        </div>
    <?php endif; ?>

    <form id="umh-booking-form">
        <!-- Hidden Inputs -->
        <input type="hidden" name="action" value="umh_submit_booking_ajax">
        <input type="hidden" name="departure_id" value="<?php echo esc_attr($prefill['departure_id']); ?>">
        <?php wp_nonce_field('umh_booking_nonce', 'umh_booking_nonce'); ?>

        <!-- Pilihan Kamar (Global untuk Booking ini) -->
        <div class="umh-form-group" style="margin-bottom:20px;">
            <label class="umh-form-label" style="font-weight:bold;">Pilihan Tipe Kamar</label>
            <select name="room_type" id="room_type" class="umh-form-control" style="width:100%; padding:10px;">
                <option value="quad" <?php selected($prefill['room_type'], 'quad'); ?>>Quad (Sekamar Ber-4)</option>
                <option value="triple" <?php selected($prefill['room_type'], 'triple'); ?>>Triple (Sekamar Ber-3)</option>
                <option value="double" <?php selected($prefill['room_type'], 'double'); ?>>Double (Sekamar Ber-2)</option>
            </select>
        </div>

        <h3 class="umh-section-title" style="border-bottom:2px solid #eee; padding-bottom:10px;">👤 Data Jamaah</h3>
        
        <div id="passenger-repeater">
            <div class="passenger-item umh-passenger-card" data-index="0" style="background:#f9f9f9; padding:15px; margin-bottom:15px; border:1px solid #ddd;">
                <div style="display:flex; justify-content:space-between; margin-bottom:10px;">
                    <strong>Jamaah #1</strong>
                </div>
                
                <div class="umh-form-group">
                    <label>Nama Lengkap (Sesuai Paspor)</label>
                    <input type="text" name="passengers[0][name]" required class="umh-form-control" style="width:100%;">
                </div>

                <div style="display:flex; gap:15px; margin-top:10px;">
                    <div style="flex:1;">
                        <label>Tipe Jamaah</label>
                        <select name="passengers[0][pax_type]" class="umh-form-control pax-type-select" style="width:100%;">
                            <option value="adult">Dewasa</option>
                            <option value="child_no_bed">Anak (No Bed)</option>
                            <option value="infant">Bayi (< 2 Th)</option>
                        </select>
                    </div>
                    <div style="flex:1;">
                        <label>No. Paspor (Opsional)</label>
                        <input type="text" name="passengers[0][passport_number]" class="umh-form-control" style="width:100%;">
                    </div>
                </div>
            </div>
        </div>

        <button type="button" id="add-passenger" class="button" style="margin-bottom:20px;">+ Tambah Jamaah Lain</button>

        <!-- Kupon Diskon -->
        <div style="margin-top:20px; padding:15px; background:#fffbe6; border:1px solid #ffe58f;">
            <label style="font-weight:bold;">Kode Promo / Kupon</label>
            <div style="display:flex; gap:10px;">
                <input type="text" name="coupon_code" id="coupon_code" class="umh-form-control" placeholder="Masukkan kode..." style="flex:1;">
                <button type="button" id="btn-check-coupon" class="button">Cek</button>
            </div>
            <div id="coupon-feedback" style="font-size:0.9rem; margin-top:5px;"></div>
        </div>

        <!-- Estimasi Harga (Kalkulasi JS) -->
        <div style="margin-top:20px; text-align:right; font-size:1.2rem;">
            Total Estimasi: <strong id="total-display" style="color:#2f855a;">Rp 0</strong>
        </div>

        <div class="umh-form-actions" style="margin-top:30px; text-align:center;">
            <button type="submit" class="umh-btn-submit" style="background:#2271b1; color:white; padding:12px 30px; border:none; font-size:1.1rem; cursor:pointer;">
                Konfirmasi & Pesan Sekarang
            </button>
        </div>
    </form>
</div>