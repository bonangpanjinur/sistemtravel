<?php
// File: finance.php
// Location: templates/admin/finance.php
?>
<div class="wrap">
    <h1 class="wp-heading-inline">Finance Center & Verifikasi Pembayaran</h1>
    <hr class="wp-header-end">

    <!-- Notifikasi -->
    <?php if (isset($_GET['msg']) && $_GET['msg'] == 'verified'): ?>
        <div class="notice notice-success is-dismissible"><p>Pembayaran berhasil diverifikasi.</p></div>
    <?php endif; ?>

    <!-- TABEL 1: Perlu Verifikasi -->
    <div style="background:#fff; border:1px solid #ccd0d4; padding:20px; margin-top:20px; border-left:4px solid #d63638;">
        <h2 style="margin-top:0;">⏳ Menunggu Verifikasi (Incoming)</h2>
        <p>Harap cek mutasi rekening sebelum melakukan verifikasi.</p>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Jemaah / Booking ID</th>
                    <th>Bank & Pengirim</th>
                    <th>Nominal</th>
                    <th>Bukti Transfer</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($pending_payments)): ?>
                    <?php foreach ($pending_payments as $row): ?>
                        <tr>
                            <td><?php echo date('d M Y H:i', strtotime($row->created_at)); ?></td>
                            <td>
                                <strong><?php echo esc_html($row->jemaah_name); ?></strong><br>
                                <a href="#">Booking #<?php echo $row->booking_id; ?></a>
                            </td>
                            <td>
                                Ke: <?php echo esc_html($row->bank_target); ?><br>
                                Dari: <em><?php echo esc_html($row->sender_name); ?></em>
                            </td>
                            <td>
                                <strong style="color:#2271b1; font-size:16px;">Rp <?php echo number_format($row->amount, 0, ',', '.'); ?></strong>
                            </td>
                            <td>
                                <a href="<?php echo esc_url($row->proof_file_url); ?>" target="_blank" class="button">Lihat Bukti</a>
                            </td>
                            <td>
                                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="display:inline-block;">
                                    <input type="hidden" name="action" value="umh_verify_payment">
                                    <input type="hidden" name="payment_id" value="<?php echo $row->id; ?>">
                                    <?php wp_nonce_field('umh_finance_action'); ?>
                                    <button type="submit" class="button button-primary" onclick="return confirm('Yakin valid? Aksi ini akan mengupdate status booking.')">Verifikasi ✅</button>
                                </form>
                                
                                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="display:inline-block; margin-left:5px;">
                                    <input type="hidden" name="action" value="umh_reject_payment">
                                    <input type="hidden" name="payment_id" value="<?php echo $row->id; ?>">
                                    <?php wp_nonce_field('umh_finance_action'); ?>
                                    <button type="submit" class="button button-link-delete" onclick="return confirm('Tolak pembayaran ini?')">Tolak</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6">Tidak ada pembayaran yang menunggu verifikasi.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- TABEL 2: Riwayat Verifikasi -->
    <h3 style="margin-top:30px;">Riwayat Verifikasi Terakhir</h3>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>Tanggal Verifikasi</th>
                <th>Jemaah</th>
                <th>Nominal</th>
                <th>Status</th>
                <th>Oleh</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($history)): foreach ($history as $h): ?>
                <tr>
                    <td><?php echo date('d M Y', strtotime($h->verified_at)); ?></td>
                    <td><?php echo esc_html($h->jemaah_name); ?></td>
                    <td>Rp <?php echo number_format($h->amount, 0, ',', '.'); ?></td>
                    <td>
                        <?php if ($h->status == 'verified'): ?>
                            <span style="color:green; font-weight:bold;">Diterima</span>
                        <?php else: ?>
                            <span style="color:red; font-weight:bold;">Ditolak</span>
                        <?php endif; ?>
                    </td>
                    <td>Admin #<?php echo $h->verified_by; ?></td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>