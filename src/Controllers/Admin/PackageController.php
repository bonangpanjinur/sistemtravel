<?php
namespace UmhMgmt\Controllers\Admin;

use UmhMgmt\Repositories\PackageRepository;
use UmhMgmt\Utils\View;

class PackageController {
    private $repo;

    public function __construct() {
        $this->repo = new PackageRepository();
        add_action('admin_menu', [$this, 'add_submenu_page']);
        add_action('admin_post_umh_save_package', [$this, 'handle_save_package']);
        add_action('admin_post_umh_delete_package', [$this, 'handle_delete_package']);
    }

    public function add_submenu_page() {
        add_submenu_page(
            'umh-dashboard',
            'Manage Packages',
            'Packages',
            'manage_options',
            'umh-packages',
            [$this, 'render_packages']
        );

        add_submenu_page(
            null, // Hidden from menu
            'Add New Package',
            'Add New Package',
            'manage_options',
            'umh-packages-add',
            [$this, 'render_package_form']
        );
    }

    public function render_package_form() {
        global $wpdb;
        $hotels = $wpdb->get_results("SELECT id, name FROM {$wpdb->prefix}umh_hotels");
        $airlines = $wpdb->get_results("SELECT id, name FROM {$wpdb->prefix}umh_airlines");
        
        $data = [
            'hotels' => $hotels,
            'airlines' => $airlines
        ];

        // Jika mode Edit, load data lengkap
        if (isset($_GET['id'])) {
            $id = absint($_GET['id']);
            $package = $this->repo->find($id);
            if ($package) {
                $data['package'] = $package;
                $data['pricing'] = $this->repo->getPricing($id);
                $data['itinerary'] = $this->repo->getItinerary($id);
                $data['facilities'] = $this->repo->getFacilities($id);
            }
        }
        
        View::render('admin/packages/form', $data);
    }

    public function handle_save_package() {
        check_admin_referer('umh_package_nonce');
        if (!current_user_can('manage_options')) wp_die('Unauthorized');

        global $wpdb;
        $wpdb->query('START TRANSACTION');

        try {
            $package_data = [
                'name' => sanitize_text_field($_POST['name']),
                'description' => sanitize_textarea_field($_POST['description']),
                'hotel_mekkah_id' => absint($_POST['hotel_mekkah_id']),
                'hotel_madinah_id' => absint($_POST['hotel_madinah_id']),
                'airline_id' => absint($_POST['airline_id']),
                'departure_airport' => sanitize_text_field($_POST['departure_airport']),
                'package_image_url' => esc_url_raw($_POST['package_image_url']),
            ];

            if (!empty($_POST['id'])) {
                $package_id = absint($_POST['id']);
                $wpdb->update("{$wpdb->prefix}umh_packages", $package_data, ['id' => $package_id]);
            } else {
                $wpdb->insert("{$wpdb->prefix}umh_packages", $package_data);
                $package_id = $wpdb->insert_id;
            }

            // 1. Save Pricing Tiers
            $wpdb->delete("{$wpdb->prefix}umh_package_pricing", ['package_id' => $package_id]);
            $pricing_types = ['quad', 'triple', 'double'];
            foreach ($pricing_types as $type) {
                if (isset($_POST['price_' . $type])) {
                    $wpdb->insert("{$wpdb->prefix}umh_package_pricing", [
                        'package_id' => $package_id,
                        'room_type' => $type,
                        'price' => floatval($_POST['price_' . $type])
                    ]);
                }
            }

            // 2. Save Itinerary
            $wpdb->delete("{$wpdb->prefix}umh_package_itineraries", ['package_id' => $package_id]);
            if (isset($_POST['itinerary']) && is_array($_POST['itinerary'])) {
                foreach ($_POST['itinerary'] as $index => $item) {
                    if (empty($item['title'])) continue;
                    $wpdb->insert("{$wpdb->prefix}umh_package_itineraries", [
                        'package_id' => $package_id,
                        'day_number' => $index + 1,
                        'title' => sanitize_text_field($item['title']),
                        'description' => sanitize_textarea_field($item['description']),
                        'location' => sanitize_text_field($item['location'])
                    ]);
                }
            }

            // 3. Save Facilities (Included/Excluded)
            $wpdb->delete("{$wpdb->prefix}umh_package_facilities", ['package_id' => $package_id]);
            
            // Included
            if (isset($_POST['facilities']['included']) && is_array($_POST['facilities']['included'])) {
                foreach ($_POST['facilities']['included'] as $facility) {
                    if (empty($facility)) continue;
                    $wpdb->insert("{$wpdb->prefix}umh_package_facilities", [
                        'package_id' => $package_id,
                        'facility_name' => sanitize_text_field($facility),
                        'type' => 'included'
                    ]);
                }
            }
            
            // Excluded
            if (isset($_POST['facilities']['excluded']) && is_array($_POST['facilities']['excluded'])) {
                foreach ($_POST['facilities']['excluded'] as $facility) {
                    if (empty($facility)) continue;
                    $wpdb->insert("{$wpdb->prefix}umh_package_facilities", [
                        'package_id' => $package_id,
                        'facility_name' => sanitize_text_field($facility),
                        'type' => 'excluded'
                    ]);
                }
            }

            $wpdb->query('COMMIT');
            wp_redirect(admin_url('admin.php?page=umh-packages&message=saved'));
        } catch (\Exception $e) {
            $wpdb->query('ROLLBACK');
            wp_die('Error saving package: ' . $e->getMessage());
        }
        exit;
    }

    public function handle_delete_package() {
        check_admin_referer('umh_package_nonce');
        if (!current_user_can('manage_options')) wp_die('Unauthorized');

        $id = absint($_GET['id']);
        $this->repo->delete($id);
        wp_redirect(admin_url('admin.php?page=umh-packages'));
        exit;
    }

    public function render_packages() {
        $packages = $this->repo->all();
        View::render('admin/packages/list', ['packages' => $packages]);
    }
}