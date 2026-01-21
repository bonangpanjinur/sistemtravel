<?php
// templates/admin/settings.php

if (!defined('ABSPATH')) exit;

// Ambil nilai setting saat ini dari database
$paymentSettings = get_option('umh_payment_settings', []);
$activeGateway = get_option('umh_active_gateway', 'manual');
$generalSettings = get_option('umh_general_settings', [
    'company_name' => '',
    'company_address' => '',
    'company_phone' => '',
    'company_logo' => ''
]);
$notificationSettings = get_option('umh_notification_settings', [
    'email_sender_name' => '',
    'wa_api_url' => '',
    'wa_api_token' => '',
    'wa_msg_booking' => '',
    'wa_msg_payment' => ''
]);
$integrationSettings = get_option('umh_integration_settings', [
    'siskopatuh_api_key' => ''
]);

// Handle Form Submission (Satu handler untuk semua tab)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['umh_save_settings'])) {
    check_admin_referer('umh_save_settings_nonce');

    // 1. Simpan Tab UMUM
    $newGeneral = [
        'company_name' => sanitize_text_field($_POST['umh_company_name']),
        'company_address' => sanitize_textarea_field($_POST['umh_company_address']),
        'company_phone' => sanitize_text_field($_POST['umh_company_phone']),
        'company_logo' => sanitize_text_field($_POST['umh_company_logo']),
    ];
    update_option('umh_general_settings', $newGeneral);
    $generalSettings = $newGeneral;

    // 2. Simpan Tab PAYMENT
    if(isset($_POST['active_gateway'])) {
        $activeGateway = sanitize_text_field($_POST['active_gateway']);
        update_option('umh_active_gateway', $activeGateway);

        $newPayment = [
            'midtrans_server_key' => sanitize_text_field($_POST['midtrans_server_key']),
            'midtrans_client_key' => sanitize_text_field($_POST['midtrans_client_key']),
            'midtrans_mode' => sanitize_text_field($_POST['midtrans_mode']),
        ];
        update_option('umh_payment_settings', $newPayment);
        $paymentSettings = $newPayment;
    }

    // 3. Simpan Tab NOTIFICATION
    $newNotif = [
        'email_sender_name' => sanitize_text_field($_POST['umh_email_sender_name']),
        'wa_api_url' => sanitize_text_field($_POST['umh_wa_api_url']),
        'wa_api_token' => sanitize_text_field($_POST['umh_wa_api_token']), // Token biasanya panjang & karakter aneh, sanitize text ok
        'wa_msg_booking' => sanitize_textarea_field($_POST['umh_wa_msg_booking']),
        'wa_msg_payment' => sanitize_textarea_field($_POST['umh_wa_msg_payment']),
    ];
    update_option('umh_notification_settings', $newNotif);
    $notificationSettings = $newNotif;

    // 4. Simpan Tab INTEGRATION
    $newIntegration = [
        'siskopatuh_api_key' => sanitize_text_field($_POST['umh_siskopatuh_api_key']),
    ];
    update_option('umh_integration_settings', $newIntegration);
    $integrationSettings = $newIntegration;

    echo '<div class="notice notice-success is-dismissible"><p>Semua pengaturan berhasil disimpan.</p></div>';
}
?>

<div class="wrap umh-admin-wrapper">
    <h1>⚙️ Pengaturan Sistem Travel</h1>
    <hr class="wp-header-end">
    
    <div class="nav-tab-wrapper">
        <a href="#general" class="nav-tab nav-tab-active" onclick="switchTab(event, 'general')">Umum</a>
        <a href="#payment" class="nav-tab" onclick="switchTab(event, 'payment')">Pembayaran (Payment)</a>
        <a href="#notification" class="nav-tab" onclick="switchTab(event, 'notification')">Notifikasi</a>
        <a href="#integrations" class="nav-tab" onclick="switchTab(event, 'integrations')">Integrasi API</a>
    </div>

    <form method="post" action="">
        <?php wp_nonce_field('umh_save_settings_nonce'); ?>
        <input type="hidden" name="umh_save_settings" value="1">

        <div class="umh-settings-content" style="margin-top: 20px; background: #fff; padding: 20px; border: 1px solid #ccd0d4;">
            
            <!-- TAB UMUM -->
            <div id="general" class="tab-content">
                <h3>Identitas Perusahaan</h3>
                <p class="description">Informasi ini akan ditampilkan di Invoice, Kop Surat, dan Email.</p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="umh_company_name">Nama Travel</label></th>
                        <td><input name="umh_company_name" type="text" id="umh_company_name" value="<?php echo esc_attr($generalSettings['company_name']); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="umh_company_address">Alamat Kantor</label></th>
                        <td><textarea name="umh_company_address" id="umh_company_address" rows="3" class="large-text code"><?php echo esc_textarea($generalSettings['company_address']); ?></textarea></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="umh_company_phone">No. Telepon Official</label></th>
                        <td><input name="umh_company_phone" type="text" id="umh_company_phone" value="<?php echo esc_attr($generalSettings['company_phone']); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="umh_company_logo">URL Logo Perusahaan</label></th>
                        <td>
                            <input name="umh_company_logo" type="text" id="umh_company_logo" value="<?php echo esc_attr($generalSettings['company_logo']); ?>" class="large-text">
                            <p class="description">Paste URL logo (PNG/JPG) untuk ditampilkan di Invoice & Email.</p>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- TAB PAYMENT GATEWAY -->
            <div id="payment" class="tab-content" style="display:none;">
                <div class="card" style="padding: 20px; max-width: 800px; margin-bottom: 20px; border: 1px solid #ddd;">
                    <h3>💳 Metode Pembayaran Utama</h3>
                    <p class="description">Pilih gateway pembayaran yang akan digunakan oleh jemaah saat checkout.</p>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="active_gateway">Gateway Aktif</label></th>
                            <td>
                                <select name="active_gateway" id="active_gateway" class="regular-text">
                                    <option value="manual" <?php selected($activeGateway, 'manual'); ?>>Transfer Manual (Upload Bukti)</option>
                                    <option value="midtrans" <?php selected($activeGateway, 'midtrans'); ?>>Midtrans (Virtual Account, QRIS, CC)</option>
                                    <!-- Future: <option value="xendit">Xendit</option> -->
                                </select>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- SETTING MIDTRANS -->
                <div id="midtrans-settings" class="card payment-gateway-card" style="padding: 20px; max-width: 800px; border-left: 4px solid #0063d1; margin-bottom: 20px; background: #f9f9f9; <?php echo ($activeGateway !== 'midtrans') ? 'display:none;' : ''; ?>">
                    <div style="display:flex; align-items:center; margin-bottom:15px;">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/e/eb/Logo_midtrans.png" alt="Midtrans Logo" style="height:30px; margin-right:15px;">
                        <h3 style="margin:0;">Konfigurasi Midtrans Snap</h3>
                    </div>
                    <p>Dapatkan Server Key & Client Key di dashboard <a href="https://dashboard.midtrans.com" target="_blank">Midtrans</a>.</p>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="midtrans_mode">Mode Environment</label></th>
                            <td>
                                <select name="midtrans_mode" id="midtrans_mode">
                                    <option value="sandbox" <?php selected($paymentSettings['midtrans_mode'] ?? 'sandbox', 'sandbox'); ?>>Sandbox (Testing)</option>
                                    <option value="production" <?php selected($paymentSettings['midtrans_mode'] ?? 'sandbox', 'production'); ?>>Production (Live)</option>
                                </select>
                                <p class="description">Gunakan Sandbox untuk uji coba transaksi fiktif.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="midtrans_server_key">Server Key</label></th>
                            <td>
                                <input name="midtrans_server_key" type="password" id="midtrans_server_key" value="<?php echo esc_attr($paymentSettings['midtrans_server_key'] ?? ''); ?>" class="regular-text">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="midtrans_client_key">Client Key</label></th>
                            <td>
                                <input name="midtrans_client_key" type="text" id="midtrans_client_key" value="<?php echo esc_attr($paymentSettings['midtrans_client_key'] ?? ''); ?>" class="regular-text">
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- TAB: NOTIFICATION -->
            <div id="notification" class="tab-content" style="display:none;">
                <h3>WhatsApp Gateway & Email</h3>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="umh_email_sender_name">Nama Pengirim Email</label></th>
                        <td><input name="umh_email_sender_name" type="text" id="umh_email_sender_name" value="<?php echo esc_attr($notificationSettings['email_sender_name']); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th colspan="2"><hr></th>
                    </tr>
                    <tr>
                        <th scope="row"><label for="umh_wa_api_url">WA API URL (3rd Party)</label></th>
                        <td><input name="umh_wa_api_url" type="text" id="umh_wa_api_url" value="<?php echo esc_attr($notificationSettings['wa_api_url']); ?>" class="large-text" placeholder="https://api.whatsapp-provider.com/send"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="umh_wa_api_token">API Token</label></th>
                        <td><input name="umh_wa_api_token" type="password" id="umh_wa_api_token" value="<?php echo esc_attr($notificationSettings['wa_api_token']); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label>Template Pesan Booking</label></th>
                        <td>
                            <textarea name="umh_wa_msg_booking" rows="3" class="large-text"><?php echo esc_textarea($notificationSettings['wa_msg_booking']); ?></textarea>
                            <p class="description">Variabel tersedia: <code>{name}</code>, <code>{package}</code>, <code>{id}</code>, <code>{price}</code></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label>Template Pesan Lunas</label></th>
                        <td>
                            <textarea name="umh_wa_msg_payment" rows="3" class="large-text"><?php echo esc_textarea($notificationSettings['wa_msg_payment']); ?></textarea>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- TAB INTEGRASI -->
            <div id="integrations" class="tab-content" style="display:none;">
                <h3>Integrasi Pemerintah (SISKOPATUH)</h3>
                <p>Masukkan API Key dari Kementerian Agama jika Anda ingin mengaktifkan sinkronisasi otomatis.</p>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="umh_siskopatuh_api_key">Siskopatuh API Key</label></th>
                        <td><input name="umh_siskopatuh_api_key" type="password" id="umh_siskopatuh_api_key" value="<?php echo esc_attr($integrationSettings['siskopatuh_api_key']); ?>" class="large-text"></td>
                    </tr>
                </table>
            </div>

        </div>

        <p class="submit">
            <input type="submit" name="submit" id="submit" class="button button-primary" value="Simpan Perubahan">
        </p>
    </form>

    <script>
        // Simple Tab Switcher (Vanilla JS)
        function switchTab(evt, tabName) {
            evt.preventDefault(); // Mencegah jump to anchor
            var i, tabcontent, tablinks;
            
            // Sembunyikan semua konten tab
            tabcontent = document.getElementsByClassName("tab-content");
            for (i = 0; i < tabcontent.length; i++) {
                tabcontent[i].style.display = "none";
            }
            
            // Hapus class active dari semua link
            tablinks = document.getElementsByClassName("nav-tab");
            for (i = 0; i < tablinks.length; i++) {
                tablinks[i].className = tablinks[i].className.replace(" nav-tab-active", "");
            }
            
            // Tampilkan tab yang dipilih
            document.getElementById(tabName).style.display = "block";
            evt.currentTarget.className += " nav-tab-active";
        }

        // Toggle Gateway Settings based on Dropdown
        document.getElementById('active_gateway').addEventListener('change', function() {
            var selected = this.value;
            var cards = document.getElementsByClassName('payment-gateway-card');
            for(var i=0; i<cards.length; i++) {
                cards[i].style.display = 'none';
            }
            
            if(selected === 'midtrans') {
                document.getElementById('midtrans-settings').style.display = 'block';
            }
            // Logic for other gateways...
        });
    </script>
</div>