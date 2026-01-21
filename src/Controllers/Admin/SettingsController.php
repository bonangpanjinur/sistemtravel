<?php
// File: src/Controllers/Admin/SettingsController.php

namespace App\Controllers\Admin;

use App\Utils\View;

class SettingsController {

    public function __construct() {
        add_action('admin_post_umh_save_settings', [$this, 'handleSaveSettings']);
    }

    public function index() {
        $tab = $_GET['tab'] ?? 'staff';

        $tabs = [
            ['id' => 'staff', 'label' => 'Staff', 'url' => admin_url('admin.php?page=travel-sys-settings-group&tab=staff')],
            ['id' => 'agents', 'label' => 'Agen', 'url' => admin_url('admin.php?page=travel-sys-settings-group&tab=agents')],
            ['id' => 'master', 'label' => 'Master Data', 'url' => admin_url('admin.php?page=travel-sys-settings-group&tab=master')],
            ['id' => 'integrations', 'label' => 'Integrasi', 'url' => admin_url('admin.php?page=travel-sys-settings-group&tab=integrations')],
        ];

        echo '<div class="wrap">';
        echo '<h1>Pengaturan</h1>';
        View::renderTabs($tabs, $tab);

        switch ($tab) {
            case 'agents':
                echo View::render('admin/agents/commissions'); // Fallback to commissions as list is missing
                break;
            case 'master':
                echo View::render('admin/master-data');
                break;
            case 'integrations':
                echo View::render('admin/integrations/settings');
                break;
            case 'staff':
            default:
                echo View::render('admin/settings');
                break;
        }
        echo '</div>';
    }

    public function handleSaveSettings() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        check_admin_referer('umh_save_settings_action', 'umh_settings_nonce');

        $fields = [
            'umh_company_name', 'umh_company_address', 'umh_company_phone', 'umh_company_logo',
            'umh_midtrans_server_key', 'umh_midtrans_client_key', 'umh_midtrans_is_production',
            'umh_wa_api_url', 'umh_wa_api_token', 'umh_email_sender_name',
            'umh_wa_msg_booking', 'umh_wa_msg_payment',
            'umh_siskopatuh_api_key'
        ];

        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                $value = ($_POST[$field]); 
                if ($field === 'umh_midtrans_is_production') {
                    $value = intval($value);
                } else {
                    $value = sanitize_text_field($value);
                    if (strpos($field, 'msg') !== false) {
                        $value = sanitize_textarea_field($_POST[$field]);
                    }
                }
                update_option($field, $value);
            }
        }

        wp_redirect(admin_url('admin.php?page=travel-sys-settings-group&status=success'));
        exit;
    }
}
