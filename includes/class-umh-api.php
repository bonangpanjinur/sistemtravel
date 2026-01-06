<?php
/**
 * REST API endpoints for the plugin.
 */
class UMH_API {
    public function __construct() {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes() {
        register_rest_route('umh/v1', '/packages', [
            'methods' => 'GET',
            'callback' => [$this, 'get_packages'],
            'permission_callback' => '__return_true'
        ]);

        register_rest_route('umh/v1', '/bookings', [
            'methods' => 'POST',
            'callback' => [$this, 'create_booking'],
            'permission_callback' => [$this, 'check_auth']
        ]);
    }

    public function get_packages($request) {
        global $wpdb;
        $packages = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}umh_packages");
        return new WP_REST_Response($packages, 200);
    }

    public function create_booking($request) {
        // Logic for creating booking
        return new WP_REST_Response(['message' => 'Booking created'], 201);
    }

    public function check_auth() {
        return current_user_can('read');
    }
}
