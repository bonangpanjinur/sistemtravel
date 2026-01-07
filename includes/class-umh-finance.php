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
    public function record_payment($data) {
        $booking_id = intval($data['booking_id']);
        $amount = floatval($data['amount']);
        $method = sanitize_text_field($data['payment_method']);
        $status = isset($data['status']) ? sanitize_text_field($data['status']) : 'pending';

        // 1. Insert into Finance table
        $this->wpdb->insert(
            "{$this->wpdb->prefix}umh_finance",
            [
                'booking_id' => $booking_id,
                'transaction_type' => 'income',
                'category' => 'booking_payment',
                'amount' => $amount,
                'payment_method' => $method,
                'payment_proof' => isset($data['payment_proof']) ? sanitize_text_field($data['payment_proof']) : '',
                'status' => $status,
                'transaction_date' => current_time('mysql'),
                'notes' => isset($data['notes']) ? sanitize_textarea_field($data['notes']) : ''
            ]
        );
        $finance_id = $this->wpdb->insert_id;

        // 2. If verified, update booking status
        if ($status == 'verified') {
            $this->check_payment_status($booking_id);
        }

        return $finance_id;
    }

    public function verify_payment($finance_id) {
        $payment = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM {$this->wpdb->prefix}umh_finance WHERE id = %d",
            $finance_id
        ));

        if ($payment && $payment->status == 'pending') {
            $this->wpdb->update(
                "{$this->wpdb->prefix}umh_finance",
                ['status' => 'verified'],
                ['id' => $finance_id]
            );

            if ($payment->booking_id) {
                $this->check_payment_status($payment->booking_id);
            }
            return true;
        }
        return false;
    }

    private function check_payment_status($booking_id) {
        $total_paid = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT SUM(amount) FROM {$this->wpdb->prefix}umh_finance WHERE booking_id = %d AND transaction_type = 'income' AND status = 'verified'",
            $booking_id
        ));

        $booking = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT total_price FROM {$this->wpdb->prefix}umh_bookings WHERE id = %d",
            $booking_id
        ));

        if (!$booking) return;

        $total_price = $booking->total_price;

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
     * Refund Management
     */
    public function request_refund($data) {
        $this->wpdb->insert(
            "{$this->wpdb->prefix}umh_booking_requests",
            [
                'booking_id' => intval($data['booking_id']),
                'request_type' => 'refund',
                'reason' => sanitize_textarea_field($data['reason']),
                'amount_requested' => floatval($data['amount']),
                'status' => 'pending',
                'created_at' => current_time('mysql')
            ]
        );
        return $this->wpdb->insert_id;
    }

    /**
     * Savings Account Logic
     */
    public function deposit_savings($user_id, $amount, $proof = '') {
        $account_id = $this->get_or_create_savings_account($user_id);
        
        $this->wpdb->insert(
            "{$this->wpdb->prefix}umh_savings_transactions",
            [
                'account_id' => $account_id,
                'amount' => $amount,
                'payment_proof' => $proof,
                'status' => 'pending',
                'transaction_date' => current_time('mysql')
            ]
        );
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
                'target_amount' => 30000000,
                'current_balance' => 0,
                'status' => 'active'
            ]
        );
        return $this->wpdb->insert_id;
    }
}
