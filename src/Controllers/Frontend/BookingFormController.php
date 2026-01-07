<?php
namespace UmhMgmt\Controllers\Frontend;

use UmhMgmt\Services\BookingService;
use UmhMgmt\Repositories\BookingRepository;
use UmhMgmt\Utils\View;

class BookingFormController {
    private $service;

    public function __construct() {
        $this->service = new BookingService(new BookingRepository());
        add_shortcode('umh_booking_form', [$this, 'render_form']);
        add_action('admin_post_nopriv_umh_submit_booking', [$this, 'handle_form_submission']);
        add_action('admin_post_umh_submit_booking', [$this, 'handle_form_submission']);
    }

    public function render_form($atts) {
        ob_start();
        View::render('frontend/booking-form', ['atts' => $atts]);
        return ob_get_clean();
    }

    public function handle_form_submission() {
        if (!isset($_POST['umh_booking_nonce']) || !wp_verify_nonce($_POST['umh_booking_nonce'], 'umh_booking_nonce')) {
            wp_die('Security check failed');
        }

        $sanitized_data = [
            'departure_id' => isset($_POST['departure_id']) ? absint($_POST['departure_id']) : 0,
            'passengers' => []
        ];

        if (isset($_POST['passengers']) && is_array($_POST['passengers'])) {
            foreach ($_POST['passengers'] as $passenger) {
                $sanitized_data['passengers'][] = [
                    'name' => isset($passenger['name']) ? sanitize_text_field($passenger['name']) : '',
                    'passport_number' => isset($passenger['passport_number']) ? sanitize_text_field($passenger['passport_number']) : '',
                    'passport_expiry' => isset($passenger['passport_expiry']) ? sanitize_text_field($passenger['passport_expiry']) : '',
                ];
            }
        }

        try {
            $booking_id = $this->service->createBooking($sanitized_data);
            wp_redirect(add_query_arg('booking_status', 'success', wp_get_referer()));
            exit;
        } catch (\Exception $e) {
            wp_die($e->getMessage());
        }
    }
}
