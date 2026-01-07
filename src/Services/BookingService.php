<?php
namespace UmhMgmt\Services;

use UmhMgmt\Repositories\BookingRepository;
use Exception;

class BookingService {
    private $repo;

    public function __construct(BookingRepository $repo) {
        $this->repo = $repo;
    }

    public function createBooking($data) {
        global $wpdb;

        // 1. Validasi Dokumen (Audit: Document Tracking)
        if (isset($data['passport_expiry']) && isset($data['departure_date'])) {
            if (DocumentService::isPassportExpiringSoon($data['passport_expiry'], $data['departure_date'])) {
                throw new Exception("Paspor harus berlaku minimal 6 bulan sebelum keberangkatan.");
            }
        }

        // 2. Transaksi Database (Audit: Race Condition)
        $wpdb->query('START TRANSACTION');

        try {
            // 3. LOCK ROW: Cek Stok dengan 'FOR UPDATE'
            $departure = $wpdb->get_row($wpdb->prepare(
                "SELECT available_seats FROM {$wpdb->prefix}umh_departures WHERE id = %d FOR UPDATE", 
                $data['departure_id']
            ));
            
            if (!$departure || $departure->available_seats < count($data['passengers'])) {
                throw new Exception("Mohon maaf, kursi baru saja habis.");
            }

            // 4. Proses Insert Booking & Kurangi Stok
            $bookingId = $this->repo->create($data);
            $this->repo->decreaseQuota($data['departure_id'], count($data['passengers']));

            // 5. Commit
            $wpdb->query('COMMIT');

            // 6. Notifikasi (Event Driven)
            do_action('umh_booking_created', $bookingId);

            return $bookingId;

        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            throw $e;
        }
    }
}
