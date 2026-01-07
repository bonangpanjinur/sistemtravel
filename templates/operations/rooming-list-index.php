<?php
// File: rooming-list-index.php
// Location: templates/admin/operations/rooming-list-index.php
?>
<div class="wrap">
    <h1 class="wp-heading-inline">Rooming List Manager</h1>
    <hr class="wp-header-end">
    
    <div style="background:#fff; padding:20px; margin-top:15px; border:1px solid #ccd0d4;">
        <p>Pilih jadwal keberangkatan untuk mengatur pembagian kamar hotel jamaah.</p>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Tanggal Berangkat</th>
                    <th>Paket Umroh</th>
                    <th>Jumlah Jamaah (Paid)</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($departures)): foreach ($departures as $row): ?>
                    <tr>
                        <td><strong><?php echo date('d M Y', strtotime($row->departure_date)); ?></strong></td>
                        <td><?php echo esc_html($row->package_name); ?></td>
                        <td><?php echo esc_html($row->total_pax); ?> Orang</td>
                        <td>
                            <a href="<?php echo admin_url('admin.php?page=umh-rooming-list&departure_id=' . $row->id); ?>" class="button button-primary">
                                Kelola Kamar 🛏️
                            </a>
                        </td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="4">Belum ada jadwal keberangkatan aktif.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>