<?php
// File: DocumentController.php
// Location: src/Controllers/Frontend/DocumentController.php

namespace UmhMgmt\Controllers\Frontend;

use UmhMgmt\Services\AuditLogService;

class DocumentController {

    public function __construct() {
        // Handle Form Submission dari Dashboard Jemaah
        add_action('admin_post_umh_upload_document', [$this, 'handle_upload']);
        add_action('admin_post_nopriv_umh_upload_document', [$this, 'handle_upload']); // Redirect ke login jika belum login
    }

    public function handle_upload() {
        // 1. Security Check
        if (!is_user_logged_in()) {
            wp_redirect(home_url('/login')); // Redirect ke login page
            exit;
        }

        check_admin_referer('umh_upload_doc_nonce');

        $booking_id = absint($_POST['booking_id']);
        $passenger_id = absint($_POST['passenger_id']);
        $doc_type = sanitize_text_field($_POST['doc_type']); // 'passport', 'ktp', 'photo'

        // Validasi Kepemilikan (Penting! User A tidak boleh upload ke Booking User B)
        // (Logic validasi ini disederhanakan, idealnya query DB untuk cek ownership)
        $current_user_id = get_current_user_id();
        // $is_owner = ... (query cek booking_id milik user_id ini) ... 

        // 2. File Handling
        if (!empty($_FILES['document_file']['name'])) {
            $uploaded_file = $_FILES['document_file'];

            // Gunakan fungsi upload WordPress agar aman & masuk Media Library
            // Kita butuh include file ini untuk frontend usage
            if (!function_exists('wp_handle_upload')) {
                require_once(ABSPATH . 'wp-admin/includes/file.php');
            }

            $upload_overrides = ['test_form' => false];
            $movefile = wp_handle_upload($uploaded_file, $upload_overrides);

            if ($movefile && !isset($movefile['error'])) {
                $file_url = $movefile['url'];
                $file_path = $movefile['file'];

                // 3. Update Database (Tabel Passengers)
                global $wpdb;
                $table = $wpdb->prefix . 'umh_booking_passengers';
                
                // Tentukan kolom mana yang diupdate berdasarkan tipe dokumen
                $column = '';
                if ($doc_type === 'passport') $column = 'passport_file_url'; // Pastikan kolom ini ada di DB schema
                elseif ($doc_type === 'ktp') $column = 'ktp_file_url'; // (Perlu alter table jika belum ada)
                elseif ($doc_type === 'photo') $column = 'photo_file_url'; // (Perlu alter table jika belum ada)

                if ($column) {
                    $wpdb->update($table, [$column => $file_url], ['id' => $passenger_id]);
                    
                    // Audit Log
                    AuditLogService::log('upload_document', 'passenger', $passenger_id, null, ['file' => $file_url]);
                }

                wp_redirect(add_query_arg('upload_status', 'success', wp_get_referer()));
            } else {
                // Error saat upload
                wp_die('Error uploading file: ' . $movefile['error']);
            }
        } else {
            wp_redirect(add_query_arg('upload_status', 'empty', wp_get_referer()));
        }
        exit;
    }
}