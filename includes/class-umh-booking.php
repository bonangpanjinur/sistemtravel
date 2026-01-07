<?php
/**
 * Logic for Booking Engine.
 */
class UMH_Booking {
    private $wpdb;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    /**
     * Create a new booking with passengers.
     */
    public function create_booking($data) {
        $departure_id = intval($data['departure_id']);
        $passengers = $data['passengers']; // Array of jamaah data
        
        // 1. Generate Booking Code
        $booking_code = 'UMH-' . date('Ymd') . '-' . strtoupper(wp_generate_password(4, false));

        // 2. Calculate Pricing & Discounts
        $total_price = 0;
        foreach ($passengers as $pax) {
            $total_price += floatval($pax['price']);
        }

        $discount_amount = 0;
        $coupon_id = null;
        if (!empty($data['coupon_code'])) {
            $coupon = $this->validate_coupon($data['coupon_code'], $total_price);
            if ($coupon) {
                $coupon_id = $coupon->id;
                $discount_amount = ($coupon->type == 'percent') ? ($total_price * ($coupon->value / 100)) : $coupon->value;
                if ($coupon->max_discount > 0 && $discount_amount > $coupon->max_discount) {
                    $discount_amount = $coupon->max_discount;
                }
            }
        }

        $final_price = $total_price - $discount_amount;

        // 3. Insert Booking
        $this->wpdb->insert(
            "{$this->wpdb->prefix}umh_bookings",
            [
                'departure_id' => $departure_id,
                'booking_code' => $booking_code,
                'total_price' => $final_price,
                'discount_amount' => $discount_amount,
                'coupon_id' => $coupon_id,
                'agent_id' => isset($data['agent_id']) ? intval($data['agent_id']) : null,
                'contact_name' => sanitize_text_field($data['contact_name']),
                'contact_phone' => sanitize_text_field($data['contact_phone']),
                'contact_email' => sanitize_email($data['contact_email']),
                'total_pax' => count($passengers),
                'status' => 'pending',
                'created_at' => current_time('mysql')
            ]
        );
        $booking_id = $this->wpdb->insert_id;

        // 4. Process Passengers
        foreach ($passengers as $pax) {
            $jamaah_id = $this->get_or_create_jamaah($pax);
            
            $this->wpdb->insert(
                "{$this->wpdb->prefix}umh_booking_passengers",
                [
                    'booking_id' => $booking_id,
                    'jamaah_id' => $jamaah_id,
                    'room_type' => sanitize_text_field($pax['room_type']),
                    'price' => floatval($pax['price'])
                ]
            );

            $this->init_doc_tracking($jamaah_id);
        }

        // 5. Update Seat Quota & Coupon Usage
        $this->update_seat_quota($departure_id, count($passengers));
        if ($coupon_id) {
            $this->wpdb->query($this->wpdb->prepare(
                "UPDATE {$this->wpdb->prefix}umh_coupons SET used_count = used_count + 1 WHERE id = %d",
                $coupon_id
            ));
        }

        // 6. Log Activity
        $this->log_activity($booking_id, 'create_booking', 'New booking created: ' . $booking_code);

        return $booking_id;
    }

    private function validate_coupon($code, $total_price) {
        $coupon = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM {$this->wpdb->prefix}umh_coupons WHERE code = %s AND status = 'active' AND (expiry_date IS NULL OR expiry_date >= CURDATE())",
            $code
        ));

        if ($coupon && $total_price >= $coupon->min_purchase && ($coupon->quota == 0 || $coupon->used_count < $coupon->quota)) {
            return $coupon;
        }
        return false;
    }

    private function get_or_create_jamaah($data) {
        $existing = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT id FROM {$this->wpdb->prefix}umh_jamaah WHERE nik = %s",
            $data['nik']
        ));

        if ($existing) {
            return $existing;
        }

        $this->wpdb->insert(
            "{$this->wpdb->prefix}umh_jamaah",
            [
                'nik' => sanitize_text_field($data['nik']),
                'full_name' => sanitize_text_field($data['full_name']),
                'phone' => sanitize_text_field($data['phone']),
                'gender' => sanitize_text_field($data['gender']),
                'passport_number' => isset($data['passport_number']) ? sanitize_text_field($data['passport_number']) : ''
            ]
        );
        return $this->wpdb->insert_id;
    }

    private function init_doc_tracking($jamaah_id) {
        $docs = ['Paspor', 'KTP', 'KK', 'Buku Nikah', 'Buku Kuning'];
        foreach ($docs as $doc) {
            $this->wpdb->insert(
                "{$this->wpdb->prefix}umh_doc_tracking",
                [
                    'jamaah_id' => $jamaah_id,
                    'doc_type' => $doc,
                    'status' => 'jamaah'
                ]
            );
        }
    }

    private function update_seat_quota($departure_id, $count) {
        $this->wpdb->query($this->wpdb->prepare(
            "UPDATE {$this->wpdb->prefix}umh_departures SET available_seats = available_seats - %d, seat_booked = seat_booked + %d WHERE id = %d",
            $count, $count, $departure_id
        ));
    }

    private function log_activity($record_id, $action, $message) {
        $this->wpdb->insert(
            "{$this->wpdb->prefix}umh_activity_logs",
            [
                'user_id' => get_current_user_id(),
                'action' => $action,
                'table_name' => 'bookings',
                'record_id' => $record_id,
                'new_value' => $message,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? ''
            ]
        );
    }
}
