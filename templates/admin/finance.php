<div class="wrap">
    <h1 class="wp-heading-inline">Finance Management</h1>
    
    <div class="tab-content" style="margin-top: 20px;">
        <h3>Konfirmasi Pembayaran Pending</h3>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>ID Booking</th>
                    <th>Paket</th>
                    <th>Tanggal Berangkat</th>
                    <th>Total Harga</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($pending_payments)): ?>
                    <?php foreach ($pending_payments as $payment): ?>
                        <tr>
                            <td>#<?php echo esc_html($payment->id); ?></td>
                            <td><?php echo esc_html($payment->package_name); ?></td>
                            <td><?php echo date('d M Y', strtotime($payment->departure_date)); ?></td>
                            <td>Rp <?php echo number_format($payment->total_price, 0, ',', '.'); ?></td>
                            <td><span class="umh-status-pending"><?php echo ucfirst($payment->status); ?></span></td>
                            <td><a href="#" class="button button-primary button-small">Konfirmasi</a></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6">Tidak ada pembayaran pending.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

