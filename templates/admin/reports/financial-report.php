<?php
// File: financial-report.php
// Location: templates/admin/reports/financial-report.php
?>
<div class="wrap">
    <h1 class="wp-heading-inline">Laporan Keuangan <?php echo $is_branch_view ? '(Cabang)' : '(Konsolidasi Pusat)'; ?></h1>
    <hr class="wp-header-end">

    <!-- Filter Date -->
    <form method="get" style="background:#fff; padding:15px; border:1px solid #ccc; margin-bottom:20px; display:flex; align-items:center; gap:10px;">
        <input type="hidden" name="page" value="umh-reports">
        <label>Periode:</label>
        <input type="date" name="start" value="<?php echo esc_attr($start_date); ?>">
        <span>s/d</span>
        <input type="date" name="end" value="<?php echo esc_attr($end_date); ?>">
        <button type="submit" class="button button-primary">Tampilkan</button>
    </form>

    <!-- Summary Cards -->
    <div style="display:grid; grid-template-columns: repeat(3, 1fr); gap:20px; margin-bottom:30px;">
        <div style="background:#fff; padding:20px; border-left:5px solid #2271b1; box-shadow:0 1px 3px rgba(0,0,0,0.1);">
            <h3 style="margin:0; font-size:14px; color:#666;">Total Pendapatan (Omzet)</h3>
            <div style="font-size:24px; font-weight:bold; color:#2271b1; margin-top:5px;">
                Rp <?php echo number_format($revenue, 0, ',', '.'); ?>
            </div>
        </div>
        <div style="background:#fff; padding:20px; border-left:5px solid #d63638; box-shadow:0 1px 3px rgba(0,0,0,0.1);">
            <h3 style="margin:0; font-size:14px; color:#666;">Beban Komisi Agen</h3>
            <div style="font-size:24px; font-weight:bold; color:#d63638; margin-top:5px;">
                (Rp <?php echo number_format($commissions, 0, ',', '.'); ?>)
            </div>
        </div>
        <div style="background:#fff; padding:20px; border-left:5px solid #46b450; box-shadow:0 1px 3px rgba(0,0,0,0.1);">
            <h3 style="margin:0; font-size:14px; color:#666;">Estimasi Laba Kotor</h3>
            <div style="font-size:24px; font-weight:bold; color:#46b450; margin-top:5px;">
                Rp <?php echo number_format($revenue - $commissions, 0, ',', '.'); ?>
            </div>
            <small style="color:#888;">*Belum termasuk biaya operasional lain.</small>
        </div>
    </div>

    <!-- Breakdown Table -->
    <div style="background:#fff; padding:20px; border:1px solid #ccc;">
        <h3 style="margin-top:0;">Performa Paket Umroh</h3>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Nama Paket</th>
                    <th>Jumlah Pax Terjual</th>
                    <th>Total Omzet</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($breakdown)): foreach ($breakdown as $row): ?>
                    <tr>
                        <td><strong><?php echo esc_html($row->package_name); ?></strong></td>
                        <td><?php echo number_format($row->total_pax); ?> Orang</td>
                        <td>Rp <?php echo number_format($row->total_omzet, 0, ',', '.'); ?></td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="3">Tidak ada data transaksi pada periode ini.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>