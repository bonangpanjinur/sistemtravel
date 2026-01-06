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
        $package_id = $data['id'] ?: null;
        
        $package_data = [
            'name' => sanitize_text_field($data['name']),
            'category_id' => intval($data['category_id']),
            'duration_days' => intval($data['duration_days']),
            'description' => sanitize_textarea_field($data['description'])
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

        return $package_id;
    }

    private function save_itineraries($package_id, $itineraries) {
        // Clear existing itineraries first
        $this->wpdb->delete("{$this->wpdb->prefix}umh_package_itineraries", ['package_id' => $package_id]);

        foreach ($itineraries as $day => $content) {
            $this->wpdb->insert("{$this->wpdb->prefix}umh_package_itineraries", [
                'package_id' => $package_id,
                'day_number' => intval($day),
                'activity_title' => sanitize_text_field($content['title']),
                'activity_description' => sanitize_textarea_field($content['description'])
            ]);
        }
    }
}
