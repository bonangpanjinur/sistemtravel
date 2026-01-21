<?php
// src/Services/Payment/MidtransGateway.php

namespace App\Services\Payment;

use App\Interfaces\PaymentGatewayInterface;
use Midtrans\Config;
use Midtrans\Snap;
use Midtrans\Notification;

/**
 * Class MidtransGateway
 * Implementasi pembayaran menggunakan Midtrans Snap.
 */
class MidtransGateway implements PaymentGatewayInterface {

    public function initialize(array $config) {
        Config::$serverKey = $config['server_key'] ?? '';
        Config::$isProduction = ($config['environment'] ?? 'sandbox') === 'production';
        Config::$isSanitized = true;
        Config::$is3ds = true;
    }

    public function getPaymentUrl($booking, $customer) {
        $params = [
            'transaction_details' => [
                'order_id' => $booking->code . '-' . time(), // Unique Order ID
                'gross_amount' => (int) $booking->total_price,
            ],
            'customer_details' => [
                'first_name' => $customer->display_name,
                'email' => $customer->user_email,
                'phone' => get_user_meta($customer->ID, 'phone_number', true) ?: '08123456789',
            ],
            'callbacks' => [
                'finish' => home_url('/umroh-dashboard?payment_status=success'),
            ]
        ];

        try {
            // Mengembalikan Redirect URL Snap
            return Snap::createTransaction($params)->redirect_url;
        } catch (\Exception $e) {
            error_log('Midtrans Error: ' . $e->getMessage());
            throw new \Exception('Gagal menghubungi server pembayaran.');
        }
    }

    public function handleNotification($request) {
        // Midtrans SDK membaca php://input secara otomatis
        $notif = new Notification();

        $transaction = $notif->transaction_status;
        $order_id = $notif->order_id;
        
        // Extract Booking Code (misal: BOOK123-167823 -> BOOK123)
        $booking_code = explode('-', $order_id)[0];
        
        $status = 'pending';
        if ($transaction == 'capture' || $transaction == 'settlement') {
            $status = 'paid';
        } else if ($transaction == 'deny' || $transaction == 'expire' || $transaction == 'cancel') {
            $status = 'cancelled';
        }

        return [
            'status' => $status,
            'booking_code' => $booking_code,
            'amount' => $notif->gross_amount,
            'raw_response' => json_encode($notif)
        ];
    }
}