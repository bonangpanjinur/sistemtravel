<?php
/**
 * Database management class for Umroh Management System.
 * Version: 6.1.0 (Enterprise)
 */
class UMH_DB {
    private $wpdb;
    private $charset_collate;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->charset_collate = $wpdb->get_charset_collate();
    }

    public function create_tables() {
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $tables = [
            // 1. MASTER DATA
            "CREATE TABLE {$this->wpdb->prefix}umh_master_locations (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                name varchar(100) NOT NULL, 
                code varchar(10) NULL,      
                type enum('airport', 'city') NOT NULL DEFAULT 'city',
                country varchar(50) DEFAULT 'Saudi Arabia',
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) {$this->charset_collate};",

            "CREATE TABLE {$this->wpdb->prefix}umh_master_airlines (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                name varchar(100) NOT NULL,
                code varchar(10) NULL,
                origin varchar(10) NULL,
                destination varchar(10) NULL,
                transit varchar(100) NULL,
                contact_info text NULL,
                type varchar(20) DEFAULT 'International',
                status varchar(20) DEFAULT 'active',
                logo_url varchar(255) NULL,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) {$this->charset_collate};",

            "CREATE TABLE {$this->wpdb->prefix}umh_master_hotels (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                name varchar(150) NOT NULL,
                city varchar(50) NOT NULL, 
                city_id bigint(20) UNSIGNED,
                rating varchar(5) DEFAULT '5',
                distance_to_haram int DEFAULT 0, 
                address text,
                description text,
                images longtext,
                map_url text,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) {$this->charset_collate};",

            "CREATE TABLE {$this->wpdb->prefix}umh_master_mutawwifs (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                name varchar(150) NOT NULL,
                phone varchar(20) NOT NULL,
                email varchar(100),
                photo_url varchar(255),
                license_number varchar(50),
                languages text,                             
                specialization varchar(100),                
                base_location enum('Makkah', 'Madinah', 'Indonesia') DEFAULT 'Indonesia',
                experience_years int DEFAULT 1,
                rating decimal(3,2) DEFAULT 5.00,
                total_groups_handled int DEFAULT 0,
                status enum('active', 'inactive', 'suspended') DEFAULT 'active',
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) {$this->charset_collate};",

            "CREATE TABLE {$this->wpdb->prefix}umh_branches (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                name varchar(255) NOT NULL,
                address text,
                phone varchar(20),
                is_main_office boolean DEFAULT 0,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) {$this->charset_collate};",

            // 2. DATA JAMAAH
            "CREATE TABLE {$this->wpdb->prefix}umh_jamaah (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                user_id bigint(20) UNSIGNED NULL, 
                umh_user_id bigint(20) UNSIGNED NULL,
                nik varchar(20) UNIQUE,
                passport_number varchar(20),
                full_name varchar(150) NOT NULL,
                full_name_ar varchar(150),
                gender enum('L', 'P') NOT NULL,
                birth_place varchar(50),
                birth_date date,
                phone varchar(20),
                email varchar(100),
                address text,
                city varchar(50),
                job_title varchar(100),
                education varchar(50),
                clothing_size varchar(5),
                disease_history text,
                bpjs_number varchar(30),
                father_name varchar(100),
                mother_name varchar(100),
                spouse_name varchar(100),
                scan_ktp varchar(255),
                scan_kk varchar(255),
                scan_passport varchar(255),
                scan_photo varchar(255),
                scan_buku_nikah varchar(255),
                package_id bigint(20) UNSIGNED NULL,
                departure_id bigint(20) UNSIGNED NULL,
                room_type varchar(20) DEFAULT 'Quad',
                package_price decimal(15,2) DEFAULT 0,
                amount_paid decimal(15,2) DEFAULT 0,
                status enum('registered', 'dp', 'lunas', 'berangkat', 'selesai', 'batal') DEFAULT 'registered',
                payment_status enum('pending', 'belum_lunas', 'lunas') DEFAULT 'pending',
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY nik (nik),
                KEY phone (phone)
            ) {$this->charset_collate};",

            // 3. KATALOG & PRODUK
            "CREATE TABLE {$this->wpdb->prefix}umh_packages (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                name varchar(200) NOT NULL,
                slug varchar(200) UNIQUE,
                type enum('umrah', 'haji', 'tour') DEFAULT 'umrah',
                category_id bigint(20) UNSIGNED NULL,
                airline_id bigint(20) UNSIGNED NULL,
                hotel_makkah_id bigint(20) UNSIGNED NULL,
                hotel_madinah_id bigint(20) UNSIGNED NULL,
                duration_days int DEFAULT 9,
                base_price decimal(15,2) DEFAULT 0,
                base_price_quad decimal(15,2) DEFAULT 0,
                base_price_triple decimal(15,2) DEFAULT 0,
                base_price_double decimal(15,2) DEFAULT 0,
                currency varchar(3) DEFAULT 'IDR',
                down_payment_amount decimal(15,2) DEFAULT 0,
                payment_due_days int DEFAULT 30,
                description longtext,
                included_features longtext,
                excluded_features longtext,
                terms_conditions longtext,
                status enum('active', 'archived', 'inactive', 'draft') DEFAULT 'active',
                image_url varchar(255),
                brochure_pdf varchar(255),
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) {$this->charset_collate};",

            "CREATE TABLE {$this->wpdb->prefix}umh_package_categories (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                name varchar(100) NOT NULL,
                slug varchar(100),
                type enum('umrah', 'haji', 'tour') DEFAULT 'umrah',
                description text,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) {$this->charset_collate};",

            "CREATE TABLE {$this->wpdb->prefix}umh_package_itineraries (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                package_id bigint(20) UNSIGNED NOT NULL,
                day_number int NOT NULL,
                title varchar(150) NOT NULL,
                description text,
                location varchar(100),
                location_id bigint(20) UNSIGNED,
                meals varchar(50),
                image_url varchar(255),
                PRIMARY KEY (id),
                KEY package_id (package_id)
            ) {$this->charset_collate};",

            "CREATE TABLE {$this->wpdb->prefix}umh_package_facilities (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                package_id bigint(20) UNSIGNED NOT NULL,
                item_name varchar(200) NOT NULL,
                type enum('include', 'exclude') NOT NULL,
                icon_class varchar(50),
                PRIMARY KEY (id),
                KEY package_id (package_id)
            ) {$this->charset_collate};",

            // 4. INVENTORY & PRICING
            "CREATE TABLE {$this->wpdb->prefix}umh_departures (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                package_id bigint(20) UNSIGNED NOT NULL,
                departure_type enum('regular', 'private') DEFAULT 'regular',
                linked_private_request_id bigint(20) UNSIGNED NULL,
                departure_date date NOT NULL,
                return_date date NOT NULL,
                airline_id bigint(20) UNSIGNED,
                origin_airport_id bigint(20) UNSIGNED,
                hotel_makkah_id bigint(20) UNSIGNED,
                hotel_madinah_id bigint(20) UNSIGNED,
                quota int DEFAULT 45,
                seat_quota int DEFAULT 45,
                filled_seats int DEFAULT 0,
                seat_booked int DEFAULT 0,
                available_seats int DEFAULT 45,
                price_quad decimal(15,2) DEFAULT 0,
                price_triple decimal(15,2) DEFAULT 0,
                price_double decimal(15,2) DEFAULT 0,
                price_override decimal(15,2) DEFAULT 0,
                currency varchar(3) DEFAULT 'IDR',
                tour_leader_name varchar(100),
                tour_leader_id bigint(20) UNSIGNED NULL,
                mutawwif_name varchar(100),
                mutawwif_id bigint(20) UNSIGNED NULL,
                flight_number_depart varchar(20),
                flight_number_return varchar(20),
                status enum('open', 'closed', 'departed', 'completed', 'cancelled') DEFAULT 'open',
                notes text,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY package_id (package_id),
                KEY departure_date (departure_date)
            ) {$this->charset_collate};",

            "CREATE TABLE {$this->wpdb->prefix}umh_coupons (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                code varchar(50) NOT NULL UNIQUE,
                type enum('nominal', 'percent') DEFAULT 'nominal',
                value decimal(15,2) NOT NULL,
                min_purchase decimal(15,2) DEFAULT 0,
                max_discount decimal(15,2) DEFAULT 0,
                quota int DEFAULT 0,
                used_count int DEFAULT 0,
                expiry_date date,
                status enum('active', 'inactive') DEFAULT 'active',
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) {$this->charset_collate};",

            "CREATE TABLE {$this->wpdb->prefix}umh_booking_addons (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                name varchar(200) NOT NULL,
                price decimal(15,2) NOT NULL,
                description text,
                status enum('active', 'inactive') DEFAULT 'active',
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) {$this->charset_collate};",

            // 5. CRM & MARKETING
            "CREATE TABLE {$this->wpdb->prefix}umh_marketing (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                title varchar(200) NOT NULL,
                platform varchar(50),
                budget decimal(15,2) DEFAULT 0,
                start_date date,
                end_date date,
                status varchar(20) DEFAULT 'active',
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) {$this->charset_collate};",

            "CREATE TABLE {$this->wpdb->prefix}umh_leads (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                name varchar(100) NOT NULL,
                phone varchar(20),
                email varchar(100),
                source varchar(50),
                marketing_id bigint(20) UNSIGNED NULL,
                interest_package_id bigint(20) UNSIGNED NULL,
                status enum('new', 'contacted', 'hot', 'deal', 'lost') DEFAULT 'new',
                converted_booking_id bigint(20) UNSIGNED NULL,
                notes text,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) {$this->charset_collate};",

            // 6. BOOKINGS
            "CREATE TABLE {$this->wpdb->prefix}umh_bookings (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                booking_code varchar(50) NOT NULL UNIQUE,
                departure_id bigint(20) UNSIGNED NOT NULL,
                user_id bigint(20) UNSIGNED NULL,
                booker_user_id bigint(20) UNSIGNED NULL,
                branch_id bigint(20) UNSIGNED DEFAULT 0,
                agent_id bigint(20) UNSIGNED NULL,
                sub_agent_id bigint(20) UNSIGNED NULL,
                coupon_id bigint(20) UNSIGNED NULL,
                discount_amount decimal(15,2) DEFAULT 0,
                contact_name varchar(150),
                contact_phone varchar(20),
                contact_email varchar(100),
                currency varchar(3) DEFAULT 'IDR',
                total_pax int DEFAULT 1,
                total_price decimal(15,2) DEFAULT 0,
                total_paid decimal(15,2) DEFAULT 0,
                commission_agent decimal(15,2) DEFAULT 0,
                commission_sub_agent decimal(15,2) DEFAULT 0,
                payment_status enum('unpaid', 'dp', 'partial', 'paid', 'refunded', 'overdue') DEFAULT 'unpaid',
                status enum('draft', 'pending', 'confirmed', 'completed', 'cancelled') DEFAULT 'draft',
                is_from_savings boolean DEFAULT 0,              
                source_savings_id bigint(20) UNSIGNED NULL,     
                notes text,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY departure_id (departure_id)
            ) {$this->charset_collate};",

            "CREATE TABLE {$this->wpdb->prefix}umh_booking_passengers (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                booking_id bigint(20) UNSIGNED NOT NULL,
                jamaah_id bigint(20) UNSIGNED NOT NULL,
                room_type enum('quad', 'triple', 'double', 'single') DEFAULT 'quad',
                price decimal(15,2) DEFAULT 0,
                status enum('active', 'cancelled', 'moved') DEFAULT 'active',
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY booking_id (booking_id),
                KEY jamaah_id (jamaah_id)
            ) {$this->charset_collate};",

            // 7. DOC TRACKING
            "CREATE TABLE {$this->wpdb->prefix}umh_doc_tracking (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                jamaah_id bigint(20) UNSIGNED NOT NULL,
                doc_type varchar(50) NOT NULL,
                status enum('jamaah', 'office', 'provider', 'done') DEFAULT 'jamaah',
                updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) {$this->charset_collate};",

            // 8. FINANCE
            "CREATE TABLE {$this->wpdb->prefix}umh_finance (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                booking_id bigint(20) UNSIGNED NULL,
                transaction_type enum('income', 'expense') NOT NULL,
                category varchar(50) DEFAULT 'booking_payment',
                amount decimal(15,2) NOT NULL,
                payment_method varchar(50),
                payment_proof varchar(255),
                status enum('pending', 'verified', 'rejected') DEFAULT 'pending',
                transaction_date datetime DEFAULT CURRENT_TIMESTAMP,
                notes text,
                PRIMARY KEY (id)
            ) {$this->charset_collate};",

            "CREATE TABLE {$this->wpdb->prefix}umh_master_bank_accounts (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                bank_name varchar(100),
                account_number varchar(50),
                account_holder varchar(255),
                PRIMARY KEY (id)
            ) {$this->charset_collate};",

            "CREATE TABLE {$this->wpdb->prefix}umh_booking_requests (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                booking_id bigint(20) UNSIGNED NOT NULL,
                request_type enum('refund', 'cancellation', 'reschedule') NOT NULL,
                reason text,
                amount_requested decimal(15,2) DEFAULT 0,
                status enum('pending', 'approved', 'rejected') DEFAULT 'pending',
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) {$this->charset_collate};",

            // 9. SAVINGS
            "CREATE TABLE {$this->wpdb->prefix}umh_savings_accounts (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                user_id bigint(20) UNSIGNED NOT NULL,
                package_id bigint(20) UNSIGNED DEFAULT NULL,   
                tenure_years int(2) NOT NULL DEFAULT 1,        
                target_amount decimal(15,2) NOT NULL,          
                current_balance decimal(15,2) DEFAULT 0,       
                status enum('active', 'completed', 'cancelled', 'on_hold') DEFAULT 'active',
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) {$this->charset_collate};",

            "CREATE TABLE {$this->wpdb->prefix}umh_savings_transactions (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                account_id bigint(20) UNSIGNED NOT NULL,
                amount decimal(15,2) NOT NULL,
                payment_proof varchar(255),
                status enum('pending', 'verified', 'rejected') DEFAULT 'pending',
                transaction_date datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) {$this->charset_collate};",

            // 10. OPERATIONAL
            "CREATE TABLE {$this->wpdb->prefix}umh_rooming_list (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                departure_id bigint(20) UNSIGNED NOT NULL,
                room_number varchar(20),
                jamaah_id bigint(20) UNSIGNED NOT NULL,
                PRIMARY KEY (id)
            ) {$this->charset_collate};",

            "CREATE TABLE {$this->wpdb->prefix}umh_visa_batches (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                batch_name varchar(255),
                provider_name varchar(255),
                status enum('draft', 'submitted', 'approved', 'rejected') DEFAULT 'draft',
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) {$this->charset_collate};",

            "CREATE TABLE {$this->wpdb->prefix}umh_manasik_schedules (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                departure_id bigint(20) UNSIGNED NOT NULL,
                event_date datetime NOT NULL,
                location text,
                notes text,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) {$this->charset_collate};",

            "CREATE TABLE {$this->wpdb->prefix}umh_manasik_attendance (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                schedule_id bigint(20) UNSIGNED NOT NULL,
                jamaah_id bigint(20) UNSIGNED NOT NULL,
                status enum('present', 'absent') DEFAULT 'absent',
                attended_at datetime,
                PRIMARY KEY (id)
            ) {$this->charset_collate};",

            // 11. LOGISTIK
            "CREATE TABLE {$this->wpdb->prefix}umh_inventory_items (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                item_code varchar(50) UNIQUE, 
                item_name varchar(100) NOT NULL,
                category enum('perlengkapan', 'dokumen', 'souvenir') DEFAULT 'perlengkapan',
                stock_qty int DEFAULT 0,
                min_stock_alert int DEFAULT 10,
                unit_cost decimal(15,2) DEFAULT 0,
                updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) {$this->charset_collate};",

            "CREATE TABLE {$this->wpdb->prefix}umh_logistics_distribution (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                booking_passenger_id bigint(20) UNSIGNED NOT NULL,
                item_id bigint(20) UNSIGNED NOT NULL,
                qty int DEFAULT 1,
                status enum('pending', 'ready', 'taken', 'shipped') DEFAULT 'pending',
                taken_by varchar(100),
                taken_date datetime,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) {$this->charset_collate};",

            // 12. AGEN & HR
            "CREATE TABLE {$this->wpdb->prefix}umh_agents (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                user_id bigint(20) UNSIGNED NOT NULL DEFAULT 0,
                name varchar(100) NOT NULL,
                email varchar(100),
                phone varchar(20),
                city varchar(50),
                code varchar(50),
                branch_id bigint(20) UNSIGNED DEFAULT 0,
                parent_id bigint(20) UNSIGNED NULL,
                type varchar(20) DEFAULT 'master',
                agency_name varchar(100),
                level enum('silver', 'gold', 'platinum', 'master', 'sub') DEFAULT 'master',
                commission_type enum('fixed', 'percent') DEFAULT 'fixed',
                commission_value decimal(15,2) DEFAULT 0,
                status enum('active', 'suspended', 'inactive', 'pending') DEFAULT 'active',
                joined_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) {$this->charset_collate};",

            "CREATE TABLE {$this->wpdb->prefix}umh_hr_employees (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                user_id bigint(20) UNSIGNED DEFAULT 0,
                name varchar(100) NOT NULL,
                email varchar(100),
                phone varchar(20),
                position varchar(100),
                department varchar(100),
                join_date date,
                salary decimal(15,2) DEFAULT 0,
                status enum('active', 'resigned', 'terminated') DEFAULT 'active',
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) {$this->charset_collate};",

            "CREATE TABLE {$this->wpdb->prefix}umh_hr_attendance (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                employee_id bigint(20) UNSIGNED NOT NULL,
                clock_in datetime,
                clock_out datetime,
                status enum('present', 'late', 'absent', 'on_leave') DEFAULT 'present',
                PRIMARY KEY (id)
            ) {$this->charset_collate};",

            // 13. SPECIAL SERVICES
            "CREATE TABLE {$this->wpdb->prefix}umh_private_requests (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                user_id bigint(20) UNSIGNED,
                name varchar(150) NOT NULL,
                phone varchar(20),
                email varchar(100),
                departure_date_plan date,
                duration_days int,
                pax_count int,
                hotel_star_pref varchar(10),
                notes text,
                status enum('new', 'quoted', 'deal', 'cancelled') DEFAULT 'new',
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) {$this->charset_collate};",

            "CREATE TABLE {$this->wpdb->prefix}umh_private_quotations (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                request_id bigint(20) UNSIGNED NOT NULL,
                total_cost decimal(15,2),
                margin_percent decimal(5,2),
                final_price decimal(15,2),
                pdf_url varchar(255),
                status enum('draft', 'sent', 'accepted', 'rejected') DEFAULT 'draft',
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) {$this->charset_collate};",

            "CREATE TABLE {$this->wpdb->prefix}umh_badal_umrah (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                booking_id bigint(20) UNSIGNED,
                badal_name varchar(150) NOT NULL,
                badal_gender enum('L', 'P'),
                status_person enum('wafat', 'sakit_parah') DEFAULT 'wafat',
                mutawwif_id bigint(20) UNSIGNED,
                execution_date date,
                proof_video_url varchar(255),
                certificate_url varchar(255),
                status enum('pending', 'assigned', 'completed') DEFAULT 'pending',
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) {$this->charset_collate};",

            // 14. CUSTOMER CARE
            "CREATE TABLE {$this->wpdb->prefix}umh_support_tickets (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                user_id bigint(20) UNSIGNED,
                booking_id bigint(20) UNSIGNED,
                subject varchar(200) NOT NULL,
                priority enum('low', 'medium', 'high') DEFAULT 'medium',
                status enum('open', 'in_progress', 'resolved', 'closed') DEFAULT 'open',
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) {$this->charset_collate};",

            "CREATE TABLE {$this->wpdb->prefix}umh_support_messages (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                ticket_id bigint(20) UNSIGNED NOT NULL,
                user_id bigint(20) UNSIGNED NOT NULL,
                message text NOT NULL,
                is_admin boolean DEFAULT 0,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) {$this->charset_collate};",

            "CREATE TABLE {$this->wpdb->prefix}umh_reviews (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                booking_id bigint(20) UNSIGNED NOT NULL,
                jamaah_id bigint(20) UNSIGNED NOT NULL,
                rating int(1) NOT NULL,
                comment text,
                review_type enum('hotel', 'mutawwif', 'service', 'overall') DEFAULT 'overall',
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) {$this->charset_collate};",

            // 15. SYSTEM & AUTOMATION
            "CREATE TABLE {$this->wpdb->prefix}umh_notifications (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                user_id bigint(20) UNSIGNED,
                title varchar(200),
                message text,
                type enum('info', 'warning', 'billing', 'schedule') DEFAULT 'info',
                is_read boolean DEFAULT 0,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) {$this->charset_collate};",

            "CREATE TABLE {$this->wpdb->prefix}umh_notification_templates (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                name varchar(100) UNIQUE,
                subject varchar(200),
                content text,
                type enum('email', 'whatsapp', 'push') DEFAULT 'whatsapp',
                PRIMARY KEY (id)
            ) {$this->charset_collate};",

            "CREATE TABLE {$this->wpdb->prefix}umh_activity_logs (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                user_id bigint(20) UNSIGNED,
                action varchar(100),
                table_name varchar(100),
                record_id bigint(20) UNSIGNED,
                old_value longtext,
                new_value longtext,
                ip_address varchar(45),
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) {$this->charset_collate};",

            "CREATE TABLE {$this->wpdb->prefix}umh_tasks (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                title varchar(200) NOT NULL,
                description text,
                assigned_to bigint(20) UNSIGNED,
                due_date date,
                priority enum('low', 'medium', 'high') DEFAULT 'medium',
                status enum('pending', 'in_progress', 'completed') DEFAULT 'pending',
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) {$this->charset_collate};"
        ];

        foreach ($tables as $sql) {
            dbDelta($sql);
        }
    }
}
