<?php
/**
 * Logic for Finance & Invoicing.
 */
class UMH_Finance {
    private $wpdb;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    /**
     * Record a payment for a booking.
     */
    public function record_payment($booking_id, $amount, $method) {
        // 1. Insert into Finance table
        $this->wpdb->insert(
            "{$this->wpdb->prefix}umh_finance",
            [
                'booking_id' => $booking_id,
                'type' => 'income',
                'amount' => $amount,
                'payment_method' => $method,
                'transaction_date' => current_time('mysql')
            ]
        );

        // 2. Check if booking is fully paid
        $this->check_payment_status($booking_id);

        return $this->wpdb->insert_id;
    }

    private function check_payment_status($booking_id) {
        $total_paid = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT SUM(amount) FROM {$this->wpdb->prefix}umh_finance WHERE booking_id = %d AND type = 'income'",
            $booking_id
        ));

        $total_amount = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT total_amount FROM {$this->wpdb->prefix}umh_bookings WHERE id = %d",
            $booking_id
        ));

        if ($total_paid >= $total_amount) {
            $this->wpdb->update(
                "{$this->wpdb->prefix}umh_bookings",
                ['status' => 'confirmed'],
                ['id' => $booking_id]
            );
        }
    }

    /**
     * Savings Account Logic
     */
    public function deposit_savings($jamaah_id, $amount) {
        $account_id = $this->get_or_create_savings_account($jamaah_id);
        
        $this->wpdb->insert(
            "{$this->wpdb->prefix}umh_savings_transactions",
            [
                'account_id' => $account_id,
                'type' => 'deposit',
                'amount' => $amount,
                'transaction_date' => current_time('mysql')
            ]
        );

        $this->wpdb->query($this->wpdb->prepare(
            "UPDATE {$this->wpdb->prefix}umh_savings_accounts SET balance = balance + %f WHERE id = %d",
            $amount, $account_id
        ));
    }

    private function get_or_create_savings_account($jamaah_id) {
        $existing = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT id FROM {$this->wpdb->prefix}umh_savings_accounts WHERE jamaah_id = %d",
            $jamaah_id
        ));

        if ($existing) {
            return $existing;
        }

        $account_number = 'SAV-' . date('Ymd') . '-' . $jamaah_id;
        $this->wpdb->insert(
            "{$this->wpdb->prefix}umh_savings_accounts",
            [
                'jamaah_id' => $jamaah_id,
                'account_number' => $account_number,
                'balance' => 0
            ]
        );
        return $this->wpdb->insert_id;
    }
}
