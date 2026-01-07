<?php
// File: commissions.php
// Location: templates/admin/agents/commissions.php
?>
<div class="wrap">
    <h1 class="wp-heading-inline">Manajemen Komisi Agen</h1>
    <hr class="wp-header-end">

    <!-- Statistik Bar -->
    <div style="display:flex; gap:20px; margin-bottom:20px; background:#fff; padding:20px; border:1px solid #ccd0d4; border-left:4px solid #2271b1;">
        <div>
            <span style="display:block; color:#666; font-size:12px;">PENDING (Butuh Verifikasi)</span>
            <strong style="font-size:20px; color:#d63638;">Rp <?php echo number_format($stats->pending_total ?? 0, 0, ',', '.'); ?></strong>
        </div>
        <div style="border-left:1px solid #ddd; padding-left:20px;">
            <span style="display:block; color:#666; font-size:12px;">SIAP DIBAYAR (Verified)</span>
            <strong style="font-size:20px; color:#e6a700;">Rp <?php echo number_format($stats->verified_total ?? 0, 0, ',', '.'); ?></strong>
        </div>
        <div style="border-left:1px solid #ddd; padding-left:20px;">
            <span style="display:block; color:#666; font-size:12px;">SUDAH DIBAYARKAN (Paid)</span>
            <strong style="font-size:20px; color:#46b450;">Rp <?php echo number_format($stats->paid_total ?? 0, 0, ',', '.'); ?></strong>
        </div>
    </div>

    <!-- Filter -->
    <ul class="subsubsub">
        <li class="all"><a href="admin.php?page=umh-agent-commissions" class="<?php echo empty($current_status) ? 'current' : ''; ?>">Semua</a> |</li>
        <li class="pending"><a href="admin.php?page=umh-agent-commissions&status=pending" class="<?php echo $current_status == 'pending' ? 'current' : ''; ?>">Pending</a> |</li>
        <li class="verified"><a href="admin.php?page=umh-agent-commissions&status=verified" class="<?php echo $current_status == 'verified' ? 'current' : ''; ?>">Siap Bayar</a> |</li>
        <li class="paid"><a href="admin.php?page=umh-agent-commissions&status=paid" class="<?php echo $current_status == 'paid' ? 'current' : ''; ?>">Selesai (Paid)</a></li>
    </ul>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th width="120">Tanggal</th>
                <th>Agen</th>
                <th>Sumber Booking</th>
                <th>Nominal Komisi</th>
                <th>Status</th>
                <th width="150">Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($commissions)): ?>
                <?php foreach ($commissions as $row): ?>
                    <tr>
                        <td>
                            <?php echo date('d M Y', strtotime($row->created_at)); ?><br>
                            <small style="color:#888;"><?php echo date('H:i', strtotime($row->created_at)); ?></small>
                        </td>
                        <td>
                            <strong><?php echo esc_html($row->agent_name); ?></strong><br>
                            <a href="mailto:<?php echo esc_attr($row->agent_email); ?>"><?php echo esc_html($row->agent_email); ?></a>
                        </td>
                        <td>
                            Booking #<?php echo $row->booking_id; ?><br>
                            <small>Paket: <?php echo esc_html($row->package_name); ?></small><br>
                            <small>Omzet: Rp <?php echo number_format($row->booking_amount, 0, ',', '.'); ?></small>
                        </td>
                        <td>
                            <strong style="color:#2271b1; font-size:14px;">
                                Rp <?php echo number_format($row->amount, 0, ',', '.'); ?>
                            </strong>
                        </td>
                        <td>
                            <?php 
                                $bg_color = '#eee'; $text_color = '#555';
                                if ($row->status == 'pending') { $bg_color = '#fcf0f1'; $text_color = '#d63638'; }
                                if ($row->status == 'verified') { $bg_color = '#fff8e5'; $text_color = '#996800'; }
                                if ($row->status == 'paid') { $bg_color = '#edfaef'; $text_color = '#46b450'; }
                            ?>
                            <span style="background:<?php echo $bg_color; ?>; color:<?php echo $text_color; ?>; padding:4px 8px; border-radius:4px; font-weight:bold; font-size:11px; text-transform:uppercase;">
                                <?php echo esc_html($row->status); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($row->status == 'pending'): ?>
                                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="display:inline;">
                                    <input type="hidden" name="action" value="umh_verify_commission">
                                    <input type="hidden" name="id" value="<?php echo $row->id; ?>">
                                    <?php wp_nonce_field('umh_commission_action'); ?>
                                    <button type="submit" class="button button-secondary" onclick="return confirm('Verifikasi komisi ini valid?')">Verifikasi</button>
                                </form>
                            <?php elseif ($row->status == 'verified'): ?>
                                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="display:inline;">
                                    <input type="hidden" name="action" value="umh_pay_commission">
                                    <input type="hidden" name="id" value="<?php echo $row->id; ?>">
                                    <?php wp_nonce_field('umh_commission_action'); ?>
                                    <button type="submit" class="button button-primary" onclick="return confirm('Tandai sudah dibayar?')">Bayar (Payout)</button>
                                </form>
                            <?php else: ?>
                                <span class="dashicons dashicons-yes" style="color:#46b450;"></span> Selesai
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6">Belum ada data komisi.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>