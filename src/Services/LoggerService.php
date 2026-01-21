<?php
// Path: src/Services/LoggerService.php

namespace App\Services;

class LoggerService {
    private $logFile;

    public function __construct() {
        // Simpan log di folder plugin/logs (pastikan folder ini writable atau gunakan folder uploads WP)
        $uploadDir = wp_upload_dir();
        $this->logFile = $uploadDir['basedir'] . '/umroh-logs/error.log';
        
        // Pastikan direktori ada
        if (!file_exists(dirname($this->logFile))) {
            mkdir(dirname($this->logFile), 0755, true);
        }
    }

    public function info($message, $context = []) {
        $this->write('INFO', $message, $context);
    }

    public function error($message, $context = []) {
        $this->write('ERROR', $message, $context);
    }

    public function warning($message, $context = []) {
        $this->write('WARNING', $message, $context);
    }

    private function write($level, $message, $context = []) {
        $timestamp = current_time('mysql');
        $contextStr = !empty($context) ? json_encode($context) : '';
        
        // Format: [2025-01-21 10:00:00] [ERROR] Pesan error {"data":"tambahan"}
        $logLine = sprintf("[%s] [%s] %s %s" . PHP_EOL, $timestamp, $level, $message, $contextStr);

        // Append ke file log
        // Gunakan error_log bawaan WP juga sebagai cadangan jika file write gagal
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("UmrohApp $level: $message");
        }
        
        @file_put_contents($this->logFile, $logLine, FILE_APPEND);
    }
}