<?php
// Folder: src/Services/
// File: AgentService.php

namespace App\Services;

class AgentService {
    private $wpdb;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    /**
     * Daftarkan agen baru dengan Upline
     */
    public function registerAgent($agentId, $uplineId = null, $tier = 'silver') {
        return $this->wpdb->insert(
            $this->wpdb->prefix . 'umh_agent_relations',
            [
                'agent_id' => $agentId,
                'upline_id' => $uplineId, // NULL jika tidak punya upline (Top Level)
                'tier_level' => $tier,
                'joined_at' => current_time('mysql')
            ]
        );
    }

    /**
     * Hitung & Distribusikan Komisi (MLM Logic)
     */
    public function distributeCommission($bookingId, $agentId, $baseAmount) {
        // 1. Catat Komisi Agen Penjual (Direct)
        $this->recordCommission($agentId, $bookingId, $baseAmount, 'Komisi Penjualan Langsung');

        // 2. Cek Upline & Hitung Overriding
        $upline = $this->getUpline($agentId);
        if ($upline) {
            // Misal: Upline dapat 10% dari komisi downline
            $overrideAmount = $baseAmount * 0.10; 
            
            if ($overrideAmount > 0) {
                $this->recordCommission(
                    $upline->upline_id, 
                    $bookingId, 
                    $overrideAmount, 
                    'Komisi Overriding dari Downline #' . $agentId
                );
            }
            
            // Jika mau multi-level (Kakek/Nenek agen), bisa diloop disini
        }
    }

    /**
     * Tambah Poin Reward
     */
    public function addPoints($agentId, $points, $bookingId = null) {
        return $this->wpdb->insert(
            $this->wpdb->prefix . 'umh_agent_points',
            [
                'agent_id' => $agentId,
                'points' => $points,
                'type' => 'earned',
                'source_booking_id' => $bookingId,
                'description' => 'Reward Booking #' . $bookingId
            ]
        );
    }

    /**
     * Get Total Poin Aktif
     */
    public function getBalancePoints($agentId) {
        $earned = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT SUM(points) FROM {$this->wpdb->prefix}umh_agent_points WHERE agent_id = %d AND type = 'earned'", $agentId
        ));
        $redeemed = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT SUM(points) FROM {$this->wpdb->prefix}umh_agent_points WHERE agent_id = %d AND type = 'redeemed'", $agentId
        ));
        
        return intval($earned) - intval($redeemed);
    }

    private function getUpline($agentId) {
        return $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM {$this->wpdb->prefix}umh_agent_relations WHERE agent_id = %d",
            $agentId
        ));
    }

    private function recordCommission($agentId, $bookingId, $amount, $desc) {
        $this->wpdb->insert($this->wpdb->prefix . 'umh_commissions', [
            'agent_id' => $agentId,
            'booking_id' => $bookingId,
            'amount' => $amount,
            'status' => 'pending',
            'description' => $desc,
            'created_at' => current_time('mysql')
        ]);
    }
}