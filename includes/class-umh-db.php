<?php
/**
 * Database management class for Umroh Management System.
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
            // Master Data
            "CREATE TABLE {$this->wpdb->prefix}umh_master_locations (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                name varchar(255) NOT NULL,
                type enum('city', 'country', 'airport') NOT NULL,
                code varchar(50),
                PRIMARY KEY (id)
            ) {$this->charset_collate};",

            "CREATE TABLE {$this->wpdb->prefix}umh_master_airlines (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                name varchar(255) NOT NULL,
                code varchar(10),
                logo_url varchar(255),
                PRIMARY KEY (id)
            ) {$this->charset_collate};",

            "CREATE TABLE {$this->wpdb->prefix}umh_master_hotels (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                name varchar(255) NOT NULL,
                location_id bigint(20),
                star_rating int(1),
                facilities text,
                gallery text,
                PRIMARY KEY (id)
            ) {$this->charset_collate};",

            "CREATE TABLE {$this->wpdb->prefix}umh_master_mutawwifs (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                name varchar(255) NOT NULL,
                phone varchar(20),
                rating decimal(3,2),
                specialization text,
                profile_photo varchar(255),
                PRIMARY KEY (id)
            ) {$this->charset_collate};",

            "CREATE TABLE {$this->wpdb->prefix}umh_branches (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                name varchar(255) NOT NULL,
                address text,
                phone varchar(20),
                is_main_office boolean DEFAULT 0,
                PRIMARY KEY (id)
            ) {$this->charset_collate};",

            // Product Factory
            "CREATE TABLE {$this->wpdb->prefix}umh_package_categories (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                name varchar(255) NOT NULL,
                PRIMARY KEY (id)
            ) {$this->charset_collate};",

            "CREATE TABLE {$this->wpdb->prefix}umh_packages (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                category_id bigint(20),
                name varchar(255) NOT NULL,
                duration_days int(3),
                description text,
                PRIMARY KEY (id)
            ) {$this->charset_collate};",

            "CREATE TABLE {$this->wpdb->prefix}umh_package_itineraries (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                package_id bigint(20),
                day_number int(3),
                activity_title varchar(255),
                activity_description text,
                PRIMARY KEY (id)
            ) {$this->charset_collate};",

            "CREATE TABLE {$this->wpdb->prefix}umh_package_facilities (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                package_id bigint(20),
                facility_name varchar(255),
                is_included boolean DEFAULT 1,
                PRIMARY KEY (id)
            ) {$this->charset_collate};",

            // Inventory & Pricing
            "CREATE TABLE {$this->wpdb->prefix}umh_departures (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                package_id bigint(20),
                departure_date date,
                quota_seat int(5),
                available_seat int(5),
                flight_number varchar(50),
                price_quad decimal(15,2),
                price_triple decimal(15,2),
                price_double decimal(15,2),
                PRIMARY KEY (id)
            ) {$this->charset_collate};",

            "CREATE TABLE {$this->wpdb->prefix}umh_coupons (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                code varchar(50) NOT NULL,
                discount_type enum('nominal', 'percent'),
                discount_value decimal(15,2),
                usage_limit int(5),
                expiry_date date,
                PRIMARY KEY (id)
            ) {$this->charset_collate};",

            "CREATE TABLE {$this->wpdb->prefix}umh_booking_addons (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                name varchar(255),
                price decimal(15,2),
                PRIMARY KEY (id)
            ) {$this->charset_collate};",

            // CRM & Leads
            "CREATE TABLE {$this->wpdb->prefix}umh_marketing (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                campaign_name varchar(255),
                source varchar(100),
                budget decimal(15,2),
                PRIMARY KEY (id)
            ) {$this->charset_collate};",

            "CREATE TABLE {$this->wpdb->prefix}umh_leads (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                campaign_id bigint(20),
                name varchar(255),
                phone varchar(20),
                status enum('new', 'contacted', 'hot', 'deal', 'lost'),
                notes text,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) {$this->charset_collate};",

            // Booking Engine
            "CREATE TABLE {$this->wpdb->prefix}umh_bookings (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                departure_id bigint(20),
                booking_code varchar(20) UNIQUE,
                total_amount decimal(15,2),
                status enum('pending', 'confirmed', 'cancelled', 'completed'),
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) {$this->charset_collate};",

            "CREATE TABLE {$this->wpdb->prefix}umh_jamaah (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                nik varchar(20) UNIQUE,
                full_name varchar(255),
                phone varchar(20),
                passport_number varchar(50),
                passport_expiry date,
                scan_ktp varchar(255),
                scan_passport varchar(255),
                PRIMARY KEY (id)
            ) {$this->charset_collate};",

            "CREATE TABLE {$this->wpdb->prefix}umh_booking_passengers (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                booking_id bigint(20),
                jamaah_id bigint(20),
                room_type enum('quad', 'triple', 'double'),
                PRIMARY KEY (id)
            ) {$this->charset_collate};",

            "CREATE TABLE {$this->wpdb->prefix}umh_doc_tracking (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                jamaah_id bigint(20),
                doc_type varchar(50),
                status enum('jamaah', 'office', 'provider', 'done'),
                updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) {$this->charset_collate};",

            // Finance
            "CREATE TABLE {$this->wpdb->prefix}umh_master_bank_accounts (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                bank_name varchar(100),
                account_number varchar(50),
                account_holder varchar(255),
                PRIMARY KEY (id)
            ) {$this->charset_collate};",

            "CREATE TABLE {$this->wpdb->prefix}umh_finance (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                booking_id bigint(20),
                type enum('income', 'expense'),
                amount decimal(15,2),
                payment_method varchar(50),
                transaction_date datetime,
                PRIMARY KEY (id)
            ) {$this->charset_collate};",

            "CREATE TABLE {$this->wpdb->prefix}umh_booking_requests (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                booking_id bigint(20),
                request_type enum('refund', 'cancellation'),
                reason text,
                status enum('pending', 'approved', 'rejected'),
                PRIMARY KEY (id)
            ) {$this->charset_collate};",

            "CREATE TABLE {$this->wpdb->prefix}umh_savings_accounts (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                jamaah_id bigint(20),
                account_number varchar(50) UNIQUE,
                balance decimal(15,2) DEFAULT 0,
                PRIMARY KEY (id)
            ) {$this->charset_collate};",

            "CREATE TABLE {$this->wpdb->prefix}umh_savings_transactions (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                account_id bigint(20),
                type enum('deposit', 'withdrawal', 'conversion'),
                amount decimal(15,2),
                transaction_date datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) {$this->charset_collate};",

            // Operasional
            "CREATE TABLE {$this->wpdb->prefix}umh_rooming_list (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                departure_id bigint(20),
                room_number varchar(20),
                jamaah_id bigint(20),
                PRIMARY KEY (id)
            ) {$this->charset_collate};",

            "CREATE TABLE {$this->wpdb->prefix}umh_visa_batches (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                batch_name varchar(255),
                provider_name varchar(255),
                status enum('draft', 'submitted', 'approved', 'rejected'),
                PRIMARY KEY (id)
            ) {$this->charset_collate};",

            "CREATE TABLE {$this->wpdb->prefix}umh_inventory_items (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                item_name varchar(255),
                stock_quantity int(11),
                PRIMARY KEY (id)
            ) {$this->charset_collate};",

            "CREATE TABLE {$this->wpdb->prefix}umh_logistics (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                item_id bigint(20),
                type enum('in', 'out'),
                quantity int(11),
                transaction_date datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) {$this->charset_collate};",

            "CREATE TABLE {$this->wpdb->prefix}umh_logistics_distribution (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                jamaah_id bigint(20),
                item_id bigint(20),
                distributed_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) {$this->charset_collate};",

            "CREATE TABLE {$this->wpdb->prefix}umh_manasik_schedules (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                departure_id bigint(20),
                event_date datetime,
                location text,
                PRIMARY KEY (id)
            ) {$this->charset_collate};",

            "CREATE TABLE {$this->wpdb->prefix}umh_manasik_attendance (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                schedule_id bigint(20),
                jamaah_id bigint(20),
                attended boolean DEFAULT 0,
                PRIMARY KEY (id)
            ) {$this->charset_collate};",

            // HR & Keagenan
            "CREATE TABLE {$this->wpdb->prefix}umh_agents (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                name varchar(255),
                tier enum('silver', 'gold', 'master'),
                phone varchar(20),
                commission_rate decimal(5,2),
                PRIMARY KEY (id)
            ) {$this->charset_collate};",

            "CREATE TABLE {$this->wpdb->prefix}umh_hr_employees (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                name varchar(255),
                position varchar(100),
                salary decimal(15,2),
                PRIMARY KEY (id)
            ) {$this->charset_collate};",

            "CREATE TABLE {$this->wpdb->prefix}umh_hr_attendance (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                employee_id bigint(20),
                clock_in datetime,
                clock_out datetime,
                PRIMARY KEY (id)
            ) {$this->charset_collate};",

            "CREATE TABLE {$this->wpdb->prefix}umh_tasks (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                assigned_to bigint(20),
                task_description text,
                due_date date,
                status enum('pending', 'in_progress', 'completed'),
                PRIMARY KEY (id)
            ) {$this->charset_collate};",

            // Special Services
            "CREATE TABLE {$this->wpdb->prefix}umh_private_requests (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                customer_name varchar(255),
                details text,
                status enum('pending', 'quoted', 'deal', 'lost'),
                PRIMARY KEY (id)
            ) {$this->charset_collate};",

            "CREATE TABLE {$this->wpdb->prefix}umh_private_quotations (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                request_id bigint(20),
                total_cost decimal(15,2),
                margin decimal(15,2),
                final_price decimal(15,2),
                PRIMARY KEY (id)
            ) {$this->charset_collate};",

            "CREATE TABLE {$this->wpdb->prefix}umh_badal_umrah (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                beneficiary_name varchar(255),
                mutawwif_id bigint(20),
                proof_video_url varchar(255),
                status enum('pending', 'processing', 'completed'),
                PRIMARY KEY (id)
            ) {$this->charset_collate};",

            // Customer Care & System
            "CREATE TABLE {$this->wpdb->prefix}umh_support_tickets (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                booking_id bigint(20),
                subject varchar(255),
                status enum('open', 'in_progress', 'closed'),
                PRIMARY KEY (id)
            ) {$this->charset_collate};",

            "CREATE TABLE {$this->wpdb->prefix}umh_support_messages (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                ticket_id bigint(20),
                sender_type enum('user', 'staff'),
                message text,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) {$this->charset_collate};",

            "CREATE TABLE {$this->wpdb->prefix}umh_reviews (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                booking_id bigint(20),
                rating int(1),
                comment text,
                target_type enum('hotel', 'mutawwif', 'general'),
                PRIMARY KEY (id)
            ) {$this->charset_collate};",

            "CREATE TABLE {$this->wpdb->prefix}umh_notifications (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                user_id bigint(20),
                message text,
                is_read boolean DEFAULT 0,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) {$this->charset_collate};",

            "CREATE TABLE {$this->wpdb->prefix}umh_notification_templates (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                name varchar(100),
                content text,
                PRIMARY KEY (id)
            ) {$this->charset_collate};",

            "CREATE TABLE {$this->wpdb->prefix}umh_activity_logs (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                user_id bigint(20),
                action varchar(255),
                details text,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) {$this->charset_collate};",

            "CREATE TABLE {$this->wpdb->prefix}umh_roles (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                role_name varchar(50),
                permissions text,
                PRIMARY KEY (id)
            ) {$this->charset_collate};"
        ];

        foreach ($tables as $sql) {
            dbDelta($sql);
        }
    }
}
