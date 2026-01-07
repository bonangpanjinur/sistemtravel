<?php
// File: package-detail.php
// Location: templates/frontend/package-detail.php

/** @var object $package Data utama paket */
/** @var array $pricing Data harga (quad/triple/double) */
/** @var array $itinerary Data itinerary harian */
/** @var array $facilities Data fasilitas include/exclude */
/** @var array $departures Data jadwal keberangkatan */
?>

<div class="umh-detail-wrapper">
    <style>
        .umh-detail-wrapper { max-width: 1100px; margin: 0 auto; font-family: sans-serif; color: #333; }
        .umh-detail-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 40px; }
        
        /* Left Column */
        .umh-hero-image { width: 100%; height: 400px; object-fit: cover; border-radius: 8px; margin-bottom: 20px; }
        .umh-title { font-size: 2rem; margin-bottom: 10px; color: #2271b1; }
        .umh-meta-row { display: flex; gap: 20px; margin-bottom: 30px; color: #666; font-size: 0.95rem; }
        .umh-section-head { font-size: 1.4rem; border-bottom: 2px solid #eee; padding-bottom: 10px; margin-top: 30px; margin-bottom: 20px; }
        
        /* Itinerary Timeline */
        .umh-timeline-item { position: relative; padding-left: 30px; margin-bottom: 20px; border-left: 2px solid #ddd; }
        .umh-timeline-dot { position: absolute; left: -6px; top: 0; width: 10px; height: 10px; background: #2271b1; border-radius: 50%; }
        .umh-day-title { font-weight: bold; display: block; margin-bottom: 5px; color: #444; }
        
        /* Facilities List */
        .umh-facility-list { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; list-style: none; padding: 0; }
        .umh-facility-item.included:before { content: '✅ '; }
        .umh-facility-item.excluded:before { content: '❌ '; }

        /* Right Column (Booking Card) */
        .umh-booking-card { background: #fff; padding: 25px; border-radius: 8px; border: 1px solid #ddd; box-shadow: 0 4px 10px rgba(0,0,0,0.05); position: sticky; top: 20px; }
        .umh-price-display { font-size: 2rem; font-weight: bold; color: #d63638; text-align: center; margin-bottom: 20px; }
        .umh-price-label { font-size: 0.9rem; color: #777; font-weight: normal; display: block; }
        .umh-form-group { margin-bottom: 15px; }
        .umh-form-label { display: block; font-weight: 600; margin-bottom: 5px; }
        .umh-select { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; }
        .umh-btn-book { width: 100%; padding: 12px; background: #2271b1; color: #fff; border: none; border-radius: 4px; font-size: 1.1rem; cursor: pointer; transition: 0.2s; }
        .umh-btn-book:hover { background: #135e96; }

        @media (max-width: 768px) {
            .umh-detail-grid { grid-template-columns: 1fr; }
            .umh-hero-image { height: 250px; }
        }
    </style>

    <div class="umh-detail-grid">
        <!-- LEFT COLUMN: Content -->
        <div class="umh-left-col">
            <?php if (!empty($package->package_image_url)): ?>
                <img src="<?php echo esc_url($package->package_image_url); ?>" class="umh-hero-image" alt="Cover Paket">
            <?php endif; ?>

            <h1 class="umh-title"><?php echo esc_html($package->name); ?></h1>
            
            <div class="umh-meta-row">
                <span>⏱️ <?php echo esc_html($package->duration_days); ?> Hari</span>
                <span>🏨 Mekkah: <?php echo esc_html($package->hotel_mekkah_name ?? 'TBA'); ?></span>
                <span>🏨 Madinah: <?php echo esc_html($package->hotel_madinah_name ?? 'TBA'); ?></span>
                <span>✈️ <?php echo esc_html($package->airline_name ?? 'TBA'); ?></span>
            </div>

            <div class="umh-description">
                <p><?php echo nl2br(esc_html($package->description)); ?></p>
            </div>

            <h3 class="umh-section-head">Rencana Perjalanan (Itinerary)</h3>
            <div class="umh-itinerary-list">
                <?php if (!empty($itinerary)): foreach ($itinerary as $day): ?>
                    <div class="umh-timeline-item">
                        <div class="umh-timeline-dot"></div>
                        <span class="umh-day-title">Hari ke-<?php echo $day->day_number; ?>: <?php echo esc_html($day->title); ?></span>
                        <p style="margin:0; font-size:0.95rem; color:#555;">
                            <?php echo esc_html($day->description); ?>
                            <?php if(!empty($day->location)) echo '<br><small>📍 ' . esc_html($day->location) . '</small>'; ?>
                        </p>
                    </div>
                <?php endforeach; else: ?>
                    <p>Itinerary detail belum tersedia.</p>
                <?php endif; ?>
            </div>

            <h3 class="umh-section-head">Fasilitas Paket</h3>
            <div class="umh-facility-list">
                <?php if (!empty($facilities)): foreach ($facilities as $fac): ?>
                    <div class="umh-facility-item <?php echo esc_attr($fac->type); ?>">
                        <?php echo esc_html($fac->facility_name); ?>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </div>

        <!-- RIGHT COLUMN: Booking Card -->
        <div class="umh-right-col">
            <div class="umh-booking-card">
                <div class="umh-price-display" id="display-price">
                    <small class="umh-price-label">Mulai dari</small>
                    Rp <?php echo number_format($pricing['quad'] ?? 0, 0, ',', '.'); ?>
                </div>

                <form action="<?php echo site_url('/booking'); ?>" method="get">
                    <!-- Hidden input untuk ID Paket -->
                    <input type="hidden" name="package_id" value="<?php echo esc_attr($package->id); ?>">

                    <div class="umh-form-group">
                        <label class="umh-form-label">Pilih Tanggal Keberangkatan</label>
                        <select name="departure_id" id="departure-select" class="umh-select" required>
                            <option value="">-- Pilih Tanggal --</option>
                            <?php if (!empty($departures)): foreach ($departures as $dep): ?>
                                <option value="<?php echo $dep->id; ?>">
                                    <?php echo date('d M Y', strtotime($dep->departure_date)); ?> 
                                    (Sisa: <?php echo $dep->available_seats; ?> Seat)
                                </option>
                            <?php endforeach; else: ?>
                                <option value="" disabled>Jadwal Habis / Belum Ada</option>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="umh-form-group">
                        <label class="umh-form-label">Tipe Kamar (Room Type)</label>
                        <select id="room-type-select" class="umh-select">
                            <option value="quad" data-price="<?php echo $pricing['quad']; ?>">Quad (Sekamar Ber-4)</option>
                            <option value="triple" data-price="<?php echo $pricing['triple']; ?>">Triple (Sekamar Ber-3)</option>
                            <option value="double" data-price="<?php echo $pricing['double']; ?>">Double (Sekamar Ber-2)</option>
                        </select>
                        <small style="color:#666; display:block; margin-top:5px;">Harga otomatis menyesuaikan tipe kamar.</small>
                    </div>

                    <button type="submit" class="umh-btn-book">Lanjut Booking</button>
                    
                    <div style="margin-top:15px; text-align:center; font-size:0.85rem; color:#777;">
                        <p>🔒 Transaksi Aman & Terpercaya</p>
                        <p>Butuh bantuan? <a href="#">Hubungi CS</a></p>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Simple JS untuk Kalkulator Harga -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const priceDisplay = document.getElementById('display-price');
            const roomSelect = document.getElementById('room-type-select');
            
            // Format Rupiah
            const formatRupiah = (number) => {
                return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(number);
            }

            // Event Listener Ganti Tipe Kamar
            roomSelect.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                const price = selectedOption.getAttribute('data-price');
                
                if (price) {
                    // Update tampilan harga, tapi pertahankan label "Harga per pax"
                    priceDisplay.innerHTML = `<small class="umh-price-label">Harga per pax (${this.value})</small>` + formatRupiah(price);
                }
            });
        });
    </script>
</div>