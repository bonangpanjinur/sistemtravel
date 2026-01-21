<?php
// Path: src/Utils/View.php

namespace App\Utils;

class View {
    /**
     * Render template dengan data.
     * @param string $path Path relatif terhadap folder templates (misal: 'admin/packages/index')
     * @param array $data Data yang akan dikirim ke view
     */
    public static function render($path, $data = []) {
        // Lokasi folder template
        $templatePath = plugin_dir_path(dirname(__DIR__, 2)) . 'templates/' . $path . '.php';

        if (!file_exists($templatePath)) {
            $error_msg = "View template not found: " . esc_html($path);
            error_log($error_msg . " at " . $templatePath);
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                return '<div class="notice notice-error"><p>' . $error_msg . '</p></div>';
            }
            return '<div class="notice notice-error"><p>Terjadi kesalahan saat memuat tampilan.</p></div>';
        }

        // Auto-escape data untuk keamanan XSS (kecuali yang ditandai raw)
        $data = self::escapeData($data);

        // Extract data menjadi variabel
        extract($data);

        // Start output buffering
        ob_start();
        try {
            include $templatePath;
        } catch (\Exception $e) {
            ob_end_clean();
            error_log("Error rendering template $path: " . $e->getMessage());
            return '<div class="notice notice-error"><p>Error rendering template.</p></div>';
        }
        return ob_get_clean();
    }

    /**
     * Render WordPress style tabs
     * @param array $tabs Array of ['id' => 'slug', 'label' => 'Title', 'url' => '...']
     * @param string $activeTab Current active tab ID
     */
    public static function renderTabs($tabs, $activeTab) {
        echo '<h2 class="nav-tab-wrapper">';
        foreach ($tabs as $tab) {
            $active = ($activeTab === $tab['id']) ? 'nav-tab-active' : '';
            echo sprintf(
                '<a href="%s" class="nav-tab %s">%s</a>',
                esc_url($tab['url']),
                esc_attr($active),
                esc_html($tab['label'])
            );
        }
        echo '</h2>';
    }

    /**
     * Rekursif escape data array/string
     */
    private static function escapeData($data) {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = self::escapeData($value);
            }
        } elseif (is_string($data)) {
            // Jangan escape jika sepertinya HTML yang aman (misal hasil wp_editor)
            // Ini pendekatan simpel, idealnya gunakan library purifier
            $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        }
        return $data;
    }
}