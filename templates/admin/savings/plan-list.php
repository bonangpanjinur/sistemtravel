<div class="wrap">
    <h1 class="wp-heading-inline">Daftar Tabungan Umroh</h1>
    <a href="#" class="page-title-action">Buka Tabungan Baru</a>
    <hr class="wp-header-end">

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>Nama Jamaah</th>
                <th>Target (Rp)</th>
                <th>Tenor</th>
                <th>Cicilan/Bulan</th>
                <th>Status</th>
                <th>Tanggal Mulai</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($plans)): ?>
                <?php foreach ($plans as $plan): ?>
                    <tr>
                        <td><?php echo esc_html($plan->customer_name); ?></td>
                        <td><?php echo number_format($plan->target_amount, 0, ',', '.'); ?></td>
                        <td><?php echo esc_html($plan->tenor_months); ?> Bulan</td>
                        <td><?php echo number_format($plan->monthly_amount, 0, ',', '.'); ?></td>
                        <td><?php echo esc_html(ucfirst($plan->status)); ?></td>
                        <td><?php echo esc_html($plan->created_at); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6">Belum ada data tabungan.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
