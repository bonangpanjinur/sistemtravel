<?php
// File: departures.php
// Location: templates/admin/departures.php
?>
<div class="wrap">
    <h1 class="wp-heading-inline">Manajemen Jadwal Keberangkatan</h1>
    <button class="page-title-action" onclick="openDepartureModal()">Buat Jadwal Baru</button>
    <hr class="wp-header-end">

    <div class="umh-stats-bar" style="background:#fff; padding:15px; margin-bottom:20px; border-left:4px solid #2271b1; box-shadow:0 1px 1px rgba(0,0,0,0.04);">
        <p style="margin:0;">
            <strong>Info:</strong> Pastikan Anda sudah membuat <em>Paket Umroh</em>, <em>Data Muthawif</em>, dan <em>Data Bus</em> di menu Master Data sebelum membuat jadwal.
        </p>
    </div>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>Paket</th>
                <th>Ketersediaan (Seat)</th>
                <th>Muthawif & Bus</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($departures)): ?>
                <?php foreach ($departures as $row): ?>
                    <?php 
                        // Visualisasi Progress Bar Kursi
                        $filled = $row->total_seats - $row->available_seats;
                        $percent = ($row->total_seats > 0) ? ($filled / $row->total_seats) * 100 : 0;
                        $barColor = ($percent >= 100) ? '#d63638' : '#46b450';
                    ?>
                    <tr>
                        <td>
                            <strong><?php echo date('d M Y', strtotime($row->departure_date)); ?></strong><br>
                            <span style="color:#666; font-size:12px;"><?php echo date('l', strtotime($row->departure_date)); ?></span>
                        </td>
                        <td>
                            <strong><?php echo esc_html($row->package_name); ?></strong>
                        </td>
                        <td>
                            <div style="font-weight:600; margin-bottom:3px;">
                                <?php echo $row->available_seats; ?> Tersedia / <?php echo $row->total_seats; ?> Total
                            </div>
                            <div style="background:#eee; width:100%; height:8px; border-radius:4px;">
                                <div style="background:<?php echo $barColor; ?>; width:<?php echo $percent; ?>%; height:8px; border-radius:4px;"></div>
                            </div>
                        </td>
                        <td>
                            <ul style="margin:0; font-size:12px;">
                                <li><strong>Ustadz:</strong> <?php echo $row->muthawif_id ? 'ID: #'.$row->muthawif_id : '<span style="color:#aaa;">-</span>'; ?></li>
                                <li><strong>Bus:</strong> <?php echo $row->bus_provider_id ? 'ID: #'.$row->bus_provider_id : '<span style="color:#aaa;">-</span>'; ?></li>
                            </ul>
                        </td>
                        <td>
                            <?php 
                                $statusClass = 'umh-status-open';
                                if($row->status === 'closed') $statusClass = 'umh-status-pending'; // Reuse class css
                                if($row->status === 'departed') $statusClass = 'umh-status-departed';
                            ?>
                            <span class="<?php echo $statusClass; ?>"><?php echo ucfirst($row->status); ?></span>
                        </td>
                        <td>
                            <div style="display:flex; gap:5px; flex-wrap:wrap;">
                                <button class="button" onclick='openDepartureModal(<?php echo json_encode($row); ?>)'>Edit</button>
                                
                                <!-- [NEW] Tombol Manifest -->
                                <a href="<?php echo admin_url('admin-post.php?action=umh_print_manifest&departure_id=' . $row->id); ?>" 
                                   target="_blank" 
                                   class="button button-secondary" title="Cetak Manifest">
                                   📄 Manifest
                                </a>

                                <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=umh_delete_departure&id=' . $row->id), 'umh_departure_nonce'); ?>" 
                                   class="button button-link-delete" 
                                   onclick="return confirm('Hapus jadwal ini? Pastikan belum ada jamaah terdaftar.')">Hapus</a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6">Belum ada jadwal keberangkatan aktif.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Modal Form (Code Modal sama seperti sebelumnya, disembunyikan agar hemat tempat) -->
<div id="departure-modal" class="umh-modal" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; background-color:rgba(0,0,0,0.5);">
    <div style="background-color:#fff; margin:5% auto; padding:20px; width:500px; border-radius:5px; box-shadow:0 4px 10px rgba(0,0,0,0.2);">
        <h3 id="modal-title" style="margin-top:0;">Buat Jadwal Baru</h3>
        <form action="<?php echo admin_url('admin-post.php'); ?>" method="post">
            <input type="hidden" name="action" value="umh_save_departure">
            <input type="hidden" name="id" id="dep-id" value="">
            <?php wp_nonce_field('umh_departure_nonce'); ?>
            <!-- Form fields sama seperti sebelumnya -->
            <div style="margin-bottom:15px;">
                <label style="font-weight:600;">Pilih Paket Umroh</label>
                <select name="package_id" id="dep-package" class="widefat" required>
                    <option value="">-- Pilih Paket --</option>
                    <?php foreach ($packages as $pkg): ?>
                        <option value="<?php echo $pkg->id; ?>"><?php echo esc_html($pkg->name); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="display:flex; gap:15px; margin-bottom:15px;">
                <div style="flex:1;">
                    <label style="font-weight:600;">Tanggal Berangkat</label>
                    <input type="date" name="departure_date" id="dep-date" class="widefat" required>
                </div>
                <div style="flex:1;">
                    <label style="font-weight:600;">Total Seat</label>
                    <input type="number" name="total_seats" id="dep-seats" class="widefat" required placeholder="45">
                </div>
            </div>
            <div style="margin-bottom:15px;">
                <label style="font-weight:600;">Status</label>
                <select name="status" id="dep-status" class="widefat">
                    <option value="open">Open</option>
                    <option value="closed">Closed</option>
                    <option value="departed">Departed</option>
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
    } else {
        document.getElementById('modal-title').innerText = 'Buat Jadwal Baru';
        document.getElementById('dep-id').value = '';
        document.getElementById('dep-package').value = '';
        document.getElementById('dep-date').value = '';
        document.getElementById('dep-seats').value = '45';
        document.getElementById('dep-status').value = 'open';
    }
    modal.style.display = 'block';
}
window.onclick = function(event) {
    if (event.target == document.getElementById('departure-modal')) {
        document.getElementById('departure-modal').style.display = "none";
    }
}
</script>