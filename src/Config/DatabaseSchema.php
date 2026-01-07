<?php
namespace UmhMgmt\Config;

class DatabaseSchema {
    public static function get_schema() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        return [
            "CREATE TABLE {$wpdb->prefix}umh_packages (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                description TEXT,
                price DECIMAL(15,2) NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                deleted_at DATETIME NULL DEFAULT NULL
            ) $charset_collate;",

            "CREATE TABLE {$wpdb->prefix}umh_departures (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                package_id BIGINT,
                departure_date DATE NOT NULL,
                available_seats INT DEFAULT 0,
                status VARCHAR(50) DEFAULT 'open',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (package_id) REFERENCES {$wpdb->prefix}umh_packages(id) ON DELETE SET NULL
            ) $charset_collate;",

            "CREATE TABLE {$wpdb->prefix}umh_bookings (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                departure_id BIGINT,
                customer_user_id BIGINT(20) UNSIGNED NULL,
                total_price DECIMAL(15,2) NOT NULL,
                status VARCHAR(50) DEFAULT 'pending',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                deleted_at DATETIME NULL DEFAULT NULL,
                FOREIGN KEY (departure_id) REFERENCES {$wpdb->prefix}umh_departures(id) ON DELETE SET NULL
            ) $charset_collate;",

            "CREATE TABLE {$wpdb->prefix}umh_booking_passengers (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                booking_id BIGINT,
                name VARCHAR(255) NOT NULL,
                passport_number VARCHAR(50),
                passport_expiry DATE,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (booking_id) REFERENCES {$wpdb->prefix}umh_bookings(id) ON DELETE CASCADE
            ) $charset_collate;",

            "CREATE TABLE {$wpdb->prefix}umh_jamaah (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                customer_user_id BIGINT(20) UNSIGNED NULL,
                name VARCHAR(255) NOT NULL,
                nik VARCHAR(20),
                phone VARCHAR(20),
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            ) $charset_collate;",

            "CREATE TABLE {$wpdb->prefix}umh_journal_entries (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                transaction_ref_id VARCHAR(50),
                account_code VARCHAR(20),
                description TEXT,
                debit DECIMAL(15,2) DEFAULT 0,
                credit DECIMAL(15,2) DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            ) $charset_collate;",

            "CREATE TABLE {$wpdb->prefix}umh_inventory_items (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                item_code VARCHAR(50) NOT NULL,
                item_name VARCHAR(255) NOT NULL,
                stock_qty INT DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            ) $charset_collate;",

            "CREATE TABLE {$wpdb->prefix}umh_hotels (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                location VARCHAR(255),
                rating INT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            ) $charset_collate;",

            "CREATE TABLE {$wpdb->prefix}umh_airlines (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                code VARCHAR(10),
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            ) $charset_collate;",

            "CREATE TABLE {$wpdb->prefix}umh_leads (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                phone VARCHAR(20),
                email VARCHAR(100),
                status VARCHAR(50) DEFAULT 'new',
                source VARCHAR(100),
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            ) $charset_collate;"
        ];
    }
}
