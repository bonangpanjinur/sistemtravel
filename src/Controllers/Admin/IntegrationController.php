<?php
// File: IntegrationController.php
// Location: src/Controllers/Admin/IntegrationController.php

namespace UmhMgmt\Controllers\Admin;

use UmhMgmt\Utils\View;

class IntegrationController {

    public function __construct() {
        add_action('admin_menu', [$this, 'add_menu']);
        add_action('admin_post_umh_save_wa_settings', [$this, 'save_settings']);
        add_action('admin_post_umh_test_wa', [$this, 'test_message']);
    }

    public function add_menu() {
        add_submenu_page(
            'umh-dashboard',
            'Integrasi & Notifikasi',
            'Integrasi (WA)',
            'manage_options',
            'umh-integrations',
            [$this, 'render_page']
        );
    }

    public function save_settings() {
        check_admin_referer('umh_integration_nonce');
        if (!current_user_can('manage_options')) wp_die('Unauthorized');

        global $wpdb;
        $settings = [
            'wa_provider' => sanitize_text_field($_POST['wa_provider']),
            'wa_api_url'  => esc_url_raw($_POST['wa_api_url']),
            'wa_api_key'  => sanitize_text_field($_POST['wa_api_key']),
            'wa_msg_booking' => sanitize_textarea_field($_POST['wa_msg_booking']), // Template Pesan
            'wa_msg_payment' => sanitize_textarea_field($_POST['wa_msg_payment']),
        ];

        foreach ($settings as $key => $val) {
            $wpdb->replace(
                $wpdb->prefix . 'umh_settings',
                ['setting_key' => $key, 'setting_value' => $val],
                ['%s', '%s']
            );
        }

        wp_redirect(admin_url('admin.php?page=umh-integrations&msg=saved'));
        exit;
    }

    public function test_message() {
        // Logic test kirim WA (Simulasi)
        check_admin_referer('umh_integration_nonce');
        $phone = sanitize_text_field($_POST['test_phone']);
        
        // Simpan ke Outbox sebagai simulasi
        global $wpdb;
        $wpdb->insert($wpdb->prefix.'umh_wa_outbox', [
            'phone_number' => $phone,
            'message' => 'Tes Notifikasi Sistem Travel Umroh. Halo!',
            'status' => 'pending' // Nanti diproses oleh cron job atau service
        ]);

        wp_redirect(admin_url('admin.php?page=umh-integrations&msg=tested'));
        exit;
    }

    public function render_page() {
        global $wpdb;
        
        // Helper untuk ambil setting
        $get_opt = function($key) use ($wpdb) {
            return $wpdb->get_var($wpdb->prepare("SELECT setting_value FROM {$wpdb->prefix}umh_settings WHERE setting_key = %s", $key));
        };

        View::render('admin/integrations/settings', [
            'wa_provider' => $get_opt('wa_provider'),
            'wa_api_url'  => $get_opt('wa_api_url'),
            'wa_api_key'  => $get_opt('wa_api_key'),
            'wa_msg_booking' => $get_opt('wa_msg_booking'),
            'wa_msg_payment' => $get_opt('wa_msg_payment'),
        ]);
    }
}