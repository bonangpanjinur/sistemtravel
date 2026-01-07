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
        // Handle form submission logic here
        // check_admin_referer('umh_booking_nonce');
        // $this->service->createBooking($_POST);
    }
}
