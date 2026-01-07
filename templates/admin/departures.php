<div class="wrap">
    <h1 class="wp-heading-inline">Manajemen Keberangkatan (Departures)</h1>
    <button class="page-title-action" onclick="openDepartureModal()">Buat Jadwal Baru</button>
    <hr class="wp-header-end">

    <!-- Filter / Info Bar -->
    <div style="background:#fff; padding:15px; margin-bottom:20px; border:1px solid #ccd0d4; display:flex; gap:20px;">
        <div><strong>Total Jadwal Aktif:</strong> <?php echo count($departures); ?></div>
        <div><strong>Status:</strong> <span class="umh-status-open">Open</span> Siap Booking &nbsp; <span class="umh-status-closed" style="background:#ddd; color:#555; padding:2px 5px; border-radius:3px; font-size:11px;">Closed</span> Penuh/Tutup</div>
    </div>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>Tanggal Berangkat</th>
                <th>Paket Umroh</th>
                <th>Seat (Sisa / Total)</th>
                <th>Pembimbing (Muthawif)</th>
                <th>Bus</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($departures)): ?>
                <?php foreach ($departures as $row): ?>
                    <?php 
                        // Hitung persentase keterisian untuk visual bar
                        $filled = $row->total_seats - $row->available_seats;
                        $percent = ($row->total_seats > 0) ? ($filled / $row->total_seats) * 100 : 0;
                        $color = ($percent > 90) ? '#d63638' : '#46b450';
                    ?>
                    <tr>
                        <td>
                            <strong><?php echo date('d M Y', strtotime($row->departure_date)); ?></strong><br>
                            <small><?php echo date('l', strtotime($row->departure_date)); ?></small>
                        </td>
                        <td>
                            <a href="<?php echo admin_url('admin.php?page=umh-packages-add&id='.$row->package_id); ?>">
                                <?php echo esc_html($row->package_name); ?>
                            </a>
                        </td>
                        <td>
                            <div style="font-weight:bold; font-size:14px;">
                                <?php echo $row->available_seats; ?> / <?php echo $row->total_seats; ?>
                            </div>
                            <div style="background:#eee; height:6px; width:100%; margin-top:5px; border-radius:3px;">
                                <div style="background:<?php echo $color; ?>; height:6px; width:<?php echo $percent; ?>%; border-radius:3px;"></div>
                            </div>
                        </td>
                        <td>
                            <?php 
                                // Fetch nama muthawif jika ada (biasanya di query join, tapi kita handle display simple dulu)
                                echo $row->muthawif_id ? "ID: " . $row->muthawif_id : '<span style="color:#aaa;">- Belum set -</span>'; 
                            ?>
                        </td>
                        <td>
                             <?php echo $row->bus_provider_id ? "ID: " . $row->bus_provider_id : '<span style="color:#aaa;">- Belum set -</span>'; ?>
                        </td>
                        <td>
                            <span class="umh-status-<?php echo esc_attr($row->status); ?>">
                                <?php echo ucfirst($row->status); ?>
                            </span>
                        </td>
                        <td>
                            <button class="button" onclick='openDepartureModal(<?php echo json_encode($row); ?>)'>Edit</button>
                            <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=umh_delete_departure&id=' . $row->id), 'umh_departure_nonce'); ?>" 
                               class="button button-link-delete" 
                               onclick="return confirm('Hapus jadwal ini? Pastikan belum ada jamaah yang mendaftar.')">Hapus</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7">Belum ada jadwal keberangkatan. Silakan buat jadwal baru.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Modal Form -->
<div id="departure-modal" class="umh-modal" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; background-color:rgba(0,0,0,0.5);">
    <div style="background-color:#fff; margin:5% auto; padding:20px; width:500px; border-radius:5px; box-shadow:0 5px 15px rgba(0,0,0,0.3);">
        <h3 id="modal-title" style="margin-top:0;">Buat Jadwal Keberangkatan</h3>
        
        <form action="<?php echo admin_url('admin-post.php'); ?>" method="post">
            <input type="hidden" name="action" value="umh_save_departure">
            <input type="hidden" name="id" id="dep-id" value="">
            <?php wp_nonce_field('umh_departure_nonce'); ?>

            <div style="margin-bottom:15px;">
                <label style="font-weight:600;">Pilih Paket Umroh</label>
                <select name="package_id" id="dep-package" class="widefat" required>
                    <option value="">-- Pilih Paket --</option>
                    <?php foreach ($packages as $pkg): ?>
                        <option value="<?php echo $pkg->id; ?>"><?php echo esc_html($pkg->name); ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="description">Paket menentukan harga, hotel, dan maskapai.</p>
            </div>

            <div style="display:flex; gap:15px; margin-bottom:15px;">
                <div style="flex:1;">
                    <label style="font-weight:600;">Tanggal Berangkat</label>
                    <input type="date" name="departure_date" id="dep-date" class="widefat" required>
                </div>
                <div style="flex:1;">
                    <label style="font-weight:600;">Total Seat (Pax)</label>
                    <input type="number" name="total_seats" id="dep-seats" class="widefat" required placeholder="45">
                </div>
            </div>

            <div style="margin-bottom:15px;">
                <label style="font-weight:600;">Status Penjualan</label>
                <select name="status" id="dep-status" class="widefat">
                    <option value="open">Open (Buka Penjualan)</option>
                    <option value="closed">Closed (Tutup/Penuh)</option>
                    <option value="departed">Departed (Sudah Berangkat)</option>
                </select>
            </div>

            <hr style="border:0; border-top:1px solid #eee; margin:15px 0;">
            <h4 style="margin:0 0 10px;">Penugasan Operasional (Opsional)</h4>

            <div style="margin-bottom:10px;">
                <label>Pembimbing (Muthawif)</label>
                <select name="muthawif_id" id="dep-muthawif" class="widefat">
                    <option value="">-- Belum Ditentukan --</option>
                    <?php foreach ($muthawifs as $m): ?>
                        <option value="<?php echo $m->id; ?>"><?php echo esc_html($m->name); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="margin-bottom:20px;">
                <label>Transportasi Bus</label>
                <select name="bus_provider_id" id="dep-bus" class="widefat">
                    <option value="">-- Belum Ditentukan --</option>
                    <?php foreach ($buses as $b): ?>
                        <option value="<?php echo $b->id; ?>"><?php echo esc_html($b->company_name . ' (' . $b->bus_type . ')'); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="text-align:right;">
                <button type="button" class="button" onclick="document.getElementById('departure-modal').style.display='none'">Batal</button>
                <button type="submit" class="button button-primary">Simpan Jadwal</button>
            </div>
        </form>
    </div>
</div>

<script>
function openDepartureModal(data = null) {
    const modal = document.getElementById('departure-modal');
    
    if (data) {
        document.getElementById('modal-title').innerText = 'Edit Jadwal';
        document.getElementById('dep-id').value = data.id;
        document.getElementById('dep-package').value = data.package_id;
        document.getElementById('dep-date').value = data.departure_date;
        document.getElementById('dep-seats').value = data.total_seats;
        document.getElementById('dep-status').value = data.status;
        document.getElementById('dep-muthawif').value = data.muthawif_id || '';
        document.getElementById('dep-bus').value = data.bus_provider_id || '';
    } else {
        document.getElementById('modal-title').innerText = 'Buat Jadwal Baru';
        document.getElementById('dep-id').value = '';
        document.getElementById('dep-package').value = '';
        // Default date today + 30 days
        const date = new Date();
        date.setDate(date.getDate() + 30);
        document.getElementById('dep-date').value = date.toISOString().split('T')[0];
        document.getElementById('dep-seats').value = '45';
        document.getElementById('dep-status').value = 'open';
        document.getElementById('dep-muthawif').value = '';
        document.getElementById('dep-bus').value = '';
    }
    modal.style.display = 'block';
}

// Close modal on outside click
window.onclick = function(event) {
    const modal = document.getElementById('departure-modal');
    if (event.target == modal) {
        modal.style.display = "none";
    }
}
</script>