<?php
// Folder: src/Services/
// File: AuditLogService.php

namespace UmhMgmt\Services;

class AuditLogService {
    
    /**
     * Mencatat aktivitas user ke database.
     * * @param string $action Jenis aksi (create, update, delete, login, export)
     * @param string $objectType Objek yang dimanipulasi (booking, package, payment)
     * @param int $objectId ID dari objek tersebut
     * @param array|null $oldValue Data sebelum perubahan (untuk update)
     * @param array|null $newValue Data setelah perubahan
     */
    public static function log($action, $objectType, $objectId, $oldValue = null, $newValue = null) {
        global $wpdb;
        $table = $wpdb->prefix . 'umh_audit_logs';
        
        $user_id = get_current_user_id();
        
        // Dapatkan IP Address yang valid (termasuk jika di belakang Cloudflare)
        $ip_address = $_SERVER['REMOTE_ADDR'];
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }

        $wpdb->insert($table, [
            'user_id'     => $user_id,
            'action'      => $action,
            'object_type' => $objectType,
            'object_id'   => $objectId,
            'old_value'   => $oldValue ? json_encode($oldValue) : null,
            'new_value'   => $newValue ? json_encode($newValue) : null,
            'ip_address'  => substr($ip_address, 0, 45),
            'user_agent'  => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255)
        ]);
    }
}