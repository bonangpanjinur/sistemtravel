<?php
// File: agent-dashboard.php
// Location: templates/frontend/agent-dashboard.php

/** @var object $user */
/** @var int $total_bookings */
/** @var object $commission_stats (pending, paid, total) */
/** @var array $recent_sales */
/** @var string $referral_link */
?>

<div class="umh-agent-dashboard">
    <style>
        .umh-agent-dashboard { font-family: 'Segoe UI', sans-serif; max-width: 1100px; margin: 0 auto; color: #333; }
        
        /* Stats Cards */
        .umh-stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .umh-stat-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .umh-stat-label { font-size: 0.9rem; color: #718096; display: block; margin-bottom: 5px; text-transform: uppercase; letter-spacing: 0.5px; }
        .umh-stat-value { font-size: 1.8rem; font-weight: bold; color: #2d3748; }
        .umh-stat-card.blue { border-left: 4px solid #3182ce; }
        .umh-stat-card.green { border-left: 4px solid #48bb78; }
        .umh-stat-card.orange { border-left: 4px solid #ed8936; }

        /* Referral Box */
        .umh-ref-box { background: #ebf8ff; border: 1px dashed #4299e1; padding: 20px; border-radius: 8px; margin-bottom: 30px; text-align: center; }
        .umh-ref-input { width: 80%; padding: 10px; font-size: 1rem; border: 1px solid #ccc; border-radius: 4px; text-align: center; color: #2b6cb0; font-weight: bold; background: #fff; }
        .umh-btn-copy { padding: 10px 20px; background: #3182ce; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; margin-left: 10px; }
        
        /* Tables */
        .umh-table-wrapper { background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; }
        .umh-table-header { padding: 15px 20px; background: #f7fafc; border-bottom: 1px solid #e2e8f0; font-weight: bold; font-size: 1.1rem; }
        .umh-data-table { width: 100%; border-collapse: collapse; }
        .umh-data-table th, .umh-data-table td { padding: 12px 20px; text-align: left; border-bottom: 1px solid #edf2f7; font-size: 0.95rem; }
        .umh-data-table th { background: #f8f9fa; color: #4a5568; }
        .status-paid { color: #2f855a; font-weight: bold; }
        .status-pending { color: #c05621; font-weight: bold; }
    </style>

    <div style="margin-bottom:30px; display:flex; justify-content:space-between; align-items:center;">
        <div>
            <h2 style="margin:0;">Dashboard Mitra Agen</h2>
            <p style="margin:5px 0 0; color:#718096;">Selamat datang, <strong><?php echo esc_html($user->display_name); ?></strong></p>
        </div>
        <div>
            <!-- Tombol Booking Manual (Agen mendaftarkan Jemaah) -->
            <a href="<?php echo home_url('/katalog-umroh'); ?>" class="button" style="background:#2d3748; color:#fff; text-decoration:none; padding:10px 20px; border-radius:4px;">+ Daftarkan Jemaah Baru</a>
        </div>
    </div>

    <!-- Referral Link -->
    <div class="umh-ref-box">
        <h4 style="margin-top:0; margin-bottom:10px;">🔗 Link Referral Anda</h4>
        <p style="font-size:0.9rem; margin-bottom:15px; color:#4a5568;">Bagikan link ini. Setiap jemaah yang mendaftar melalui link ini akan otomatis tercatat sebagai penjualan Anda.</p>
        <div style="display:flex; justify-content:center;">
            <input type="text" value="<?php echo esc_attr($referral_link); ?>" class="umh-ref-input" readonly id="refLink">
            <button class="umh-btn-copy" onclick="copyRef()">Salin</button>
        </div>
    </div>

    <!-- Statistik -->
    <div class="umh-stats-grid">
        <div class="umh-stat-card blue">
            <span class="umh-stat-label">Total Penjualan (Pax)</span>
            <div class="umh-stat-value"><?php echo number_format($total_bookings); ?></div>
        </div>
        <div class="umh-stat-card green">
            <span class="umh-stat-label">Komisi Dibayarkan (Cair)</span>
            <div class="umh-stat-value">Rp <?php echo number_format($commission_stats->paid ?? 0, 0, ',', '.'); ?></div>
        </div>
        <div class="umh-stat-card orange">
            <span class="umh-stat-label">Komisi Pending</span>
            <div class="umh-stat-value">Rp <?php echo number_format($commission_stats->pending ?? 0, 0, ',', '.'); ?></div>
        </div>
    </div>

    <!-- Riwayat Penjualan Terbaru -->
    <div class="umh-table-wrapper">
        <div class="umh-table-header">📊 Riwayat Penjualan Terakhir</div>
        <table class="umh-data-table">
            <thead>
                <tr>
                    <th>No. Order</th>
                    <th>Nama Paket</th>
                    <th>Tgl Booking</th>
                    <th>Total Harga</th>
                    <th>Status Booking</th>
                    <th>Est. Komisi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($recent_sales)): ?>
                    <?php foreach ($recent_sales as $row): ?>
                        <tr>
                            <td>#<?php echo esc_html($row->id); ?></td>
                            <td><?php echo esc_html($row->package_name); ?></td>
                            <td><?php echo date('d M Y', strtotime($row->created_at)); ?></td>
                            <td>Rp <?php echo number_format($row->total_price, 0, ',', '.'); ?></td>
                            <td>
                                <span class="status-<?php echo esc_attr($row->booking_status); ?>">
                                    <?php echo ucfirst($row->booking_status); ?>
                                </span>
                            </td>
                            <td>
                                <!-- Simulasi Hitungan Komisi (Misal 2.5% atau Fixed 500rb per pax) -->
                                <!-- Logic aslinya harusnya ambil dari tabel umh_commissions -->
                                <?php $est_comm = 500000; // Placeholder ?> 
                                <span style="color:#2b6cb0;">Rp <?php echo number_format($est_comm, 0, ',', '.'); ?></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align:center; padding:30px;">Belum ada penjualan. Yuk mulai share link referral!</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <script>
    function copyRef() {
        var copyText = document.getElementById("refLink");
        copyText.select();
        copyText.setSelectionRange(0, 99999); 
        document.execCommand("copy");
        alert("Link referral berhasil disalin!");
    }
    </script>
</div>