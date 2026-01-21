<?php

namespace App\Controllers\Admin;

// FIX: Menambahkan Import Class View yang hilang
use App\Utils\View;
use App\Interfaces\DatabaseInterface;

class SettingsController
{
    private $db;

    public function __construct(DatabaseInterface $db)
    {
        $this->db = $db;
    }

    public function index()
    {
        // Handle form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
            $this->saveSettings($_POST);
        }

        $settings = $this->getSettings();

        // Error sebelumnya terjadi di sini karena class View tidak ditemukan
        View::render('admin/settings', [
            'title' => 'System Settings',
            'settings' => $settings
        ]);
    }

    private function getSettings()
    {
        // Fetch settings from wp_options or custom table
        $defaults = [
            'company_name' => get_option('travel_company_name', ''),
            'company_address' => get_option('travel_company_address', ''),
            'company_phone' => get_option('travel_company_phone', ''),
            'company_email' => get_option('travel_company_email', ''),
            'currency' => get_option('travel_currency', 'IDR'),
            'logo_url' => get_option('travel_logo_url', '')
        ];

        return $defaults;
    }

    private function saveSettings($data)
    {
        // Verify nonce
        if (!isset($data['travel_settings_nonce']) || !wp_verify_nonce($data['travel_settings_nonce'], 'save_travel_settings')) {
            return;
        }

        // Save fields
        $fields = ['company_name', 'company_address', 'company_phone', 'company_email', 'currency', 'logo_url'];
        
        foreach ($fields as $field) {
            $key = 'travel_' . $field;
            if (isset($data[$key])) {
                update_option($key, sanitize_text_field($data[$key]));
            }
        }

        // Add success message
        add_settings_error('travel_settings', 'settings_updated', 'Settings Saved Successfully', 'success');
    }
}