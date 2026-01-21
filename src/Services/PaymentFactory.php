<?php
// src/Services/PaymentFactory.php

namespace App\Services;

use App\Services\Payment\MidtransGateway;
// use App\Services\Payment\XenditGateway; // Nanti tinggal tambah ini

/**
 * Class PaymentFactory
 * Bertugas memilih gateway mana yang akan digunakan berdasarkan settings.
 */
class PaymentFactory {

    public static function create($gatewayName) {
        
        // Ambil setting dari database
        $settings = get_option('umh_payment_settings', []);

        switch ($gatewayName) {
            case 'midtrans':
                $gateway = new MidtransGateway();
                $gateway->initialize([
                    'server_key' => $settings['midtrans_server_key'] ?? '',
                    'environment' => $settings['midtrans_mode'] ?? 'sandbox'
                ]);
                return $gateway;
            
            // case 'xendit':
            //     return new XenditGateway();

            default:
                throw new \Exception("Payment Gateway '$gatewayName' tidak ditemukan.");
        }
    }
}