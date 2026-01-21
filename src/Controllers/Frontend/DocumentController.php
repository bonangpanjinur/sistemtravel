<?php
// src/Controllers/Frontend/DocumentController.php

namespace UmhMgmt\Controllers\Frontend;

use UmhMgmt\Core\Container;
use UmhMgmt\Repositories\BookingRepository;
use UmhMgmt\Services\AuditLogService;

class DocumentController {
    
    private $bookingRepo;

    public function __construct() {
        $this->bookingRepo = Container::get(BookingRepository::class);
        
        // Hook form submission
        add_action('admin_post_umh_upload_document', [$this, 'handleUploadSubmission']);
        add_action('admin_post_nopriv_umh_upload_document', [$this, 'handleUploadSubmission']);
    }

    public function handleUploadSubmission() {
        // 1. Security Check
        if (!is_user_logged_in()) {
            wp_redirect(home_url('/login'));
            exit;
        }

        if (!isset($_POST['umh_upload_doc_nonce']) || !wp_verify_nonce($_POST['umh_upload_doc_nonce'], 'umh_upload_doc_nonce')) {
            wp_die('Security check failed');
        }

        $bookingId = isset($_POST['booking_id']) ? absint($_POST['booking_id']) : 0;
        $passengerId = isset($_POST['passenger_id']) ? absint($_POST['passenger_id']) : 0;
        $docType = isset($_POST['doc_type']) ? sanitize_text_field($_POST['doc_type']) : ''; // e.g., 'passport', 'visa'
        
        // Validasi Kepemilikan (PENTING: Cek apakah user berhak upload untuk booking ini)
        $booking = $this->bookingRepo->find($bookingId);
        if (!$booking || $booking->customer_user_id != get_current_user_id()) {
             wp_die('Unauthorized access.');
        }

        // 2. File Handling
        if (isset($_FILES['document_file']) && !empty($_FILES['document_file']['name'])) {
            try {
                // Validasi error upload PHP standar
                if ($_FILES['document_file']['error'] !== UPLOAD_ERR_OK) {
                    throw new \Exception('Terjadi kesalahan saat mengupload file (Error Code: ' . $_FILES['document_file']['error'] . ')');
                }

                $fileUrl = $this->processSecureUpload($_FILES['document_file']);
                
                // Update Database (Simpan URL file)
                $this->updatePassengerDocument($passengerId, $docType, $fileUrl);
                
                // Audit Log
                if (class_exists(AuditLogService::class)) {
                    AuditLogService::log('upload_document', 'passenger', $passengerId, null, ['file' => $fileUrl, 'type' => $docType]);
                }

                wp_redirect(add_query_arg('upload_status', 'success', wp_get_referer()));
                exit;

            } catch (\Exception $e) {
                wp_die('Upload Gagal: ' . $e->getMessage());
            }
        } else {
             wp_redirect(add_query_arg('upload_status', 'empty', wp_get_referer()));
             exit;
        }
    }

    /**
     * Logic inti Secure Upload
     */
    private function processSecureUpload($file) {
        // 1. Validasi Ukuran (Max 2MB)
        if ($file['size'] > 2 * 1024 * 1024) {
            throw new \Exception("Ukuran file terlalu besar. Maksimal 2MB.");
        }

        // 2. Validasi MIME Type Asli (Server-Side Check)
        // Jangan percaya $_FILES['type'] dari browser, gunakan finfo
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime_type = $finfo->file($file['tmp_name']);
        
        $allowed_mimes = [
            'image/jpeg', 
            'image/png', 
            'image/jpg',
            'application/pdf'
        ];

        if (!in_array($mime_type, $allowed_mimes)) {
            throw new \Exception("Tipe file tidak valid! Terdeteksi: " . $mime_type . ". Hanya JPG, PNG, atau PDF yang diperbolehkan.");
        }

        // 3. Gunakan wp_handle_upload untuk keamanan WordPress standar
        // (Rename file, sanitize filename, taruh di folder yang benar)
        if (!function_exists('wp_handle_upload')) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
        }
        
        $upload_overrides = ['test_form' => false];
        
        // Tambahkan filter untuk memaksa pengecekan mime type WP (Layer 2)
        add_filter('upload_mimes', function($mimes) {
            return [
                'jpg|jpeg|jpe' => 'image/jpeg',
                'png'          => 'image/png',
                'pdf'          => 'application/pdf',
            ];
        });

        $movefile = wp_handle_upload($file, $upload_overrides);

        if ($movefile && !isset($movefile['error'])) {
            return $movefile['url'];
        } else {
            throw new \Exception($movefile['error']);
        }
    }

    private function updatePassengerDocument($passengerId, $docType, $fileUrl) {
        global $wpdb;
        $table = $wpdb->prefix . 'umh_booking_passengers';
        
        // Tentukan kolom mana yang diupdate berdasarkan tipe dokumen
        $column = '';
        if ($docType === 'passport') $column = 'passport_file_url'; 
        elseif ($docType === 'ktp') $column = 'ktp_file_url';
        elseif ($docType === 'photo') $column = 'photo_file_url';
        
        // Jika kolom valid, lakukan update
        if ($column) {
            $wpdb->update(
                $table,
                [$column => $fileUrl],
                ['id' => $passengerId],
                ['%s'],
                ['%d']
            );
        }
    }
}