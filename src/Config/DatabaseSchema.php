<?php
// Folder: src/Config/
// File: DatabaseSchema.php

namespace UmhMgmt\Config;

class DatabaseSchema {
    public static function get_schema() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        return [
            // --- 1. CORE & MASTER DATA ---

            "CREATE TABLE {$wpdb->prefix}umh_branches (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                address TEXT,
                phone VARCHAR(20),
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            ) $charset_collate;",

            // Tabel Hotels (Updated: Support Foto & Maps)
            "CREATE TABLE {$wpdb->prefix}umh_hotels (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                location VARCHAR(255),
                rating INT,
                description TEXT,
                image_url TEXT,
                map_embed_code TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            ) $charset_collate;",

            "CREATE TABLE {$wpdb->prefix}umh_airlines (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                code VARCHAR(10),
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            ) $charset_collate;",

            // [NEW] Master Data Muthawif (Pembimbing Ibadah)
            "CREATE TABLE {$wpdb->prefix}umh_muthawifs (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                phone_saudi VARCHAR(20),
                phone_indo VARCHAR(20),
                certification VARCHAR(100),
                rating DECIMAL(3,2) DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            ) $charset_collate;",

            // [NEW] Master Data Transportasi Bus (Bus Provider)
            "CREATE TABLE {$wpdb->prefix}umh_bus_providers (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                company_name VARCHAR(255) NOT NULL,
                contact_person VARCHAR(100),
                phone VARCHAR(20),
                bus_type VARCHAR(50), -- VIP, Executive, Standard
                seat_capacity INT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            ) $charset_collate;",

            // [NEW] Master Data Bandara (Airports)
            "CREATE TABLE {$wpdb->prefix}umh_airports (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                iata_code VARCHAR(5) NOT NULL, -- CGK, JED, MED
                airport_name VARCHAR(255),
                city VARCHAR(100),
                terminal VARCHAR(50),
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            ) $charset_collate;",

            // [NEW] Master Data Perlengkapan (Equipment Kits - Catalog)
            "CREATE TABLE {$wpdb->prefix}umh_equipment_catalog (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                item_name VARCHAR(255) NOT NULL,
                sku VARCHAR(50) UNIQUE,
                cost_price DECIMAL(15,2) DEFAULT 0, -- HPP
                description TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            ) $charset_collate;",

            // [NEW] Master Data Rekening Bank (Company Accounts)
            "CREATE TABLE {$wpdb->prefix}umh_bank_accounts (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                bank_name VARCHAR(100) NOT NULL,
                account_number VARCHAR(50) NOT NULL,
                account_holder VARCHAR(255) NOT NULL,
                swift_code VARCHAR(20),
                is_active TINYINT(1) DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            ) $charset_collate;",

            // [NEW] Master Data Muassasah / Visa Provider
            "CREATE TABLE {$wpdb->prefix}umh_visa_providers (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                provider_name VARCHAR(255) NOT NULL,
                contact_info TEXT,
                base_visa_cost DECIMAL(15,2) DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            ) $charset_collate;",

            // --- 2. PRODUCT FACTORY (PACKAGES) ---

            "CREATE TABLE {$wpdb->prefix}umh_packages (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                description TEXT,
                hotel_mekkah_id BIGINT,
                hotel_madinah_id BIGINT,
                airline_id BIGINT,
                departure_airport VARCHAR(100),
                package_image_url VARCHAR(255),
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                deleted_at DATETIME NULL DEFAULT NULL
            ) $charset_collate;",

            "CREATE TABLE {$wpdb->prefix}umh_package_pricing (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                package_id BIGINT NOT NULL,
                room_type ENUM('quad', 'triple', 'double') NOT NULL,
                price DECIMAL(15,2) NOT NULL,
                currency VARCHAR(3) DEFAULT 'IDR',
                FOREIGN KEY (package_id) REFERENCES {$wpdb->prefix}umh_packages(id) ON DELETE CASCADE
            ) $charset_collate;",

            "CREATE TABLE {$wpdb->prefix}umh_package_itineraries (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                package_id BIGINT NOT NULL,
                day_number INT NOT NULL,
                title VARCHAR(255),
                description TEXT,
                location VARCHAR(100),
                FOREIGN KEY (package_id) REFERENCES {$wpdb->prefix}umh_packages(id) ON DELETE CASCADE
            ) $charset_collate;",

            "CREATE TABLE {$wpdb->prefix}umh_package_facilities (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                package_id BIGINT NOT NULL,
                facility_name VARCHAR(255),
                type ENUM('included', 'excluded') DEFAULT 'included',
                FOREIGN KEY (package_id) REFERENCES {$wpdb->prefix}umh_packages(id) ON DELETE CASCADE
            ) $charset_collate;",

            // --- 3. OPERATIONAL & INVENTORY ---

            "CREATE TABLE {$wpdb->prefix}umh_departures (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                package_id BIGINT,
                departure_date DATE NOT NULL,
                total_seats INT DEFAULT 45,
                available_seats INT DEFAULT 0,
                status VARCHAR(50) DEFAULT 'open',
                muthawif_id BIGINT, -- Link ke Muthawif
                bus_provider_id BIGINT, -- Link ke Bus
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (package_id) REFERENCES {$wpdb->prefix}umh_packages(id) ON DELETE SET NULL
            ) $charset_collate;",

            "CREATE TABLE {$wpdb->prefix}umh_inventory_items (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                item_code VARCHAR(50) NOT NULL,
                item_name VARCHAR(255) NOT NULL,
                stock_qty INT DEFAULT 0,
                catalog_id BIGINT, -- Link ke Master Catalog
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            ) $charset_collate;",

            // --- 4. BOOKING ENGINE ---

            "CREATE TABLE {$wpdb->prefix}umh_bookings (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                departure_id BIGINT,
                branch_id BIGINT,
                customer_user_id BIGINT(20) UNSIGNED NULL,
                total_price DECIMAL(15,2) NOT NULL,
                status VARCHAR(50) DEFAULT 'pending',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                deleted_at DATETIME NULL DEFAULT NULL,
                FOREIGN KEY (departure_id) REFERENCES {$wpdb->prefix}umh_departures(id) ON DELETE SET NULL,
                FOREIGN KEY (branch_id) REFERENCES {$wpdb->prefix}umh_branches(id) ON DELETE SET NULL
            ) $charset_collate;",

            "CREATE TABLE {$wpdb->prefix}umh_booking_passengers (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                booking_id BIGINT,
                name VARCHAR(255) NOT NULL,
                passport_number VARCHAR(50),
                passport_expiry DATE,
                is_tour_leader TINYINT(1) DEFAULT 0, -- Flag Tour Leader
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (booking_id) REFERENCES {$wpdb->prefix}umh_bookings(id) ON DELETE CASCADE
            ) $charset_collate;",

            // --- 5. FINANCE & SAVINGS ---

            "CREATE TABLE {$wpdb->prefix}umh_savings_plans (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                customer_user_id BIGINT(20) UNSIGNED NOT NULL,
                target_amount DECIMAL(15,2) NOT NULL,
                tenor_months INT NOT NULL,
                monthly_amount DECIMAL(15,2) NOT NULL,
                status VARCHAR(50) DEFAULT 'active',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            ) $charset_collate;",

            "CREATE TABLE {$wpdb->prefix}umh_savings_transactions (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                plan_id BIGINT,
                amount DECIMAL(15,2) NOT NULL,
                type ENUM('deposit', 'withdrawal') DEFAULT 'deposit',
                transaction_date DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (plan_id) REFERENCES {$wpdb->prefix}umh_savings_plans(id) ON DELETE CASCADE
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

            // --- 6. CRM & SPECIAL ---

            "CREATE TABLE {$wpdb->prefix}umh_haji_queue (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                customer_user_id BIGINT(20) UNSIGNED NOT NULL,
                porsi_number VARCHAR(50),
                estimated_year INT,
                status VARCHAR(50) DEFAULT 'waiting',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            ) $charset_collate;",

            "CREATE TABLE {$wpdb->prefix}umh_leads (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(100),
                phone VARCHAR(20),
                status VARCHAR(50) DEFAULT 'new',
                source VARCHAR(100),
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            ) $charset_collate;",

            // --- 7. HR & EMPLOYEE ---

            "CREATE TABLE {$wpdb->prefix}umh_employees (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                user_id BIGINT(20) UNSIGNED NULL,
                name VARCHAR(255) NOT NULL,
                position VARCHAR(100),
                base_salary DECIMAL(15,2) DEFAULT 0,
                joined_at DATE,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            ) $charset_collate;",

            "CREATE TABLE {$wpdb->prefix}umh_payroll (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                employee_id BIGINT,
                period_month INT,
                period_year INT,
                amount_paid DECIMAL(15,2) NOT NULL,
                payment_date DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (employee_id) REFERENCES {$wpdb->prefix}umh_employees(id) ON DELETE CASCADE
            ) $charset_collate;",

            // --- 8. ENTERPRISE SECURITY & AUDIT ---

            "CREATE TABLE {$wpdb->prefix}umh_audit_logs (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                user_id BIGINT UNSIGNED NOT NULL,
                action VARCHAR(50) NOT NULL,
                object_type VARCHAR(50) NOT NULL,
                object_id BIGINT NOT NULL,
                old_value LONGTEXT NULL,
                new_value LONGTEXT NULL,
                ip_address VARCHAR(45),
                user_agent TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            ) $charset_collate;",

            "CREATE TABLE {$wpdb->prefix}umh_api_keys (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                user_id BIGINT UNSIGNED NOT NULL,
                api_key VARCHAR(64) NOT NULL UNIQUE,
                permissions TEXT,
                last_used_at DATETIME,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            ) $charset_collate;"
        ];
    }
}