<?php
// File: settings.php
// Location: templates/admin/integrations/settings.php
?>
<div class="wrap">
    <h1 class="wp-heading-inline">Integrasi WhatsApp Gateway</h1>
    <hr class="wp-header-end">

    <?php if (isset($_GET['msg']) && $_GET['msg'] == 'saved'): ?>
        <div class="notice notice-success is-dismissible"><p>Pengaturan berhasil disimpan.</p></div>
    <?php endif; ?>

    <div style="display:grid; grid-template-columns: 2fr 1fr; gap: 20px;">
        
        <!-- Kolom Kiri: Form Settings -->
        <div style="background:#fff; padding:20px; border:1px solid #ccd0d4;">
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <input type="hidden" name="action" value="umh_save_wa_settings">
                <?php wp_nonce_field('umh_integration_nonce'); ?>

                <h2 style="margin-top:0;">Konfigurasi API</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">Provider</th>
                        <td>
                            <select name="wa_provider" class="regular-text">
                                <option value="fonnte" <?php selected($wa_provider, 'fonnte'); ?>>Fonnte (Rekomendasi)</option>
                                <option value="wablas" <?php selected($wa_provider, 'wablas'); ?>>Wablas</option>
                                <option value="twilio" <?php selected($wa_provider, 'twilio'); ?>>Twilio</option>
                                <option value="custom" <?php selected($wa_provider, 'custom'); ?>>Custom Endpoint</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">API URL (Endpoint)</th>
                        <td>
                            <input type="url" name="wa_api_url" value="<?php echo esc_attr($wa_api_url); ?>" class="regular-text" placeholder="https://api.fonnte.com/send">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">API Key / Token</th>
                        <td>
                            <input type="password" name="wa_api_key" value="<?php echo esc_attr($wa_api_key); ?>" class="regular-text">
                        </td>
                    </tr>
                </table>

                <h2 style="margin-top:20px;">Template Pesan Otomatis</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">Booking Baru (Ke Jemaah)</th>
                        <td>
                            <textarea name="wa_msg_booking" rows="4" class="large-text code"><?php echo esc_textarea($wa_msg_booking ?: "Assalamu'alaikum {name}, Terima kasih telah mendaftar paket {package}. Booking ID Anda: #{id}. Silakan lakukan pembayaran."); ?></textarea>
                            <p class="description">Variabel: <code>{name}</code>, <code>{package}</code>, <code>{id}</code>, <code>{price}</code></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Pembayaran Diterima</th>
                        <td>
                            <textarea name="wa_msg_payment" rows="4" class="large-text code"><?php echo esc_textarea($wa_msg_payment ?: "Alhamdulillah, pembayaran untuk Booking #{id} sebesar Rp {amount} telah kami terima. Status: LUNAS."); ?></textarea>
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <input type="submit" name="submit" id="submit" class="button button-primary" value="Simpan Perubahan">
                </p>
            </form>
        </div>

        <!-- Kolom Kanan: Test Sender -->
        <div style="background:#fff; padding:20px; border:1px solid #ccd0d4; height:fit-content;">
            <h3 style="margin-top:0;">Tes Koneksi</h3>
            <p>Kirim pesan tes untuk memastikan API Key valid.</p>
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <input type="hidden" name="action" value="umh_test_wa">
                <?php wp_nonce_field('umh_integration_nonce'); ?>
                
                <label>Nomor Tujuan (Format 62xxx)</label>
                <input type="text" name="test_phone" class="widefat" placeholder="628123456789" required style="margin-bottom:10px;">
                
                <button type="submit" class="button button-secondary">Kirim Pesan Tes</button>
            </form>
            
            <hr>
            <h4>Log Pesan Terakhir (Outbox)</h4>
            <?php
                global $wpdb;
                $logs = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}umh_wa_outbox ORDER BY created_at DESC LIMIT 5");
                if($logs):
                    echo '<ul style="font-size:11px; color:#555;">';
                    foreach($logs as $l) {
                        $status_color = ($l->status == 'sent') ? 'green' : 'orange';
                        echo "<li><strong>{$l->phone_number}</strong>: <span style='color:$status_color'>{$l->status}</span><br>".substr($l->message, 0, 30)."...</li>";
                    }
                    echo '</ul>';
                else:
                    echo '<p style="font-size:11px;">Belum ada log.</p>';
                endif;
            ?>
        </div>
    </div>
</div>