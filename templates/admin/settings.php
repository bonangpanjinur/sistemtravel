<?php
// File: templates/admin/settings.php
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'general';
?>

<div class="wrap umh-admin-wrapper">
    <h1 class="wp-heading-inline">⚙️ Pengaturan Sistem Travel</h1>
    <hr class="wp-header-end">

    <?php if (isset($_GET['status']) && $_GET['status'] == 'success'): ?>
        <div class="notice notice-success is-dismissible">
            <p>Pengaturan berhasil disimpan.</p>
        </div>
    <?php endif; ?>

    <h2 class="nav-tab-wrapper">
        <a href="?page=umh-settings&tab=general" class="nav-tab <?php echo $active_tab == 'general' ? 'nav-tab-active' : ''; ?>">Umum</a>
        <a href="?page=umh-settings&tab=payment" class="nav-tab <?php echo $active_tab == 'payment' ? 'nav-tab-active' : ''; ?>">Pembayaran (Payment)</a>
        <a href="?page=umh-settings&tab=notification" class="nav-tab <?php echo $active_tab == 'notification' ? 'nav-tab-active' : ''; ?>">Notifikasi</a>
        <a href="?page=umh-settings&tab=integration" class="nav-tab <?php echo $active_tab == 'integration' ? 'nav-tab-active' : ''; ?>">Integrasi API</a>
    </h2>

    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
        <input type="hidden" name="action" value="umh_save_settings">
        <?php wp_nonce_field('umh_save_settings_action', 'umh_settings_nonce'); ?>

        <div class="umh-settings-content" style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; margin-top: -1px;">
            
            <!-- TAB: GENERAL -->
            <?php if ($active_tab == 'general'): ?>
                <h3>Identitas Perusahaan</h3>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="umh_company_name">Nama Travel</label></th>
                        <td><input name="umh_company_name" type="text" id="umh_company_name" value="<?php echo esc_attr($settings['company_name']); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="umh_company_address">Alamat Kantor</label></th>
                        <td><textarea name="umh_company_address" id="umh_company_address" rows="3" class="large-text code"><?php echo esc_textarea($settings['company_address']); ?></textarea></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="umh_company_phone">No. Telepon Official</label></th>
                        <td><input name="umh_company_phone" type="text" id="umh_company_phone" value="<?php echo esc_attr($settings['company_phone']); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="umh_company_logo">URL Logo Perusahaan</label></th>
                        <td>
                            <input name="umh_company_logo" type="text" id="umh_company_logo" value="<?php echo esc_attr($settings['company_logo']); ?>" class="large-text">
                            <p class="description">Paste URL logo (PNG/JPG) untuk ditampilkan di Invoice & Email.</p>
                        </td>
                    </tr>
                </table>
            <?php endif; ?>

            <!-- TAB: PAYMENT -->
            <?php if ($active_tab == 'payment'): ?>
                <h3>Konfigurasi Midtrans Gateway</h3>
                <p>Dapatkan Server Key & Client Key di dashboard <a href="https://dashboard.midtrans.com" target="_blank">Midtrans</a>.</p>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="umh_midtrans_is_production">Mode Production</label></th>
                        <td>
                            <select name="umh_midtrans_is_production" id="umh_midtrans_is_production">
                                <option value="0" <?php selected($settings['midtrans_is_production'], 0); ?>>Sandbox (Test)</option>
                                <option value="1" <?php selected($settings['midtrans_is_production'], 1); ?>>Production (Live)</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="umh_midtrans_server_key">Server Key</label></th>
                        <td><input name="umh_midtrans_server_key" type="password" id="umh_midtrans_server_key" value="<?php echo esc_attr($settings['midtrans_server_key']); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="umh_midtrans_client_key">Client Key</label></th>
                        <td><input name="umh_midtrans_client_key" type="text" id="umh_midtrans_client_key" value="<?php echo esc_attr($settings['midtrans_client_key']); ?>" class="regular-text"></td>
                    </tr>
                </table>
            <?php endif; ?>

            <!-- TAB: NOTIFICATION -->
            <?php if ($active_tab == 'notification'): ?>
                <h3>WhatsApp Gateway & Email</h3>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="umh_email_sender_name">Nama Pengirim Email</label></th>
                        <td><input name="umh_email_sender_name" type="text" id="umh_email_sender_name" value="<?php echo esc_attr($settings['email_sender_name']); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th colspan="2"><hr></th>
                    </tr>
                    <tr>
                        <th scope="row"><label for="umh_wa_api_url">WA API URL (3rd Party)</label></th>
                        <td><input name="umh_wa_api_url" type="text" id="umh_wa_api_url" value="<?php echo esc_attr($settings['wa_api_url']); ?>" class="large-text" placeholder="https://api.whatsapp-provider.com/send"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="umh_wa_api_token">API Token</label></th>
                        <td><input name="umh_wa_api_token" type="password" id="umh_wa_api_token" value="<?php echo esc_attr($settings['wa_api_token']); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label>Template Pesan Booking</label></th>
                        <td>
                            <textarea name="umh_wa_msg_booking" rows="3" class="large-text"><?php echo esc_textarea($settings['wa_msg_booking']); ?></textarea>
                            <p class="description">Variabel tersedia: <code>{name}</code>, <code>{package}</code>, <code>{id}</code>, <code>{price}</code></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label>Template Pesan Lunas</label></th>
                        <td>
                            <textarea name="umh_wa_msg_payment" rows="3" class="large-text"><?php echo esc_textarea($settings['wa_msg_payment']); ?></textarea>
                        </td>
                    </tr>
                </table>
            <?php endif; ?>

            <!-- TAB: INTEGRATION -->
            <?php if ($active_tab == 'integration'): ?>
                <h3>Integrasi Pemerintah (SISKOPATUH)</h3>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="umh_siskopatuh_api_key">Siskopatuh API Key</label></th>
                        <td><input name="umh_siskopatuh_api_key" type="password" id="umh_siskopatuh_api_key" value="<?php echo esc_attr($settings['siskopatuh_api_key']); ?>" class="large-text"></td>
                    </tr>
                    <!-- Bisa ditambah endpoint URL jika dinamis -->
                </table>
            <?php endif; ?>

        </div>
        
        <p class="submit">
            <input type="submit" name="submit" id="submit" class="button button-primary" value="Simpan Perubahan">
        </p>
    </form>
</div>