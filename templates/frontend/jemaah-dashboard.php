<?php
// File: jemaah-dashboard.php
// Location: templates/frontend/jemaah-dashboard.php

/** @var object $user */
/** @var array $bookings */

// Helper: Ambil Riwayat Pembayaran per Booking
function get_payment_history($booking_id) {
    global $wpdb;
    return $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}umh_payments WHERE booking_id = %d ORDER BY created_at DESC", 
        $booking_id
    ));
}

// Helper: Ambil Data Rekening Bank Perusahaan
function get_company_accounts() {
    global $wpdb;
    return $wpdb->get_results("SELECT * FROM {$wpdb->prefix}umh_bank_accounts WHERE is_active = 1");
}
$company_accounts = get_company_accounts();
?>

<div class="umh-dashboard-container">
    <style>
        .umh-dashboard-container { font-family: sans-serif; max-width: 1000px; margin: 0 auto; color: #333; }
        .umh-header-profile { background: #fff; padding: 25px; border-radius: 8px; margin-bottom: 30px; border: 1px solid #e2e8f0; border-top: 4px solid #2271b1; display:flex; justify-content:space-between; }
        
        /* Card & Layout */
        .umh-booking-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; margin-bottom: 30px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
        .umh-card-header { background: #f7fafc; padding: 15px 20px; border-bottom: 1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center; }
        .umh-card-body { padding: 20px; }
        
        /* Badges */
        .status-badge { padding: 4px 10px; border-radius: 12px; font-size: 0.8rem; font-weight: bold; text-transform: uppercase; }
        .badge-pending { background: #fffaf0; color: #c05621; }
        .badge-paid { background: #f0fff4; color: #2f855a; }
        .badge-pending_verification { background: #ebf8ff; color: #2b6cb0; }
        
        /* Payment Section */
        .umh-bank-list { display: flex; gap: 15px; flex-wrap: wrap; margin-bottom: 20px; }
        .umh-bank-card { border: 1px solid #cbd5e0; padding: 15px; border-radius: 6px; flex: 1; min-width: 200px; background: #f8f9fa; }
        .umh-payment-history { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 0.9rem; }
        .umh-payment-history th { text-align: left; background: #eee; padding: 8px; }
        .umh-payment-history td { border-bottom: 1px solid #eee; padding: 8px; }

        /* Buttons & Modal */
        .umh-btn { padding: 8px 16px; border-radius: 4px; text-decoration: none; font-size: 0.9rem; font-weight: 600; cursor: pointer; border: none; display:inline-block; }
        .btn-primary { background: #2271b1; color: #fff; }
        .btn-primary:hover { background: #135e96; }
        .umh-modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 999; }
        .umh-modal-box { background: #fff; width: 450px; margin: 10vh auto; padding: 25px; border-radius: 8px; position: relative; }
    </style>

    <div class="umh-header-profile">
        <div>
            <h2 style="margin:0;">Dashboard Jemaah</h2>
            <p style="margin:5px 0; color:#666;">Selamat datang, <?php echo esc_html($user->display_name); ?></p>
        </div>
    </div>

    <?php if (isset($_GET['payment_status']) && $_GET['payment_status'] == 'submitted'): ?>
        <div style="background:#f0fff4; border:1px solid #9ae6b4; color:#2f855a; padding:15px; margin-bottom:20px; border-radius:6px;">
            ✅ Bukti pembayaran berhasil dikirim. Menunggu verifikasi admin.
        </div>
    <?php endif; ?>

    <?php if (!empty($bookings)): foreach ($bookings as $bk): ?>
        <div class="umh-booking-card">
            <div class="umh-card-header">
                <strong>BOOKING #<?php echo $bk->id; ?></strong>
                <span class="status-badge badge-<?php echo $bk->status; ?>"><?php echo strtoupper($bk->status); ?></span>
            </div>
            <div class="umh-card-body">
                <!-- Info Paket -->
                <div style="margin-bottom:20px;">
                    <h3 style="margin:0 0 5px; color:#2271b1;"><?php echo esc_html($bk->package_name); ?></h3>
                    <p style="margin:0;">Keberangkatan: <?php echo date('d F Y', strtotime($bk->departure_date)); ?></p>
                    <p style="margin:5px 0 0; font-size:1.2rem;">Total Tagihan: <strong style="color:#d63638;">Rp <?php echo number_format($bk->total_price, 0, ',', '.'); ?></strong></p>
                </div>

                <!-- Bagian Pembayaran -->
                <hr style="border:0; border-top:1px solid #eee; margin:20px 0;">
                <h4 style="margin-top:0;">💳 Status & Konfirmasi Pembayaran</h4>

                <!-- 1. Daftar Rekening -->
                <?php if ($bk->status == 'pending'): ?>
                    <div style="background:#eef; padding:15px; border-radius:6px; margin-bottom:20px;">
                        <p style="margin-top:0; font-weight:bold;">Silakan transfer ke salah satu rekening berikut:</p>
                        <div class="umh-bank-list">
                            <?php foreach ($company_accounts as $acc): ?>
                                <div class="umh-bank-card">
                                    <strong style="color:#2271b1;"><?php echo esc_html($acc->bank_name); ?></strong><br>
                                    <span style="font-size:1.1rem; font-weight:bold;"><?php echo esc_html($acc->account_number); ?></span><br>
                                    <small>a.n <?php echo esc_html($acc->account_holder); ?></small>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <button class="umh-btn btn-primary btn-pay-confirm" data-id="<?php echo $bk->id; ?>">+ Konfirmasi Pembayaran (Upload Bukti)</button>
                    </div>
                <?php endif; ?>

                <!-- 2. Riwayat Pembayaran -->
                <?php $payments = get_payment_history($bk->id); ?>
                <?php if (!empty($payments)): ?>
                    <table class="umh-payment-history">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Jumlah Transfer</th>
                                <th>Status</th>
                                <th>Ket</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $total_paid = 0;
                            foreach ($payments as $pay): 
                                if($pay->status == 'verified') $total_paid += $pay->amount;
                            ?>
                                <tr>
                                    <td><?php echo date('d/m/Y H:i', strtotime($pay->created_at)); ?></td>
                                    <td>Rp <?php echo number_format($pay->amount, 0, ',', '.'); ?></td>
                                    <td>
                                        <span class="status-badge badge-<?php echo $pay->status; ?>">
                                            <?php echo ($pay->status == 'pending_verification') ? 'Diperiksa' : ucfirst($pay->status); ?>
                                        </span>
                                    </td>
                                    <td><?php echo esc_html($pay->bank_target); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr style="background:#f9f9f9; font-weight:bold;">
                                <td colspan="1">Total Terverifikasi</td>
                                <td colspan="3" style="color:#2f855a;">Rp <?php echo number_format($total_paid, 0, ',', '.'); ?></td>
                            </tr>
                        </tfoot>
                    </table>
                <?php else: ?>
                    <p style="color:#888;">Belum ada riwayat pembayaran.</p>
                <?php endif; ?>

                <!-- Tombol Dokumen (Link ke fitur sebelumnya) -->
                <div style="margin-top:20px; text-align:right;">
                     <a href="#" class="umh-btn" style="border:1px solid #ccc; color:#555;">Lihat Dokumen Paspor</a>
                </div>
            </div>
        </div>
    <?php endforeach; else: ?>
        <p>Tidak ada booking aktif.</p>
    <?php endif; ?>

    <!-- MODAL KONFIRMASI BAYAR -->
    <div id="paymentModal" class="umh-modal">
        <div class="umh-modal-box">
            <span onclick="document.getElementById('paymentModal').style.display='none'" style="float:right; cursor:pointer; font-weight:bold;">✕</span>
            <h3 style="margin-top:0;">Konfirmasi Pembayaran</h3>
            <form action="<?php echo admin_url('admin-post.php'); ?>" method="post" enctype="multipart/form-data">
                <input type="hidden" name="action" value="umh_submit_payment">
                <input type="hidden" name="booking_id" id="modal-booking-id">
                <?php wp_nonce_field('umh_payment_nonce'); ?>

                <div style="margin-bottom:10px;">
                    <label style="display:block; font-weight:bold;">Transfer ke Bank</label>
                    <select name="bank_target" class="umh-form-control" style="width:100%; padding:8px;">
                        <?php foreach ($company_accounts as $acc): ?>
                            <option value="<?php echo esc_attr($acc->bank_name . ' - ' . $acc->account_number); ?>">
                                <?php echo esc_html($acc->bank_name . ' (' . $acc->account_number . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="margin-bottom:10px;">
                    <label style="display:block; font-weight:bold;">Nama Pengirim (Sesuai Rekening)</label>
                    <input type="text" name="sender_name" required style="width:100%; padding:8px;">
                </div>

                <div style="margin-bottom:10px;">
                    <label style="display:block; font-weight:bold;">Jumlah Transfer (Rp)</label>
                    <input type="number" name="amount" required style="width:100%; padding:8px;" placeholder="Contoh: 5000000">
                </div>

                <div style="margin-bottom:15px;">
                    <label style="display:block; font-weight:bold;">Bukti Transfer (Foto/Screenshot)</label>
                    <input type="file" name="proof_file" required accept="image/*,.pdf">
                </div>

                <button type="submit" class="umh-btn btn-primary" style="width:100%;">Kirim Konfirmasi</button>
            </form>
        </div>
    </div>

    <script>
        document.querySelectorAll('.btn-pay-confirm').forEach(btn => {
            btn.addEventListener('click', function() {
                document.getElementById('modal-booking-id').value = this.getAttribute('data-id');
                document.getElementById('paymentModal').style.display = 'block';
            });
        });
        
        window.onclick = function(event) {
            if (event.target == document.getElementById('paymentModal')) {
                document.getElementById('paymentModal').style.display = 'none';
            }
        }
    </script>
</div>