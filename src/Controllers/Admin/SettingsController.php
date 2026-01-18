<?php
// File: src/Controllers/Admin/SettingsController.php

namespace UmhMgmt\Controllers\Admin;

use UmhMgmt\Utils\View;

class SettingsController {

    public function __construct() {
        add_action('admin_menu', [$this, 'registerMenu']);
        add_action('admin_post_umh_save_settings', [$this, 'handleSaveSettings']);
    }

    public function registerMenu() {
        // Tambahkan submenu di bawah menu utama plugin "Travel Management"
        // Asumsi slug parent menu utama adalah 'umroh-management'
        add_submenu_page(
            'umroh-management', 
            'Pengaturan Sistem', 
            'Pengaturan', 
            'manage_options', 
            'umh-settings', 
            [$this, 'render']
        );
    }

    public function render() {
        // Ambil semua opsi yang dibutuhkan
        $settings = [
            // General
            'company_name' => get_option('umh_company_name', get_bloginfo('name')),
            'company_address' => get_option('umh_company_address', ''),
            'company_phone' => get_option('umh_company_phone', ''),
            'company_logo' => get_option('umh_company_logo', ''), // URL Logo
            
            // Payment (Midtrans)
            'midtrans_server_key' => get_option('umh_midtrans_server_key', ''),
            'midtrans_client_key' => get_option('umh_midtrans_client_key', ''),
            'midtrans_is_production' => get_option('umh_midtrans_is_production', 0),
            
            // Notification (WA & Email)
            'wa_api_url' => get_option('umh_wa_api_url', ''),
            'wa_api_token' => get_option('umh_wa_api_token', ''),
            'email_sender_name' => get_option('umh_email_sender_name', get_bloginfo('name')),
            'wa_msg_booking' => get_option('umh_wa_msg_booking', "Halo {name}, booking paket {package} berhasil. Silakan lakukan pembayaran."),
            'wa_msg_payment' => get_option('umh_wa_msg_payment', "Terima kasih {name}, pembayaran sebesar Rp {amount} telah kami terima."),

            // Integrations (Siskopatuh)
            'siskopatuh_api_key' => get_option('umh_siskopatuh_api_key', ''),
        ];

        View::render('admin/settings', ['settings' => $settings]);
    }

    public function handleSaveSettings() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        check_admin_referer('umh_save_settings_action', 'umh_settings_nonce');

        // List field yang diizinkan untuk disimpan
        $fields = [
            'umh_company_name', 'umh_company_address', 'umh_company_phone', 'umh_company_logo',
            'umh_midtrans_server_key', 'umh_midtrans_client_key', 'umh_midtrans_is_production',
            'umh_wa_api_url', 'umh_wa_api_token', 'umh_email_sender_name',
            'umh_wa_msg_booking', 'umh_wa_msg_payment',
            'umh_siskopatuh_api_key'
        ];

        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                // Sanitasi dasar
                $value = ($_POST[$field]); 
                if ($field === 'umh_midtrans_is_production') {
                    $value = intval($value);
                } else {
                    $value = sanitize_text_field($value);
                    // Pengecualian untuk text area atau HTML jika perlu
                    if (strpos($field, 'msg') !== false) {
                        $value = sanitize_textarea_field($_POST[$field]);
                    }
                }
                update_option($field, $value);
            }
        }

        // Redirect kembali dengan pesan sukses
        wp_redirect(admin_url('admin.php?page=umh-settings&status=success'));
        exit;
    }
}