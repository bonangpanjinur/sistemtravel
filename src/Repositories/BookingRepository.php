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
        
        // Get current user's branch if applicable
        $branch_id = isset($data['branch_id']) ? $data['branch_id'] : get_user_meta(get_current_user_id(), 'umh_branch_id', true);

        $wpdb->insert($this->table, [
            'departure_id' => $data['departure_id'],
            'branch_id' => $branch_id ?: null,
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

    public function countAll() {
        global $wpdb;
        return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$this->table}");
    }

    public function sumRevenue() {
        global $wpdb;
        return (float) $wpdb->get_var("SELECT SUM(total_price) FROM {$this->table} WHERE status = 'paid'");
    }
}
