<?php
/**
 * Fired during plugin activation.
 */
class UMH_Activator {
    public static function activate() {
        require_once plugin_dir_path(__FILE__) . 'class-umh-db.php';
        $db = new UMH_DB();
        $db->create_tables();
    }
}
