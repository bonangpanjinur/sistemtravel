<?php 
// File: templates/frontend/booking-form.php
// UI untuk Frontend Booking
?>
<div class="umh-booking-container">
    <div class="umh-booking-header">
        <h3>Formulir Pemesanan Umroh</h3>
        <div class="package-summary">
            <strong><?php echo esc_html($package->name); ?></strong>
            <br>Keberangkatan: <?php echo date('d F Y', strtotime($package->departure_date)); ?>
            <br>Sisa Kursi: <span class="badge badge-info"><?php echo intval($package->available_seats); ?></span>
        </div>
    </div>

    <?php if (!$user_logged_in): ?>
        <div class="alert alert-warning">
            Silakan <a href="<?php echo wp_login_url(get_permalink()); ?>">Login</a> terlebih dahulu untuk melanjutkan pemesanan.
        </div>
    <?php else: ?>

    <form id="umhBookingForm" action="<?php echo admin_url('admin-post.php'); ?>" method="POST">
        <input type="hidden" name="action" value="umh_submit_booking">
        <input type="hidden" name="departure_id" value="<?php echo esc_attr($departure_id); ?>">
        <?php wp_nonce_field('submit_booking', 'umh_booking_nonce'); ?>

        <!-- 1. Pilih Tipe Kamar -->
        <div class="form-section">
            <h4>1. Pilih Tipe Kamar</h4>
            <div class="room-options">
                <?php foreach ($pricing as $type => $price): 
                    if(in_array($type, ['quad','triple','double'])): ?>
                    <label class="room-option-card">
                        <input type="radio" name="room_type" value="<?php echo $type; ?>" data-price="<?php echo $price; ?>" <?php echo ($type === 'quad') ? 'checked' : ''; ?> required>
                        <div class="room-detail">
                            <span class="room-title"><?php echo ucfirst($type); ?></span>
                            <span class="room-price">Rp <?php echo number_format($price, 0, ',', '.'); ?></span>
                        </div>
                    </label>
                <?php endif; endforeach; ?>
            </div>
        </div>

        <!-- 2. Data Jamaah -->
        <div class="form-section">
            <div class="d-flex justify-content-between align-items-center">
                <h4>2. Data Jamaah</h4>
                <button type="button" id="addPaxBtn" class="btn btn-sm btn-outline-primary">+ Tambah Jamaah</button>
            </div>
            <div id="paxContainer">
                <!-- Baris Jamaah 1 (Default) -->
                <div class="pax-row" data-index="0">
                    <div class="pax-header">Jamaah 1</div>
                    <div class="row">
                        <div class="col-md-4">
                            <label>Nama Lengkap (Sesuai Paspor)</label>
                            <input type="text" name="pax_name[]" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label>Tipe</label>
                            <select name="pax_type[]" class="form-control pax-type-select">
                                <option value="adult">Dewasa</option>
                                <option value="child">Anak (dengan Bed)</option>
                                <option value="child_no_bed">Anak (Tanpa Bed)</option>
                                <option value="infant">Bayi (< 2 Thn)</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label>Nomor Paspor</label>
                            <input type="text" name="pax_passport[]" class="form-control">
                        </div>
                        <div class="col-md-2">
                            <label>Exp. Paspor</label>
                            <input type="date" name="pax_expiry[]" class="form-control">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Layanan Tambahan (Add-ons) -->
        <?php if (!empty($addons)): ?>
        <div class="form-section">
            <h4>3. Layanan Tambahan (Opsional)</h4>
            <div class="addons-list">
                <?php foreach ($addons as $addon): ?>
                <div class="form-check">
                    <input class="form-check-input addon-checkbox" type="checkbox" name="addons[]" value="<?php echo $addon->id; ?>" id="addon_<?php echo $addon->id; ?>" data-price="<?php echo $addon->price; ?>">
                    <label class="form-check-label" for="addon_<?php echo $addon->id; ?>">
                        <?php echo esc_html($addon->service_name); ?> 
                        <span class="text-muted">(+ Rp <?php echo number_format($addon->price, 0, ',', '.'); ?>)</span>
                    </label>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- 4. Kode Kupon -->
        <div class="form-section bg-light">
            <label>Punya Kode Promo?</label>
            <div class="input-group" style="max-width: 300px;">
                <input type="text" name="coupon_code" id="couponCode" class="form-control" placeholder="Masukkan Kode">
                <!-- Logic validasi kupon sederhana (server side handled) -->
            </div>
            <small class="text-muted">Diskon akan dihitung di halaman tagihan.</small>
        </div>

        <!-- Sticky Footer Total -->
        <div class="booking-footer">
            <div class="total-display">
                <small>Estimasi Total</small>
                <div id="grandTotalDisplay">Rp 0</div>
            </div>
            <button type="submit" class="btn btn-primary btn-lg">Lanjut Pembayaran</button>
        </div>
    </form>
    <?php endif; ?>
</div>

<!-- Data Passing ke JS -->
<script>
    var umhPricing = <?php echo json_encode($pricing); ?>;
</script>