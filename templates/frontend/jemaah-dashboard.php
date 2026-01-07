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

// Helper untuk mengambil penumpang per booking
function get_booking_passengers($booking_id) {
    global $wpdb;
    return $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}umh_booking_passengers WHERE booking_id = %d", 
        $booking_id
    ));
}
?>

<div class="umh-dashboard-container">
    <style>
        .umh-dashboard-container { font-family: sans-serif; max-width: 1000px; margin: 0 auto; color: #333; }
        .umh-header-profile { background: #fff; padding: 25px; border-radius: 8px; margin-bottom: 30px; border: 1px solid #e2e8f0; border-top: 4px solid #2271b1; display:flex; justify-content:space-between; align-items:center; }
        
        /* Card & Layout */
        .umh-booking-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; margin-bottom: 30px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
        .umh-card-header { background: #f7fafc; padding: 15px 20px; border-bottom: 1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center; }
        .umh-card-body { padding: 20px; }
        .umh-booking-id { font-weight: bold; color: #4a5568; }
        
        /* Badges */
        .status-badge { padding: 4px 10px; border-radius: 12px; font-size: 0.8rem; font-weight: bold; text-transform: uppercase; }
        .badge-pending { background: #fffaf0; color: #c05621; border: 1px solid #fbd38d; }
        .badge-paid { background: #f0fff4; color: #2f855a; border: 1px solid #9ae6b4; }
        .badge-pending_verification { background: #ebf8ff; color: #2b6cb0; border: 1px solid #bee3f8; }
        .badge-canceled { background: #fff5f5; color: #c53030; border: 1px solid #feb2b2; }
        .status-badge.status-pending { background: #fffaf0; color: #c05621; border: 1px solid #fbd38d; } 
        .status-badge.status-paid { background: #f0fff4; color: #2f855a; border: 1px solid #9ae6b4; } 
        
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
        .umh-btn-action { background: #3182ce; color: #fff; padding: 10px 18px; border-radius: 6px; text-decoration: none; display: inline-block; font-size: 0.9rem; font-weight: 600; cursor: pointer; border:none; transition: background 0.2s; } 
        .umh-btn-action:hover { background: #2b6cb0; color: #fff; }
        .umh-btn-outline { background: transparent; border: 1px solid #3182ce; color: #3182ce; padding: 9px 17px; border-radius: 6px; text-decoration: none; display: inline-block; font-size: 0.9rem; font-weight: 600; margin-right: 10px; cursor: pointer;}
        .umh-btn-outline:hover { background: #ebf8ff; }

        .umh-modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; overflow: auto; } 
        .umh-modal-box, .umh-modal-content { background: #fff; width: 450px; margin: 10vh auto; padding: 25px; border-radius: 8px; position: relative; border: 1px solid #888; } 
        .close-modal { color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer; }

        /* Grid Layout */
        .umh-info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 20px; }
        .umh-info-label { display: block; color: #718096; font-size: 0.85rem; margin-bottom: 4px; text-transform: uppercase; letter-spacing: 0.5px; }
        .umh-info-value { font-size: 1.1rem; font-weight: 600; color: #2d3748; }

        /* Document Table */
        .umh-doc-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .umh-doc-table th { text-align: left; background: #f7fafc; padding: 10px; font-size: 0.9rem; color: #4a5568; }
        .umh-doc-table td { border-bottom: 1px solid #eee; padding: 10px; font-size: 0.95rem; }
    </style>

    <!-- Header Profil -->
    <div class="umh-header-profile">
        <div>
            <h2 style="margin:0; font-size:1.8rem;">Dashboard Jemaah</h2>
            <p style="margin:5px 0; color:#666;">Selamat datang, <?php echo esc_html($user->display_name); ?></p>
        </div>
        <div style="text-align:right;">
             <span style="display:block; font-size:0.9rem; color:#718096;">Member ID</span>
             <strong style="font-size:1.2rem;"><?php echo esc_html($user->user_email); ?></strong>
             <br>
             <!-- Shortcode ID Card Digital -->
             <a href="#" onclick="alert('Fitur ID Card Digital ada di menu profil (Coming Soon)')" style="font-size:0.8rem; color:#2271b1;">[ Lihat ID Card ]</a>
        </div>
    </div>

    <!-- Alert Sukses Upload -->
    <?php if (isset($_GET['payment_status']) && $_GET['payment_status'] == 'submitted'): ?>
        <div style="background:#f0fff4; border:1px solid #9ae6b4; color:#2f855a; padding:15px; margin-bottom:20px; border-radius:6px;">
            ✅ Bukti pembayaran berhasil dikirim. Menunggu verifikasi admin.
        </div>
    <?php endif; ?>
    <?php if (isset($_GET['upload_status']) && $_GET['upload_status'] === 'success'): ?>
        <div style="background:#f0fff4; color:#2f855a; padding:15px; border-radius:6px; margin-bottom:20px; border:1px solid #9ae6b4;">
            ✅ Dokumen berhasil diunggah! Tim kami akan segera memverifikasi.
        </div>
    <?php endif; ?>

    <h3 style="font-size: 1.5rem; margin-bottom: 20px; color: #333; border-bottom: 2px solid #eee; padding-bottom: 10px;">📦 Pesanan Saya</h3>

    <?php if (!empty($bookings)): foreach ($bookings as $bk): ?>
        <div class="umh-booking-card">
            <div class="umh-card-header">
                <span class="umh-booking-id">BOOKING #<?php echo $bk->id; ?></span>
                <span class="status-badge badge-<?php echo $bk->status; ?>"><?php echo strtoupper($bk->status); ?></span>
            </div>
            <div class="umh-card-body">
                <!-- Info Paket Grid -->
                <div class="umh-info-grid">
                    <div>
                        <span class="umh-info-label">Paket Umroh</span>
                        <span class="umh-info-value"><?php echo esc_html($bk->package_name); ?></span>
                    </div>
                    <div>
                        <span class="umh-info-label">Keberangkatan</span>
                        <span class="umh-info-value"><?php echo date('d M Y', strtotime($bk->departure_date)); ?></span>
                    </div>
                    <div>
                        <span class="umh-info-label">Total Tagihan</span>
                        <span class="umh-info-value" style="color:#d63638;">Rp <?php echo number_format($bk->total_price, 0, ',', '.'); ?></span>
                    </div>
                    <div>
                        <span class="umh-info-label">Cabang</span>
                        <span class="umh-info-value"><?php echo esc_html($bk->branch_name ?? 'Pusat'); ?></span>
                    </div>
                </div>

                <!-- Bagian Pembayaran -->
                <hr style="border:0; border-top:1px solid #eee; margin:20px 0;">
                <h4 style="margin-top:0; color:#2c5282;">💳 Status & Konfirmasi Pembayaran</h4>

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

                <!-- Dokumen Section -->
                <div style="background:#fff; border:1px solid #eee; padding:15px; border-radius:6px; margin-bottom:20px; margin-top:20px;">
                    <h4 style="margin-top:0; margin-bottom:10px; font-size:1rem; color:#2c5282;">📂 Kelengkapan Dokumen Jamaah</h4>
                    <table class="umh-doc-table">
                        <thead>
                            <tr>
                                <th>Nama Jamaah</th>
                                <th>Paspor</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                                $passengers = get_booking_passengers($bk->id);
                                if (!empty($passengers)): 
                                    foreach ($passengers as $pax):
                                        // Cek kolom passport_file_url
                                        $has_passport = !empty($pax->passport_file_url); 
                            ?>
                                <tr>
                                    <td><?php echo esc_html($pax->name); ?></td>
                                    <td><?php echo esc_html($pax->passport_number); ?></td>
                                    <td>
                                        <?php if ($has_passport): ?>
                                            <span style="color:green; font-weight:bold;">✅ Terupload</span>
                                            <br><a href="<?php echo esc_url($pax->passport_file_url); ?>" target="_blank" style="font-size:0.8rem;">Lihat File</a>
                                        <?php else: ?>
                                            <span style="color:orange;">⏳ Belum Upload</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button type="button" class="umh-btn-action umh-btn-upload" 
                                            data-booking="<?php echo $bk->id; ?>" 
                                            data-passenger="<?php echo $pax->id; ?>"
                                            data-name="<?php echo esc_attr($pax->name); ?>">
                                            Upload Paspor
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; else: ?>
                                <tr><td colspan="4">Data penumpang tidak ditemukan.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div style="text-align: right; margin-top:20px;">
                    <?php if ($bk->status == 'paid' || $bk->status == 'completed' || $bk->status == 'confirmed'): ?>
                        <a href="#" class="umh-btn-outline">Download Invoice</a>
                        <a href="#" class="umh-btn-action">Unduh Tiket</a>
                        <?php
                        // Tombol Sertifikat (Hanya jika Paid/Completed)
                        global $wpdb;
                        $pax_list = $wpdb->get_results("SELECT id FROM {$wpdb->prefix}umh_booking_passengers WHERE booking_id = {$bk->id}");
                        
                        if($pax_list) {
                            foreach($pax_list as $p) {
                                echo '<a href="'.admin_url('admin-post.php?action=umh_download_certificate&booking_id='.$bk->id.'&pax_id='.$p->id).'" target="_blank" class="umh-btn-outline" style="margin-left:5px;">🏅 Sertifikat</a>';
                            }
                        }
                        ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; else: ?>
        <div style="text-align:center; padding: 60px; background: #fff; border: 1px dashed #cbd5e0; border-radius: 8px;">
            <p style="font-size:1.1rem; color:#4a5568;">Anda belum memiliki riwayat pemesanan paket umroh.</p>
            <a href="<?php echo home_url('/katalog-umroh'); ?>" class="umh-btn-action">Lihat Katalog Paket</a>
        </div>
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

    <!-- Modal Upload Dokumen -->
    <div id="uploadModal" class="umh-modal">
        <div class="umh-modal-content">
            <span class="close-modal" onclick="document.getElementById('uploadModal').style.display='none'">&times;</span>
            <h3 style="margin-top:0;">Upload Dokumen</h3>
            <p>Upload Paspor untuk jamaah: <strong id="modal-pax-name"></strong></p>
            
            <form action="<?php echo admin_url('admin-post.php'); ?>" method="post" enctype="multipart/form-data">
                <input type="hidden" name="action" value="umh_upload_document">
                <input type="hidden" name="booking_id" id="modal-upload-booking-id">
                <input type="hidden" name="passenger_id" id="modal-pax-id">
                <input type="hidden" name="doc_type" value="passport">
                <?php wp_nonce_field('umh_upload_doc_nonce'); ?>
                
                <div style="margin-bottom:15px;">
                    <label style="display:block; margin-bottom:5px;">Pilih File (JPG/PDF, Max 2MB)</label>
                    <input type="file" name="document_file" required accept=".jpg,.jpeg,.png,.pdf">
                </div>
                
                <button type="submit" class="umh-btn-action" style="width:100%;">Upload Sekarang</button>
            </form>
        </div>
    </div>

    <script>
        // Modal Payment Logic
        document.querySelectorAll('.btn-pay-confirm').forEach(btn => {
            btn.addEventListener('click', function() {
                document.getElementById('modal-booking-id').value = this.getAttribute('data-id');
                document.getElementById('paymentModal').style.display = 'block';
            });
        });
        
        // Modal Upload Document Logic
        document.querySelectorAll('.umh-btn-upload').forEach(button => {
            button.addEventListener('click', function() {
                document.getElementById('modal-upload-booking-id').value = this.getAttribute('data-booking');
                document.getElementById('modal-pax-id').value = this.getAttribute('data-passenger');
                document.getElementById('modal-pax-name').innerText = this.getAttribute('data-name');
                document.getElementById('uploadModal').style.display = "block";
            });
        });

        // Close Modals on Outside Click
        window.onclick = function(event) {
            if (event.target == document.getElementById('paymentModal')) {
                document.getElementById('paymentModal').style.display = 'none';
            }
            if (event.target == document.getElementById('uploadModal')) {
                document.getElementById('uploadModal').style.display = 'none';
            }
        }
    </script>
</div>