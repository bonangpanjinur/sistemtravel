<?php
// File: DigitalIdController.php
// Location: src/Controllers/Frontend/DigitalIdController.php

namespace UmhMgmt\Controllers\Frontend;

use UmhMgmt\Utils\View;

class DigitalIdController {

    public function __construct() {
        // Shortcode: [umh_digital_id]
        add_shortcode('umh_digital_id', [$this, 'render_card']);
    }

    public function render_card() {
        if (!is_user_logged_in()) {
            return '<p>Silakan login untuk melihat ID Card.</p>';
        }

        $user = wp_get_current_user();
        
        // Tentukan Role Display
        $role_label = 'Jemaah';
        if (in_array('umh_agent', $user->roles)) $role_label = 'Mitra Agen';
        if (in_array('umh_staff', $user->roles)) $role_label = 'Staff Operasional';
        if (in_array('administrator', $user->roles)) $role_label = 'Administrator';

        // Generate Data QR Code (Format JSON terenkripsi sederhana atau ID string)
        // Format: UMH:USER_ID:ROLE (Contoh: UMH:123:umh_jemaah)
        $qr_data = "UMH:{$user->ID}:{$role_label}";
        $qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode($qr_data);

        // Ambil Foto Profil (Avatar)
        $avatar_url = get_avatar_url($user->ID, ['size' => 100]);

        ob_start();
        View::render('frontend/digital-id-card', [
            'user' => $user,
            'role_label' => $role_label,
            'qr_url' => $qr_url,
            'avatar_url' => $avatar_url
        ]);
        return ob_get_clean();
    }
}