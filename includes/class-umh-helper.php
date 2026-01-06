<?php
/**
 * Helper class for common database operations.
 */
class UMH_Helper {
    public static function get_all($table) {
        global $wpdb;
        return $wpdb->get_results("SELECT * FROM {$wpdb->prefix}umh_{$table} ORDER BY id DESC");
    }

    public static function get_by_id($table, $id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}umh_{$table} WHERE id = %d", $id));
    }

    public static function delete($table, $id) {
        global $wpdb;
        return $wpdb->delete("{$wpdb->prefix}umh_{$table}", ['id' => $id]);
    }
}
