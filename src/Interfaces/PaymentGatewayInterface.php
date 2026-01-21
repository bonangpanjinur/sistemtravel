<?php
// src/Interfaces/PaymentGatewayInterface.php

namespace UmhMgmt\Interfaces;

/**
 * Interface PaymentGatewayInterface
 * Kontrak standar untuk semua payment gateway (Midtrans, Xendit, dll).
 */
interface PaymentGatewayInterface {
    
    /**
     * Inisialisasi konfigurasi gateway (API Key, Environment).
     * @param array $config
     */
    public function initialize(array $config);

    /**
     * Mendapatkan URL pembayaran atau Snap Token.
     * * @param object $booking Data booking dari database
     * @param object $customer Data user/customer (nama, email, phone)
     * @return string URL redirect atau Token
     */
    public function getPaymentUrl($booking, $customer);

    /**
     * Menangani notifikasi webhook dari gateway.
     * * @param array $request Data POST dari webhook
     * @return array [status => 'paid/pending/fail', booking_code => '...']
     */
    public function handleNotification($request);
}