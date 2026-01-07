<?php
namespace UmhMgmt\Repositories;

class BookingRepository {
    private $table;
    private $departure_table;

    public function __construct() {
        global $wpdb;
        $this->table = $wpdb->prefix . 'umh_bookings';
        $this->departure_table = $wpdb->prefix . 'umh_departures';
    }

    public function create($data) {
        global $wpdb;
        $wpdb->insert($this->table, [
            'departure_id' => $data['departure_id'],
            'customer_user_id' => $data['customer_user_id'],
            'total_price' => $data['total_price'],
            'status' => 'pending'
        ]);
        return $wpdb->insert_id;
    }

    public function decreaseQuota($departure_id, $count) {
        global $wpdb;
        $wpdb->query($wpdb->prepare(
            "UPDATE {$this->departure_table} SET available_seats = available_seats - %d WHERE id = %d",
            $count, $departure_id
        ));
    }
}
