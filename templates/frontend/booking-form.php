<?php
// File: booking-form.php
// Location: templates/frontend/booking-form.php

/** @var array $prefill Data dari URL (departure_id, package_id, dll) */

// Ambil detail keberangkatan jika sudah dipilih sebelumnya
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
    <style>
        .umh-booking-wrapper { max-width: 800px; margin: 0 auto; padding: 20px; background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; }
        .umh-summary-box { background: #f0f9ff; border: 1px solid #bde0fe; padding: 15px; border-radius: 6px; margin-bottom: 20px; }
        .umh-form-group { margin-bottom: 20px; }
        .umh-form-label { display: block; font-weight: 600; margin-bottom: 8px; color: #2d3748; }
        .umh-form-control { width: 100%; padding: 10px; border: 1px solid #cbd5e0; border-radius: 4px; font-size: 1rem; }
        .umh-section-title { font-size: 1.2rem; border-bottom: 2px solid #edf2f7; padding-bottom: 10px; margin-bottom: 20px; margin-top: 30px; color: #2271b1; }
        .umh-btn-submit { background: #48bb78; color: white; padding: 12px 24px; border: none; border-radius: 4px; font-size: 1.1rem; cursor: pointer; width: 100%; font-weight: bold; }
        .umh-btn-submit:hover { background: #38a169; }
        .umh-passenger-card { background: #f7fafc; padding: 15px; border: 1px dashed #cbd5e0; margin-bottom: 15px; border-radius: 6px; }
    </style>

    <h2 style="text-align:center; margin-bottom:30px;">Formulir Pendaftaran Umroh</h2>

    <!-- Feedback Message Container -->
    <div id="umh-form-message" style="display:none; padding:15px; margin-bottom:20px; border-radius:4px;"></div>

    <?php if ($selected_departure): ?>
        <div class="umh-summary-box">
            <h4 style="margin-top:0; color:#2c5282;">📦 Paket yang Dipilih:</h4>
            <p style="margin:5px 0;"><strong><?php echo esc_html($selected_departure->package_name); ?></strong></p>
            <p style="margin:5px 0;">📅 Keberangkatan: <?php echo date('d F Y', strtotime($selected_departure->departure_date)); ?></p>
        </div>
    <?php endif; ?>

    <form id="umh-booking-form" method="post" action="<?php echo admin_url('admin-post.php'); ?>">
        <!-- Action Hook untuk Controller -->
        <input type="hidden" name="action" value="umh_submit_booking">
        <?php wp_nonce_field('umh_booking_nonce', 'umh_booking_nonce'); ?>
        
        <!-- Jika sudah ada ID dari URL, jadikan hidden atau read-only select -->
        <div class="umh-form-group">
            <label for="departure_id" class="umh-form-label">Jadwal Keberangkatan</label>
            <select name="departure_id" id="departure_id" required class="umh-form-control">
                <option value="">-- Pilih Jadwal --</option>
                <?php 
                $repo = new \UmhMgmt\Repositories\OperationalRepository();
                $departures = $repo->getUpcomingDepartures();
                foreach ($departures as $dep): 
                    $selected = ($prefill['departure_id'] == $dep->id) ? 'selected' : '';
                ?>
                    <option value="<?php echo esc_attr($dep->id); ?>" <?php echo $selected; ?>>
                        <?php echo esc_html($dep->package_name . ' - ' . date('d M Y', strtotime($dep->departure_date))); ?>
                        (Sisa: <?php echo esc_html($dep->available_seats); ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <h3 class="umh-section-title">👤 Data Jamaah (Penumpang)</h3>
        
        <div id="passenger-repeater">
            <div class="passenger-item umh-passenger-card" data-index="0">
                <div class="umh-form-group">
                    <label class="umh-form-label">Nama Lengkap (Sesuai Paspor)</label>
                    <input type="text" name="passengers[0][name]" required class="umh-form-control" placeholder="Contoh: MUHAMMAD ABDULLAH">
                </div>
                <div style="display:flex; gap:15px;">
                    <div class="umh-form-group" style="flex:1;">
                        <label class="umh-form-label">Nomor Paspor</label>
                        <input type="text" name="passengers[0][passport_number]" class="umh-form-control" placeholder="X1234567">
                        <small style="color:#718096;">Bisa diisi nanti di Dashboard</small>
                    </div>
                    <div class="umh-form-group" style="flex:1;">
                        <label class="umh-form-label">Masa Berlaku Paspor</label>
                        <input type="date" name="passengers[0][passport_expiry]" class="umh-form-control">
                    </div>
                </div>
            </div>
        </div>

        <button type="button" id="add-passenger" class="button" style="margin-bottom:20px;">+ Tambah Jamaah Lain</button>

        <div class="umh-form-actions" style="margin-top:30px; text-align:center;">
            <p style="margin-bottom:15px; color:#718096; font-size:0.9rem;">
                Dengan menekan tombol di bawah, Anda menyetujui Syarat & Ketentuan kami.
            </p>
            <button type="submit" class="umh-btn-submit">Konfirmasi & Pesan Sekarang</button>
        </div>
    </form>
</div>