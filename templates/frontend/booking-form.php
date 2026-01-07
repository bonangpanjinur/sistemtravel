<?php
// Folder: templates/frontend/
// File: booking-form.php
// UX Upgrade: Added ID, Message Container, and removed direct action URL
?>
<div class="umh-booking-wrapper">
    <!-- Feedback Message Container -->
    <div id="umh-form-message" style="display:none; padding:15px; margin-bottom:20px; border-radius:4px;"></div>

    <form id="umh-booking-form" method="post">
        <?php wp_nonce_field('umh_booking_nonce', 'security'); ?>
        
        <div class="umh-form-group">
            <label for="departure_id">Pilih Keberangkatan</label>
            <select name="departure_id" id="departure_id" required class="umh-form-control">
                <option value="">-- Pilih Jadwal --</option>
                <?php 
                // Populate via Controller logic passed in $data or logic here
                // Note: Idealnya data dilempar dari controller, tapi untuk kompatibilitas code lama:
                $repo = new \UmhMgmt\Repositories\OperationalRepository();
                $departures = $repo->getUpcomingDepartures();
                foreach ($departures as $dep): 
                ?>
                    <option value="<?php echo esc_attr($dep->id); ?>">
                        <?php echo esc_html($dep->package_name . ' - ' . date('d M Y', strtotime($dep->departure_date))); ?>
                        (Sisa: <?php echo esc_html($dep->available_seats); ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div id="passenger-repeater">
            <h3>Data Penumpang</h3>
            <div class="passenger-item" data-index="0" style="background:#f9f9f9; padding:15px; margin-bottom:15px; border:1px solid #ddd;">
                <div class="umh-form-group">
                    <label>Nama Lengkap (Sesuai Paspor)</label>
                    <input type="text" name="passengers[0][name]" required class="umh-form-control">
                </div>
                <div class="umh-form-row" style="display:flex; gap:10px;">
                    <div class="umh-form-group" style="flex:1;">
                        <label>Nomor Paspor</label>
                        <input type="text" name="passengers[0][passport_number]" required class="umh-form-control">
                    </div>
                    <div class="umh-form-group" style="flex:1;">
                        <label>Masa Berlaku Paspor</label>
                        <input type="date" name="passengers[0][passport_expiry]" required class="umh-form-control">
                    </div>
                </div>
            </div>
        </div>

        <div class="umh-form-actions" style="margin-top:20px;">
            <button type="button" id="add-passenger" class="button">Tambah Penumpang</button>
            <button type="submit" class="button button-primary button-large">Book Now</button>
        </div>
    </form>
</div>

<style>
    .umh-form-group { margin-bottom: 15px; }
    .umh-form-control { width: 100%; padding: 8px; }
    .umh-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .umh-error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
</style>