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
        $departure_id = $data['departure_id'];
        $passengers = $data['passengers']; // Array of jamaah data
        
        // 1. Generate Booking Code
        $booking_code = 'UMH-' . strtoupper(wp_generate_password(6, false));

        // Get total price
        $total_price = isset($data['total_price']) ? $data['total_price'] : 0;

        // 2. Insert Booking
        $this->wpdb->insert(
            "{$this->wpdb->prefix}umh_bookings",
            [
                'departure_id' => $departure_id,
                'booking_code' => $booking_code,
                'total_price' => $total_price,
                'status' => 'pending',
                'created_at' => current_time('mysql')
            ]
        );
        $booking_id = $this->wpdb->insert_id;

        // 3. Process Passengers
        foreach ($passengers as $pax) {
            // Check if jamaah exists by NIK
            $jamaah_id = $this->get_or_create_jamaah($pax);
            
            // Insert into booking_passengers
            $this->wpdb->insert(
                "{$this->wpdb->prefix}umh_booking_passengers",
                [
                    'booking_id' => $booking_id,
                    'jamaah_id' => $jamaah_id,
                    'room_type' => $pax['room_type']
                ]
            );

            // Initialize Document Tracking
            $this->init_doc_tracking($jamaah_id);
        }

        // 4. Update Seat Quota
        $this->update_seat_quota($departure_id, count($passengers));

        return $booking_id;
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
                'nik' => $data['nik'],
                'full_name' => $data['full_name'],
                'phone' => $data['phone'],
                'passport_number' => $data['passport_number'] ?? '',
                'passport_expiry' => $data['passport_expiry'] ?? null
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
}
