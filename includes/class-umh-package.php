<?php
/**
 * Logic for Package Factory.
 */
class UMH_Package {
    private $wpdb;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    public function save_package($data) {
        $package_id = !empty($data['id']) ? intval($data['id']) : null;
        
        $package_data = [
            'name' => sanitize_text_field($data['name']),
            'category_id' => isset($data['category_id']) ? intval($data['category_id']) : 0,
            'duration_days' => isset($data['duration_days']) ? intval($data['duration_days']) : 9,
            'description' => isset($data['description']) ? sanitize_textarea_field($data['description']) : '',
            'base_price_quad' => isset($data['base_price_quad']) ? floatval($data['base_price_quad']) : 0,
            'base_price_triple' => isset($data['base_price_triple']) ? floatval($data['base_price_triple']) : 0,
            'base_price_double' => isset($data['base_price_double']) ? floatval($data['base_price_double']) : 0,
            'airline_id' => isset($data['airline_id']) ? intval($data['airline_id']) : 0,
            'hotel_makkah_id' => isset($data['hotel_makkah_id']) ? intval($data['hotel_makkah_id']) : 0,
            'hotel_madinah_id' => isset($data['hotel_madinah_id']) ? intval($data['hotel_madinah_id']) : 0,
        ];

        if ($package_id) {
            $this->wpdb->update("{$this->wpdb->prefix}umh_packages", $package_data, ['id' => $package_id]);
        } else {
            $this->wpdb->insert("{$this->wpdb->prefix}umh_packages", $package_data);
            $package_id = $this->wpdb->insert_id;
        }

        // Save Itineraries
        if (isset($data['itineraries'])) {
            $this->save_itineraries($package_id, $data['itineraries']);
        }

        // Save Facilities
        if (isset($data['facilities'])) {
            $this->save_facilities($package_id, $data['facilities']);
        }

        return $package_id;
    }

    private function save_itineraries($package_id, $itineraries) {
        $this->wpdb->delete("{$this->wpdb->prefix}umh_package_itineraries", ['package_id' => $package_id]);

        foreach ($itineraries as $day => $content) {
            if (empty($content['title'])) continue;
            $this->wpdb->insert("{$this->wpdb->prefix}umh_package_itineraries", [
                'package_id' => $package_id,
                'day_number' => intval($day),
                'title' => sanitize_text_field($content['title']),
                'description' => sanitize_textarea_field($content['description'])
            ]);
        }
    }

    private function save_facilities($package_id, $facilities) {
        $this->wpdb->delete("{$this->wpdb->prefix}umh_package_facilities", ['package_id' => $package_id]);

        if (isset($facilities['include'])) {
            foreach ($facilities['include'] as $item) {
                if (empty($item)) continue;
                $this->wpdb->insert("{$this->wpdb->prefix}umh_package_facilities", [
                    'package_id' => $package_id,
                    'item_name' => sanitize_text_field($item),
                    'type' => 'include'
                ]);
            }
        }

        if (isset($facilities['exclude'])) {
            foreach ($facilities['exclude'] as $item) {
                if (empty($item)) continue;
                $this->wpdb->insert("{$this->wpdb->prefix}umh_package_facilities", [
                    'package_id' => $package_id,
                    'item_name' => sanitize_text_field($item),
                    'type' => 'exclude'
                ]);
            }
        }
    }
}
