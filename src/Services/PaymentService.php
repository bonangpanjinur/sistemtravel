<?php
// src/Services/PaymentService.php

namespace App\Services;

use App\Core\Container;
use App\Repositories\BookingRepository;
use App\Services\PaymentFactory;

class PaymentService {

    private $bookingRepo;

    public function __construct() {
        $this->bookingRepo = Container::get(BookingRepository::class);
    }

    /**
     * Memproses pembayaran untuk booking tertentu.
     * @return string URL Redirect Pembayaran
     */
    public function processOnlinePayment($bookingId) {
        $booking = $this->bookingRepo->find($bookingId);
        if (!$booking) throw new \Exception("Booking tidak ditemukan.");

        $customer = get_userdata($booking->customer_user_id);

        // 1. Cek Gateway Aktif dari Setting
        $activeGateway = get_option('umh_active_gateway', 'manual'); // Default manual

        if ($activeGateway === 'manual') {
            throw new \Exception("Pembayaran online tidak aktif.");
        }

        // 2. Buat Instance Gateway via Factory
        $gateway = PaymentFactory::create($activeGateway);

        // 3. Minta URL Pembayaran
        return $gateway->getPaymentUrl($booking, $customer);
    }
    
    /**
     * Handle Webhook dari gateway manapun.
     */
    public function handleWebhook() {
        $activeGateway = get_option('umh_active_gateway', 'midtrans');
        $gateway = PaymentFactory::create($activeGateway);
        
        $result = $gateway->handleNotification($_POST);
        
        if ($result['status'] === 'paid') {
            // Update Database: Set Booking to Paid
            // Container::get(BookingRepository::class)->updateStatus($result['booking_code'], 'paid');
            error_log("Payment Success for: " . $result['booking_code']);
        }
    }
}