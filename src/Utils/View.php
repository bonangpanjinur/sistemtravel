<?php
// Folder: sistemtravel/src/Utils/
// File: View.php

namespace UmhMgmt\Utils;

class View {
    /**
     * Render template dengan dukungan Theme Override.
     * Urutan prioritas:
     * 1. Folder Theme: /wp-content/themes/temaumroh/umroh-templates/{file}.php
     * 2. Folder Plugin: /wp-content/plugins/sistemtravel/templates/{file}.php
     */
    public static function render($template, $data = []) {
        extract($data);

        // 1. Cek Override di Tema (Prioritas Utama)
        // Lokasi: themes/nama-tema/umroh-templates/nama-file.php
        $theme_template = get_template_directory() . '/umroh-templates/' . $template . '.php';
        
        if (file_exists($theme_template)) {
            include $theme_template;
            return;
        }

        // 2. Fallback ke Template Bawaan Plugin
        $plugin_template = UMH_PLUGIN_DIR . 'templates/' . $template . '.php';
        
        if (file_exists($plugin_template)) {
            include $plugin_template;
        } else {
            // Opsional: Tampilkan error jika file benar-benar tidak ada di keduanya
            if (defined('WP_DEBUG') && WP_DEBUG) {
                echo "<p style='color:red;'>View Template not found: {$template}</p>";
            }
        }
    }
}