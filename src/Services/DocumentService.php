<?php
namespace UmhMgmt\Services;

class DocumentService {
    /**
     * Check if passport expires within 6 months of departure
     */
    public static function isPassportExpiringSoon($expiry_date, $departure_date) {
        $expiry = strtotime($expiry_date);
        $departure = strtotime($departure_date);
        
        // 6 months in seconds (approximate)
        $six_months = 6 * 30 * 24 * 60 * 60;
        
        return ($expiry - $departure) < $six_months;
    }
}
