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
                'transaction_type' => 'income',
                'amount' => $amount,
                'payment_method' => $method,
                'status' => 'verified',
                'transaction_date' => current_time('mysql')
            ]
        );

        // 2. Check if booking is fully paid
        $this->check_payment_status($booking_id);

        return $this->wpdb->insert_id;
    }

    private function check_payment_status($booking_id) {
        $total_paid = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT SUM(amount) FROM {$this->wpdb->prefix}umh_finance WHERE booking_id = %d AND transaction_type = 'income' AND status = 'verified'",
            $booking_id
        ));

        $total_price = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT total_price FROM {$this->wpdb->prefix}umh_bookings WHERE id = %d",
            $booking_id
        ));

        // Update total_paid in bookings table
        $this->wpdb->update(
            "{$this->wpdb->prefix}umh_bookings",
            ['total_paid' => $total_paid],
            ['id' => $booking_id]
        );

        if ($total_paid >= $total_price) {
            $this->wpdb->update(
                "{$this->wpdb->prefix}umh_bookings",
                ['payment_status' => 'paid', 'status' => 'confirmed'],
                ['id' => $booking_id]
            );
        } elseif ($total_paid > 0) {
            $this->wpdb->update(
                "{$this->wpdb->prefix}umh_bookings",
                ['payment_status' => 'partial'],
                ['id' => $booking_id]
            );
        }
    }

    /**
     * Savings Account Logic
     */
    public function deposit_savings($user_id, $amount) {
        $account_id = $this->get_or_create_savings_account($user_id);
        
        $this->wpdb->insert(
            "{$this->wpdb->prefix}umh_savings_transactions",
            [
                'account_id' => $account_id,
                'type' => 'deposit',
                'amount' => $amount,
                'status' => 'verified',
                'transaction_date' => current_time('mysql')
            ]
        );

        $this->wpdb->query($this->wpdb->prepare(
            "UPDATE {$this->wpdb->prefix}umh_savings_accounts SET current_balance = current_balance + %f WHERE id = %d",
            $amount, $account_id
        ));
    }

    private function get_or_create_savings_account($user_id) {
        $existing = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT id FROM {$this->wpdb->prefix}umh_savings_accounts WHERE user_id = %d",
            $user_id
        ));

        if ($existing) {
            return $existing;
        }

        $this->wpdb->insert(
            "{$this->wpdb->prefix}umh_savings_accounts",
            [
                'user_id' => $user_id,
                'target_amount' => 30000000, // Default target
                'current_balance' => 0,
                'status' => 'active'
            ]
        );
        return $this->wpdb->insert_id;
    }
}
