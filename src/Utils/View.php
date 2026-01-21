<?php
// Path: src/Utils/View.php

namespace UmrahManagement\Utils;

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
            // Fallback error handler
            if (defined('WP_DEBUG') && WP_DEBUG) {
                echo "View template not found: " . esc_html($templatePath);
            }
            return;
        }

        // Auto-escape data untuk keamanan XSS (kecuali yang ditandai raw)
        $data = self::escapeData($data);

        // Extract data menjadi variabel
        extract($data);

        // Start output buffering
        ob_start();
        include $templatePath;
        return ob_get_clean();
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