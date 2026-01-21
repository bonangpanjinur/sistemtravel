<?php
// Path: src/Config/DatabaseSchema.php

namespace App\Config;

class DatabaseSchema {
    
    /**
     * Method utama untuk membuat semua tabel.
     * Dipanggil saat plugin diaktifkan.
     */
    public static function createTables() {
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        $schemas = self::get_schema();
        
        foreach ($schemas as $sql) {
            dbDelta($sql);
        }

        // Jalankan seeding data awal
        self::seed_initial_data();
    }

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

            "CREATE TABLE {$wpdb->prefix}umh_hotels (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                location VARCHAR(255),
                rating INT,
                description TEXT,
                image_url TEXT,
                map_embed_code TEXT,
                city VARCHAR(50), -- Added city column for consistency with MasterDataRepository
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            ) $charset_collate;",

            "CREATE TABLE {$wpdb->prefix}umh_airlines (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                code VARCHAR(10),
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            ) $charset_collate;",

            "CREATE TABLE {$wpdb->prefix}umh_muthawifs (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                phone_saudi VARCHAR(20),
                phone_indo VARCHAR(20),
                certification VARCHAR(100),
                rating DECIMAL(3,2) DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            ) $charset_collate;",

            "CREATE TABLE {$wpdb->prefix}umh_bus_providers (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                company_name VARCHAR(255) NOT NULL,
                contact_person VARCHAR(100),
                phone VARCHAR(20),
                bus_type VARCHAR(50), 
                seat_capacity INT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            ) $charset_collate;",

            "CREATE TABLE {$wpdb->prefix}umh_airports (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                iata_code VARCHAR(5) NOT NULL,
                airport_name VARCHAR(255),
                city VARCHAR(100),
                terminal VARCHAR(50),
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            ) $charset_collate;",

            // [BARU] Tabel Kurs Mata Uang
            "CREATE TABLE {$wpdb->prefix}umh_exchange_rates (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                currency_code VARCHAR(5) NOT NULL UNIQUE, -- USD, SAR
                rate_to_idr DECIMAL(15,2) NOT NULL,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) $charset_collate;",

            // [BARU] Katalog Layanan Tambahan (Add-ons)
            "CREATE TABLE {$wpdb->prefix}umh_service_catalog (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                service_name VARCHAR(255) NOT NULL,
                price DECIMAL(15,2) NOT NULL,
                unit_type VARCHAR(20) DEFAULT 'per_pax', -- per_pax, per_booking
                description TEXT,
                is_active TINYINT(1) DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            ) $charset_collate;",

            // [BARU] Master Tipe Dokumen (KTP, Paspor, Buku Kuning, dll)
            "CREATE TABLE {$wpdb->prefix}umh_master_document_types (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                doc_name VARCHAR(100) NOT NULL,
                is_mandatory TINYINT(1) DEFAULT 1,
                required_for VARCHAR(20) DEFAULT 'all', -- all, adult, child
                description TEXT
            ) $charset_collate;",

            // --- 2. PACKAGES & PRICING ---
            "CREATE TABLE {$wpdb->prefix}umh_packages (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                description TEXT,
                hotel_mekkah_id BIGINT,
                hotel_madinah_id BIGINT,
                airline_id BIGINT,
                departure_airport VARCHAR(100),
                package_image_url VARCHAR(255),
                base_price DECIMAL(15,2) DEFAULT 0,
                duration_days INT DEFAULT 9,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                deleted_at DATETIME NULL DEFAULT NULL,
                status VARCHAR(20) DEFAULT 'active'
            ) $charset_collate;",

            "CREATE TABLE {$wpdb->prefix}umh_package_pricing (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                package_id BIGINT NOT NULL,
                room_type VARCHAR(50) NOT NULL, 
                price DECIMAL(15,2) NOT NULL,
                currency VARCHAR(3) DEFAULT 'IDR',
                FOREIGN KEY (package_id) REFERENCES {$wpdb->prefix}umh_packages(id) ON DELETE CASCADE
            ) $charset_collate;",

            "CREATE TABLE {$wpdb->prefix}umh_coupons (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                code VARCHAR(50) UNIQUE NOT NULL,
                discount_type ENUM('fixed', 'percent') NOT NULL,
                amount DECIMAL(15,2) NOT NULL,
                expiry_date DATE,
                usage_limit INT DEFAULT 0,
                used_count INT DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
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

            // --- 3. DEPARTURES & INVENTORY ---
            "CREATE TABLE {$wpdb->prefix}umh_departures (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                package_id BIGINT,
                departure_date DATE NOT NULL,
                total_seats INT DEFAULT 45,
                available_seats INT DEFAULT 0,
                status VARCHAR(50) DEFAULT 'open',
                muthawif_id BIGINT,
                bus_provider_id BIGINT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (package_id) REFERENCES {$wpdb->prefix}umh_packages(id) ON DELETE SET NULL
            ) $charset_collate;",

            "CREATE TABLE {$wpdb->prefix}umh_inventory_items (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                item_code VARCHAR(50) NOT NULL,
                item_name VARCHAR(255) NOT NULL,
                stock_qty INT DEFAULT 0,
                catalog_id BIGINT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            ) $charset_collate;",
            
            "CREATE TABLE {$wpdb->prefix}umh_inventory_logs (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                item_id BIGINT NOT NULL,
                qty_change INT NOT NULL,
                transaction_type VARCHAR(50),
                reference_id VARCHAR(50),
                user_id BIGINT,
                notes TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            ) $charset_collate;",

            // --- 4. BOOKING ENGINE ---
            "CREATE TABLE {$wpdb->prefix}umh_bookings (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                code VARCHAR(50) UNIQUE, -- Added code column
                departure_id BIGINT,
                branch_id BIGINT,
                customer_user_id BIGINT(20) UNSIGNED NULL,
                agent_id BIGINT(20) UNSIGNED NULL,
                total_price DECIMAL(15,2) NOT NULL,
                discount_total DECIMAL(15,2) DEFAULT 0,
                coupon_code VARCHAR(50) NULL,
                room_type VARCHAR(50), -- Added room_type column
                status VARCHAR(50) DEFAULT 'pending',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                deleted_at DATETIME NULL DEFAULT NULL,
                FOREIGN KEY (departure_id) REFERENCES {$wpdb->prefix}umh_departures(id) ON DELETE SET NULL,
                FOREIGN KEY (branch_id) REFERENCES {$wpdb->prefix}umh_branches(id) ON DELETE SET NULL
            ) $charset_collate;",

            // [BARU] Tabel Relasi Add-ons per Booking
            "CREATE TABLE {$wpdb->prefix}umh_booking_addons (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                booking_id BIGINT NOT NULL,
                service_id BIGINT NOT NULL, // Should match service_catalog id but renamed column in input was addon_id logic
                quantity INT DEFAULT 1,
                total_price DECIMAL(15,2) NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (booking_id) REFERENCES {$wpdb->prefix}umh_bookings(id) ON DELETE CASCADE
            ) $charset_collate;",

            "CREATE TABLE {$wpdb->prefix}umh_booking_passengers (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                booking_id BIGINT,
                name VARCHAR(255) NOT NULL,
                pax_type VARCHAR(20) DEFAULT 'adult',
                passport_number VARCHAR(50),
                passport_expiry DATE,
                is_tour_leader TINYINT(1) DEFAULT 0,
                
                -- Legacy Columns (Opsional jika sudah migrasi ke tabel dokumen)
                passport_file_url TEXT,
                ktp_file_url TEXT,
                photo_file_url TEXT,
                doc_verification_status VARCHAR(20) DEFAULT 'pending',
                
                assigned_room_number VARCHAR(20),
                assigned_room_type VARCHAR(20),
                
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (booking_id) REFERENCES {$wpdb->prefix}umh_bookings(id) ON DELETE CASCADE
            ) $charset_collate;",

            // [BARU] Tabel Dokumen Detail Penumpang (Normalized)
            "CREATE TABLE {$wpdb->prefix}umh_passenger_documents (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                passenger_id BIGINT NOT NULL,
                doc_type_id BIGINT NOT NULL,
                file_url TEXT,
                status VARCHAR(20) DEFAULT 'pending', -- pending, verified, rejected
                verified_by BIGINT,
                verified_at DATETIME,
                notes TEXT,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (passenger_id) REFERENCES {$wpdb->prefix}umh_booking_passengers(id) ON DELETE CASCADE,
                FOREIGN KEY (doc_type_id) REFERENCES {$wpdb->prefix}umh_master_document_types(id)
            ) $charset_collate;",

            // --- 5. FINANCE & ACCOUNTING ---
            "CREATE TABLE {$wpdb->prefix}umh_payments (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                booking_id BIGINT NOT NULL,
                user_id BIGINT(20) UNSIGNED NOT NULL,
                amount DECIMAL(15,2) NOT NULL,
                payment_method VARCHAR(50) DEFAULT 'bank_transfer',
                bank_target VARCHAR(100),
                sender_name VARCHAR(100),
                proof_file_url TEXT,
                payment_date DATETIME DEFAULT CURRENT_TIMESTAMP, -- Added payment_date
                status VARCHAR(50) DEFAULT 'pending_verification',
                verified_by BIGINT(20) UNSIGNED,
                verified_at DATETIME,
                notes TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (booking_id) REFERENCES {$wpdb->prefix}umh_bookings(id) ON DELETE CASCADE
            ) $charset_collate;",

            "CREATE TABLE {$wpdb->prefix}umh_savings_plans (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                customer_user_id BIGINT(20) UNSIGNED NOT NULL,
                target_amount DECIMAL(15,2) NOT NULL,
                tenor_months INT NOT NULL,
                monthly_amount DECIMAL(15,2) NOT NULL,
                status VARCHAR(50) DEFAULT 'active',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            ) $charset_collate;",

            "CREATE TABLE {$wpdb->prefix}umh_gl_accounts (
                account_code VARCHAR(20) PRIMARY KEY,
                account_name VARCHAR(100) NOT NULL,
                account_type ENUM('ASSET', 'LIABILITY', 'EQUITY', 'REVENUE', 'EXPENSE') NOT NULL
            ) $charset_collate;",

            "CREATE TABLE {$wpdb->prefix}umh_journal_entries ( -- Renamed to match FinanceRepository usage
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                transaction_date DATETIME DEFAULT CURRENT_TIMESTAMP,
                transaction_ref_id VARCHAR(50), 
                description TEXT,
                account_code VARCHAR(20),
                debit DECIMAL(15,2) DEFAULT 0,
                credit DECIMAL(15,2) DEFAULT 0,
                created_by BIGINT UNSIGNED,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP, -- Added created_at
                FOREIGN KEY (account_code) REFERENCES {$wpdb->prefix}umh_gl_accounts(account_code)
            ) $charset_collate;",
            
            "CREATE TABLE {$wpdb->prefix}umh_refunds (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                booking_id BIGINT NOT NULL,
                reason TEXT,
                amount_requested DECIMAL(15,2),
                cancellation_fee DECIMAL(15,2) DEFAULT 0,
                amount_approved DECIMAL(15,2) DEFAULT 0,
                status VARCHAR(50) DEFAULT 'requested', 
                requested_by BIGINT,
                approved_by BIGINT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (booking_id) REFERENCES {$wpdb->prefix}umh_bookings(id) ON DELETE CASCADE
            ) $charset_collate;",

            // --- 6. AGENT SYSTEM, OPERATIONAL, SUPPORT, SETTINGS ---
            "CREATE TABLE {$wpdb->prefix}umh_commissions (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                agent_id BIGINT(20) UNSIGNED NOT NULL,
                booking_id BIGINT NOT NULL,
                amount DECIMAL(15,2) DEFAULT 0,
                status VARCHAR(50) DEFAULT 'pending',
                description VARCHAR(255),
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (booking_id) REFERENCES {$wpdb->prefix}umh_bookings(id) ON DELETE CASCADE
            ) $charset_collate;",

            "CREATE TABLE {$wpdb->prefix}umh_agent_relations (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                agent_id BIGINT(20) UNSIGNED NOT NULL UNIQUE,
                upline_id BIGINT(20) UNSIGNED, 
                tier_level VARCHAR(20) DEFAULT 'silver',
                joined_at DATETIME DEFAULT CURRENT_TIMESTAMP
            ) $charset_collate;",

            "CREATE TABLE {$wpdb->prefix}umh_agent_points (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                agent_id BIGINT(20) UNSIGNED NOT NULL,
                points INT NOT NULL,
                type ENUM('earned', 'redeemed') NOT NULL,
                source_booking_id BIGINT NULL,
                description VARCHAR(255),
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            ) $charset_collate;",

            // [BARU - MANIFEST]
            "CREATE TABLE {$wpdb->prefix}umh_manifest (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                departure_id BIGINT NOT NULL,
                passenger_id BIGINT NOT NULL,
                seat_number VARCHAR(10),
                visa_status VARCHAR(20) DEFAULT 'not_submitted',
                visa_number VARCHAR(50),
                room_number VARCHAR(20),
                hotel_name VARCHAR(100),
                FOREIGN KEY (departure_id) REFERENCES {$wpdb->prefix}umh_departures(id),
                FOREIGN KEY (passenger_id) REFERENCES {$wpdb->prefix}umh_booking_passengers(id)
            ) $charset_collate;",

            "CREATE TABLE {$wpdb->prefix}umh_luggage (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                passenger_id BIGINT NOT NULL,
                tag_code VARCHAR(50) NOT NULL UNIQUE, 
                status VARCHAR(30) DEFAULT 'printed', 
                last_scanned_at DATETIME,
                last_scanned_loc VARCHAR(100),
                FOREIGN KEY (passenger_id) REFERENCES {$wpdb->prefix}umh_booking_passengers(id) ON DELETE CASCADE
            ) $charset_collate;",
            
            "CREATE TABLE {$wpdb->prefix}umh_passenger_equipment (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                passenger_id BIGINT NOT NULL,
                item_id BIGINT NOT NULL,
                status VARCHAR(20) DEFAULT 'taken',
                taken_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                staff_id BIGINT,
                FOREIGN KEY (passenger_id) REFERENCES {$wpdb->prefix}umh_booking_passengers(id) ON DELETE CASCADE,
                FOREIGN KEY (item_id) REFERENCES {$wpdb->prefix}umh_inventory_items(id)
            ) $charset_collate;",

            "CREATE TABLE {$wpdb->prefix}umh_attendance (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                passenger_id BIGINT NOT NULL,
                departure_id BIGINT NOT NULL,
                checkpoint_name VARCHAR(50) NOT NULL,
                scanned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                scanned_by BIGINT UNSIGNED,
                FOREIGN KEY (passenger_id) REFERENCES {$wpdb->prefix}umh_booking_passengers(id) ON DELETE CASCADE
            ) $charset_collate;",

            "CREATE TABLE {$wpdb->prefix}umh_jemaah_progress (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                booking_id BIGINT NOT NULL,
                step_key VARCHAR(50) NOT NULL,
                status VARCHAR(20) DEFAULT 'pending',
                completed_at DATETIME,
                FOREIGN KEY (booking_id) REFERENCES {$wpdb->prefix}umh_bookings(id) ON DELETE CASCADE
            ) $charset_collate;",

            "CREATE TABLE {$wpdb->prefix}umh_digital_guides (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                content TEXT,
                category ENUM('manasik', 'doa', 'info') DEFAULT 'info',
                media_url VARCHAR(255), 
                is_active TINYINT(1) DEFAULT 1
            ) $charset_collate;",

            "CREATE TABLE {$wpdb->prefix}umh_haji_queue (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                customer_user_id BIGINT(20) UNSIGNED NOT NULL,
                porsi_number VARCHAR(50),
                estimated_year INT,
                status VARCHAR(50) DEFAULT 'waiting',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            ) $charset_collate;",

            // [UPDATE] Tabel Leads (Fitur CRM Baru)
            "CREATE TABLE {$wpdb->prefix}umh_leads (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                phone VARCHAR(20) NOT NULL,
                email VARCHAR(100) DEFAULT '',
                source VARCHAR(50) DEFAULT 'manual', -- IG, FB, WA, Walk-in
                status VARCHAR(20) DEFAULT 'new', -- new, follow_up, closing, lost
                interested_in VARCHAR(255) DEFAULT '', -- Nama paket yang diminati
                notes TEXT,
                follow_up_date DATE DEFAULT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                KEY status (status)
            ) $charset_collate;",

            "CREATE TABLE {$wpdb->prefix}umh_employees (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                user_id BIGINT(20) UNSIGNED NULL,
                name VARCHAR(255) NOT NULL,
                position VARCHAR(100),
                base_salary DECIMAL(15,2) DEFAULT 0,
                joined_at DATE,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            ) $charset_collate;",

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

            "CREATE TABLE {$wpdb->prefix}umh_wa_outbox (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                user_id BIGINT UNSIGNED NULL,
                phone_number VARCHAR(20) NOT NULL,
                message TEXT NOT NULL,
                status VARCHAR(20) DEFAULT 'pending',
                response_log TEXT,
                attempt_count INT DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                sent_at DATETIME NULL
            ) $charset_collate;",

            "CREATE TABLE {$wpdb->prefix}umh_settings (
                setting_key VARCHAR(100) PRIMARY KEY,
                setting_value LONGTEXT
            ) $charset_collate;"
        ];
    }

    /**
     * Fungsi Seeding untuk Data Awal
     */
    public static function seed_initial_data() {
        global $wpdb;

        // 1. Seed Kurs Mata Uang (Default)
        $wpdb->replace($wpdb->prefix . 'umh_exchange_rates', [
            'currency_code' => 'SAR',
            'rate_to_idr' => 4250.00
        ]);
        $wpdb->replace($wpdb->prefix . 'umh_exchange_rates', [
            'currency_code' => 'USD',
            'rate_to_idr' => 15500.00
        ]);

        // 2. Seed Katalog Layanan Tambahan (Contoh)
        $services = [
            ['Kursi Roda', 2000000, 'per_pax', 'Layanan kursi roda selama ibadah + pendorong'],
            ['Mutawwif Khusus', 3000000, 'per_booking', 'Pembimbing ibadah privat untuk keluarga'],
            ['Upgrade Hotel', 5000000, 'per_pax', 'Upgrade ke View Kabah / Hotel Bintang 5'],
            ['Kereta Cepat Haramain', 750000, 'per_pax', 'Tiket Business Class Mekkah-Madinah'],
            ['Laundry Package', 500000, 'per_pax', 'Layanan laundry full selama perjalanan']
        ];

        foreach ($services as $svc) {
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}umh_service_catalog WHERE service_name = %s", 
                $svc[0]
            ));
            
            if (!$exists) {
                $wpdb->insert($wpdb->prefix . 'umh_service_catalog', [
                    'service_name' => $svc[0],
                    'price' => $svc[1],
                    'unit_type' => $svc[2],
                    'description' => $svc[3],
                    'is_active' => 1
                ]);
            }
        }

        // 3. Seed Master Document Types
        $docs = [
            ['KTP', 1, 'all'],
            ['Kartu Keluarga', 1, 'all'],
            ['Paspor', 1, 'all'],
            ['Buku Nikah', 0, 'adult'], // Wajib jika suami istri
            ['Akte Lahir', 1, 'child'],
            ['Buku Kuning (Meningitis)', 1, 'all'],
            ['Pas Foto 4x6', 1, 'all']
        ];

        foreach ($docs as $doc) {
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}umh_master_document_types WHERE doc_name = %s", 
                $doc[0]
            ));
            
            if (!$exists) {
                $wpdb->insert($wpdb->prefix . 'umh_master_document_types', [
                    'doc_name' => $doc[0],
                    'is_mandatory' => $doc[1],
                    'required_for' => $doc[2]
                ]);
            }
        }
    }
}