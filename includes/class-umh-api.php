<?php
/**
 * REST API endpoints for the plugin.
 */
class UMH_API {
    public function __construct() {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes() {
        // Public Endpoints
        register_rest_route('umh/v1', '/packages', [
            'methods' => 'GET',
            'callback' => [$this, 'get_packages'],
            'permission_callback' => '__return_true'
        ]);

        register_rest_route('umh/v1', '/packages/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_package_detail'],
            'permission_callback' => '__return_true'
        ]);

        // Authenticated Endpoints
        register_rest_route('umh/v1', '/bookings', [
            'methods' => 'POST',
            'callback' => [$this, 'create_booking'],
            'permission_callback' => [$this, 'check_auth']
        ]);

        register_rest_route('umh/v1', '/my-bookings', [
            'methods' => 'GET',
            'callback' => [$this, 'get_my_bookings'],
            'permission_callback' => [$this, 'check_auth']
        ]);

        register_rest_route('umh/v1', '/savings', [
            'methods' => 'GET',
            'callback' => [$this, 'get_my_savings'],
            'permission_callback' => [$this, 'check_auth']
        ]);
    }

    public function get_packages($request) {
        global $wpdb;
        $packages = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}umh_packages WHERE status = 'active'");
        return new WP_REST_Response($packages, 200);
    }

    public function get_package_detail($request) {
        global $wpdb;
        $id = $request['id'];
        $package = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}umh_packages WHERE id = %d", $id));
        
        if (!$package) {
            return new WP_Error('no_package', 'Package not found', ['status' => 404]);
        }

        $package->itineraries = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}umh_package_itineraries WHERE package_id = %d ORDER BY day_number ASC", $id));
        $package->facilities = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}umh_package_facilities WHERE package_id = %d", $id));
        $package->departures = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}umh_departures WHERE package_id = %d AND status = 'open'", $id));

        return new WP_REST_Response($package, 200);
    }

    public function create_booking($request) {
        $params = $request->get_params();
        $booking_logic = new UMH_Booking();
        $booking_id = $booking_logic->create_booking($params);

        if ($booking_id) {
            return new WP_REST_Response(['message' => 'Booking created', 'booking_id' => $booking_id], 201);
        } else {
            return new WP_Error('booking_failed', 'Failed to create booking', ['status' => 500]);
        }
    }

    public function get_my_bookings($request) {
        global $wpdb;
        $user_id = get_current_user_id();
        $bookings = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}umh_bookings WHERE user_id = %d ORDER BY created_at DESC", $user_id));
        return new WP_REST_Response($bookings, 200);
    }

    public function get_my_savings($request) {
        global $wpdb;
        $user_id = get_current_user_id();
        $account = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}umh_savings_accounts WHERE user_id = %d", $user_id));
        
        if ($account) {
            $account->transactions = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}umh_savings_transactions WHERE account_id = %d ORDER BY transaction_date DESC", $account->id));
        }

        return new WP_REST_Response($account, 200);
    }

    public function check_auth() {
        return is_user_logged_in();
    }
}
