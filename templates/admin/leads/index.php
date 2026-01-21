<div class="wrap">
    <h1 class="wp-heading-inline">Sales Pipeline (Prospek)</h1>
    <a href="admin.php?page=umroh-leads-add" class="page-title-action">Tambah Baru</a>
    <hr class="wp-header-end">

    <!-- Statistik Card -->
    <div class="umh-stats-row" style="display:flex; gap:15px; margin-bottom:20px;">
        <?php 
        $summary = ['new' => 0, 'follow_up' => 0, 'closing' => 0, 'lost' => 0];
        foreach ($stats as $s) $summary[$s->status] = $s->count;
        ?>
        <div class="card" style="flex:1; padding:10px; border-left: 4px solid #0073aa;">
            <h3>Baru Masuk</h3>
            <span style="font-size:2em; font-weight:bold;"><?php echo $summary['new']; ?></span>
        </div>
        <div class="card" style="flex:1; padding:10px; border-left: 4px solid #e5a500;">
            <h3>Perlu Follow Up</h3>
            <span style="font-size:2em; font-weight:bold;"><?php echo $summary['follow_up']; ?></span>
        </div>
        <div class="card" style="flex:1; padding:10px; border-left: 4px solid #46b450;">
            <h3>Closing (Deal)</h3>
            <span style="font-size:2em; font-weight:bold;"><?php echo $summary['closing']; ?></span>
        </div>
    </div>

    <!-- Filter & Search -->
    <form method="get" style="margin-bottom: 10px;">
        <input type="hidden" name="page" value="umroh-leads">
        <select name="status">
            <option value="">Semua Status</option>
            <option value="new" <?php selected($current_status, 'new'); ?>>Baru</option>
            <option value="follow_up" <?php selected($current_status, 'follow_up'); ?>>Follow Up</option>
            <option value="closing" <?php selected($current_status, 'closing'); ?>>Closing</option>
            <option value="lost" <?php selected($current_status, 'lost'); ?>>Gagal</option>
        </select>
        <input type="search" name="s" placeholder="Cari Nama/HP..." value="<?php echo esc_attr($_GET['s'] ?? ''); ?>">
        <input type="submit" class="button" value="Filter">
    </form>

    <!-- Tabel Data -->
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>Nama</th>
                <th>Kontak</th>
                <th>Minat Paket</th>
                <th>Status</th>
                <th>Aksi Cepat</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($leads)): ?>
                <tr><td colspan="5">Belum ada data prospek.</td></tr>
            <?php else: foreach ($leads as $lead): ?>
                <tr>
                    <td>
                        <strong><?php echo esc_html($lead->name); ?></strong><br>
                        <small style="color:#777;">Sumber: <?php echo esc_html(strtoupper($lead->source)); ?></small>
                    </td>
                    <td>
                        <?php echo esc_html($lead->phone); ?><br>
                        <small><?php echo esc_html($lead->email); ?></small>
                    </td>
                    <td><?php echo esc_html($lead->interested_in); ?></td>
                    <td>
                        <?php 
                        $colors = [
                            'new' => '#0073aa', 
                            'follow_up' => '#e5a500', 
                            'closing' => '#46b450', 
                            'lost' => '#a00'
                        ];
                        $color = $colors[$lead->status] ?? '#777';
                        ?>
                        <span style="background:<?php echo $color; ?>; color:#fff; padding:3px 8px; border-radius:3px; font-size:11px;">
                            <?php echo strtoupper(str_replace('_', ' ', $lead->status)); ?>
                        </span>
                    </td>
                    <td>
                        <!-- Tombol WA -->
                        <?php 
                        $wa_phone = preg_replace('/^0/', '62', $lead->phone); 
                        $wa_text = urlencode("Assalamualaikum {$lead->name}, saya dari Travel Umrah mau menanyakan...");
                        ?>
                        <a href="https://wa.me/<?php echo $wa_phone; ?>?text=<?php echo $wa_text; ?>" target="_blank" class="button button-small" title="Chat WA">
                            <span class="dashicons dashicons-whatsapp" style="color:green; margin-top:3px;"></span> WA
                        </a>

                        <!-- Edit -->
                        <a href="admin.php?page=umroh-leads-add&id=<?php echo $lead->id; ?>" class="button button-small">Edit</a>

                        <!-- Convert (Hanya jika belum closing) -->
                        <?php if($lead->status !== 'closing'): ?>
                            <a href="<?php echo wp_nonce_url('admin-post.php?action=umh_convert_lead&id='.$lead->id, 'convert_lead'); ?>" class="button button-small button-primary" onclick="return confirm('Jadikan Jemaah booking?')">Daftarkan</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>