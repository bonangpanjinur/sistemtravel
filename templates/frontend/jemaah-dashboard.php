<?php
// File: jemaah-dashboard.php
// Location: templates/frontend/jemaah-dashboard.php

/** @var object $user Data user WordPress */
/** @var array $bookings List booking milik user */

// Helper untuk mengambil penumpang per booking (Logic sederhana di view, idealnya di controller)
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
        .umh-header-profile { background: #f8f9fa; padding: 25px; border-radius: 8px; margin-bottom: 30px; border-left: 5px solid #2271b1; display:flex; justify-content:space-between; align-items:center;}
        .umh-section-title { font-size: 1.5rem; margin-bottom: 20px; color: #333; border-bottom: 2px solid #eee; padding-bottom: 10px; }
        
        /* Card Booking */
        .umh-booking-card { border: 1px solid #e2e8f0; border-radius: 8px; padding: 0; margin-bottom: 30px; background: #fff; box-shadow: 0 4px 6px rgba(0,0,0,0.05); overflow: hidden; }
        .umh-card-header { background: #f7fafc; padding: 15px 20px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; }
        .umh-card-body { padding: 20px; }
        .umh-booking-id { font-weight: bold; color: #4a5568; }
        
        /* Status Badges */
        .umh-badge-status { padding: 6px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
        .status-pending { background: #fffaf0; color: #c05621; border: 1px solid #fbd38d; }
        .status-paid { background: #f0fff4; color: #2f855a; border: 1px solid #9ae6b4; }
        
        /* Buttons */
        .umh-btn-action { background: #3182ce; color: #fff; padding: 10px 18px; border-radius: 6px; text-decoration: none; display: inline-block; font-size: 0.9rem; font-weight: 600; cursor: pointer; border:none; }
        .umh-btn-action:hover { background: #2b6cb0; color: #fff; }
        
        /* Document Table & Modal */
        .umh-doc-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .umh-doc-table th { text-align: left; background: #f7fafc; padding: 10px; font-size: 0.9rem; color: #4a5568; }
        .umh-doc-table td { border-bottom: 1px solid #eee; padding: 10px; font-size: 0.95rem; }
        .umh-modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.5); }
        .umh-modal-content { background-color: #fefefe; margin: 15% auto; padding: 20px; border: 1px solid #888; width: 400px; border-radius: 8px; }
        .close-modal { color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer; }
    </style>

    <!-- Header Profil (Sama seperti sebelumnya) -->
    <div class="umh-header-profile">
        <div>
            <h2 style="margin:0; font-size:1.8rem;">Assalamu'alaikum, <?php echo esc_html($user->display_name); ?></h2>
            <p style="margin:5px 0 0; color:#4a5568;">Member Area Jamaah Umroh & Haji</p>
        </div>
        <div style="text-align:right;">
             <span style="display:block; font-size:0.9rem; color:#718096;">Member ID</span>
             <strong style="font-size:1.2rem;"><?php echo esc_html($user->user_email); ?></strong>
        </div>
    </div>

    <!-- Alert Sukses Upload -->
    <?php if (isset($_GET['upload_status']) && $_GET['upload_status'] === 'success'): ?>
        <div style="background:#f0fff4; color:#2f855a; padding:15px; border-radius:6px; margin-bottom:20px; border:1px solid #9ae6b4;">
            ✅ Dokumen berhasil diunggah! Tim kami akan segera memverifikasi.
        </div>
    <?php endif; ?>

    <h3 class="umh-section-title">📦 Pesanan Saya</h3>
    
    <?php if (!empty($bookings)): ?>
        <?php foreach ($bookings as $bk): ?>
            <div class="umh-booking-card">
                <div class="umh-card-header">
                    <span class="umh-booking-id">ORDER #<?php echo esc_html($bk->id); ?></span>
                    <span class="umh-badge-status status-<?php echo esc_attr($bk->status); ?>">
                        <?php echo esc_html($bk->status); ?>
                    </span>
                </div>
                
                <div class="umh-card-body">
                    <!-- Info Booking Grid (Sama seperti sebelumnya) -->
                    <div class="umh-info-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 20px;">
                        <div>
                            <span style="display:block; color:#777; font-size:0.85rem;">Paket Umroh</span>
                            <strong><?php echo esc_html($bk->package_name); ?></strong>
                        </div>
                        <div>
                            <span style="display:block; color:#777; font-size:0.85rem;">Keberangkatan</span>
                            <strong><?php echo date('d M Y', strtotime($bk->departure_date)); ?></strong>
                        </div>
                        <div>
                             <span style="display:block; color:#777; font-size:0.85rem;">Tagihan</span>
                             <strong style="color:#d63638;">Rp <?php echo number_format($bk->total_price, 0, ',', '.'); ?></strong>
                        </div>
                    </div>

                    <!-- Dokumen Section -->
                    <div style="background:#fff; border:1px solid #eee; padding:15px; border-radius:6px; margin-bottom:20px;">
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
                                            // Cek kolom passport_file_url (perlu update skema DB dulu jika belum ada)
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
                                            <button type="button" class="button button-small umh-btn-upload" 
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
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>Belum ada booking.</p>
    <?php endif; ?>

    <!-- Modal Upload -->
    <div id="uploadModal" class="umh-modal">
        <div class="umh-modal-content">
            <span class="close-modal">&times;</span>
            <h3 style="margin-top:0;">Upload Dokumen</h3>
            <p>Upload Paspor untuk jamaah: <strong id="modal-pax-name"></strong></p>
            
            <form action="<?php echo admin_url('admin-post.php'); ?>" method="post" enctype="multipart/form-data">
                <input type="hidden" name="action" value="umh_upload_document">
                <input type="hidden" name="booking_id" id="modal-booking-id">
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
        // Simple Modal Logic
        var modal = document.getElementById("uploadModal");
        var span = document.getElementsByClassName("close-modal")[0];

        // Open Modal
        document.querySelectorAll('.umh-btn-upload').forEach(button => {
            button.addEventListener('click', function() {
                document.getElementById('modal-booking-id').value = this.getAttribute('data-booking');
                document.getElementById('modal-pax-id').value = this.getAttribute('data-passenger');
                document.getElementById('modal-pax-name').innerText = this.getAttribute('data-name');
                modal.style.display = "block";
            });
        });

        // Close Modal
        span.onclick = function() { modal.style.display = "none"; }
        window.onclick = function(event) {
            if (event.target == modal) { modal.style.display = "none"; }
        }
    </script>
</div>