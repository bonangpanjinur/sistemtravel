<?php
// File: jemaah-dashboard.php
// Location: templates/frontend/jemaah-dashboard.php

/** @var object $user Data user WordPress */
/** @var array $bookings List booking milik user */
?>

<div class="umh-dashboard-container">
    <style>
        .umh-dashboard-container { font-family: sans-serif; max-width: 1000px; margin: 0 auto; }
        .umh-header-profile { background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 30px; border-left: 5px solid #2271b1; }
        .umh-section-title { font-size: 1.5rem; margin-bottom: 15px; color: #333; border-bottom: 2px solid #eee; padding-bottom: 10px; }
        
        /* Card Booking */
        .umh-booking-card { border: 1px solid #ddd; border-radius: 8px; padding: 20px; margin-bottom: 20px; background: #fff; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .umh-booking-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .umh-booking-id { font-weight: bold; color: #555; }
        
        /* Status Badges */
        .umh-badge-status { padding: 5px 10px; border-radius: 15px; font-size: 0.85rem; font-weight: 600; text-transform: uppercase; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-paid { background: #d4edda; color: #155724; }
        .status-canceled { background: #f8d7da; color: #721c24; }

        .umh-btn-action { background: #2271b1; color: #fff; text-decoration: none; padding: 8px 15px; border-radius: 4px; display: inline-block; font-size: 0.9rem; }
        .umh-btn-action:hover { background: #135e96; color: #fff; }
    </style>

    <!-- 1. Header Profil -->
    <div class="umh-header-profile">
        <h2 style="margin-top:0;">Assalamu'alaikum, <?php echo esc_html($user->display_name); ?></h2>
        <p>Email: <?php echo esc_html($user->user_email); ?></p>
        <p>Member ID: <strong>JM-<?php echo esc_html($user->ID); ?></strong></p>
    </div>

    <!-- 2. Riwayat Pesanan -->
    <h3 class="umh-section-title">Riwayat Perjalanan Ibadah</h3>
    
    <?php if (!empty($bookings)): ?>
        <?php foreach ($bookings as $bk): ?>
            <div class="umh-booking-card">
                <div class="umh-booking-header">
                    <span class="umh-booking-id">No. Booking: #<?php echo esc_html($bk->id); ?></span>
                    <span class="umh-badge-status status-<?php echo esc_attr($bk->status); ?>">
                        <?php echo esc_html($bk->status); ?>
                    </span>
                </div>
                
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                    <div>
                        <strong style="display:block; color:#777; font-size:0.9rem;">Paket Umroh</strong>
                        <span style="font-size:1.1rem; font-weight:bold;"><?php echo esc_html($bk->package_name); ?></span>
                    </div>
                    <div>
                        <strong style="display:block; color:#777; font-size:0.9rem;">Tanggal Keberangkatan</strong>
                        <span style="font-size:1.1rem;"><?php echo date('d F Y', strtotime($bk->departure_date)); ?></span>
                    </div>
                    <div>
                        <strong style="display:block; color:#777; font-size:0.9rem;">Total Biaya</strong>
                        <span style="font-size:1.1rem; color:#d63638;">Rp <?php echo number_format($bk->total_price, 0, ',', '.'); ?></span>
                    </div>
                    <div>
                        <strong style="display:block; color:#777; font-size:0.9rem;">Cabang Pendaftaran</strong>
                        <span><?php echo esc_html($bk->branch_name ?? 'Pusat'); ?></span>
                    </div>
                </div>

                <div style="border-top: 1px solid #eee; padding-top: 15px; text-align: right;">
                    <?php if ($bk->status == 'pending'): ?>
                        <a href="#" class="umh-btn-action">Bayar Sekarang</a>
                    <?php elseif ($bk->status == 'paid'): ?>
                        <a href="#" class="umh-btn-action" style="background:#46b450;">Download Invoice</a>
                        <a href="#" class="umh-btn-action" style="background:#46b450;">E-Tiket & Itinerary</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div style="text-align:center; padding: 40px; background: #fff; border: 1px dashed #ccc; border-radius: 8px;">
            <p>Anda belum memiliki riwayat pemesanan paket umroh.</p>
            <a href="<?php echo home_url('/katalog-umroh'); ?>" class="umh-btn-action">Lihat Katalog Paket</a>
        </div>
    <?php endif; ?>

    <!-- 3. Persyaratan Dokumen (Next Feature) -->
    <div style="margin-top: 40px;">
        <h3 class="umh-section-title">Dokumen Perjalanan</h3>
        <p>Silakan upload Paspor dan KTP pada menu detail booking setelah melakukan pembayaran.</p>
    </div>
</div>