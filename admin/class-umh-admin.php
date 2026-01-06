<?php
/**
 * Admin-facing functionality of the plugin.
 */
class UMH_Admin {
    private $wpdb;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        add_action('admin_menu', [$this, 'add_plugin_admin_menu']);
        add_action('admin_init', [$this, 'handle_crud_actions']);
        add_action('wp_ajax_umh_save_package', [$this, 'ajax_save_package']);
    }

    public function add_plugin_admin_menu() {
        add_menu_page('Umroh Management', 'Umroh Mgmt', 'manage_options', 'umh-dashboard', [$this, 'display_dashboard'], 'dashicons-airplane', 6);
        add_submenu_page('umh-dashboard', 'Master Data', 'Master Data', 'manage_options', 'umh-master', [$this, 'display_master_data']);
        add_submenu_page('umh-dashboard', 'Packages', 'Packages', 'manage_options', 'umh-packages', [$this, 'display_packages']);
        add_submenu_page('umh-dashboard', 'Bookings', 'Bookings', 'manage_options', 'umh-bookings', [$this, 'display_bookings']);
    }

    public function handle_crud_actions() {
        if (isset($_GET['action']) && $_GET['action'] == 'delete' && check_admin_referer('umh_nonce')) {
            $id = intval($_GET['id']);
            $table = sanitize_text_field($_GET['table']);
            $this->wpdb->delete("{$this->wpdb->prefix}umh_{$table}", ['id' => $id]);
            wp_redirect(remove_query_arg(['action', 'id', 'table', '_wpnonce']));
            exit;
        }
    }

    public function display_dashboard() {
        echo '<div class="wrap"><h1>Umroh Management Dashboard</h1><p>Selamat datang di sistem manajemen umroh enterprise.</p></div>';
    }

    public function display_master_data() {
        $tab = isset($_GET['tab']) ? $_GET['tab'] : 'hotels';
        $items = UMH_Helper::get_all('master_' . $tab);
        ?>
        <div class="wrap">
            <h1>Master Data: <?php echo ucfirst($tab); ?></h1>
            <h2 class="nav-tab-wrapper">
                <a href="?page=umh-master&tab=hotels" class="nav-tab <?php echo $tab == 'hotels' ? 'nav-tab-active' : ''; ?>">Hotels</a>
                <a href="?page=umh-master&tab=airlines" class="nav-tab <?php echo $tab == 'airlines' ? 'nav-tab-active' : ''; ?>">Airlines</a>
            </h2>
            <div class="umh-content" style="margin-top: 20px; background: #fff; padding: 20px; border: 1px solid #ccd0d4;">
                <table class="wp-list-table widefat fixed striped">
                    <thead><tr><th>ID</th><th>Nama</th><th>Aksi</th></tr></thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                        <tr>
                            <td><?php echo $item->id; ?></td>
                            <td><?php echo $item->name; ?></td>
                            <td><a href="<?php echo wp_nonce_url("?page=umh-master&tab=$tab&action=delete&table=master_$tab&id=$item->id", 'umh_nonce'); ?>" style="color:red;">Hapus</a></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }

    public function display_packages() {
        $packages = UMH_Helper::get_all('packages');
        ?>
        <div class="wrap">
            <h1>Package Factory</h1>
            <div class="umh-content" style="margin-top: 20px; background: #fff; padding: 20px; border: 1px solid #ccd0d4;">
                <h3>Buat Paket Baru</h3>
                <form id="umh-package-form">
                    <table class="form-table">
                        <tr><th>Nama Paket</th><td><input type="text" name="name" class="regular-text" required></td></tr>
                        <tr><th>Durasi (Hari)</th><td><input type="number" name="duration_days" value="9"></td></tr>
                    </table>
                    <h4>Itinerary</h4>
                    <div id="itinerary-container">
                        <div class="itinerary-day" style="border: 1px solid #eee; padding: 10px; margin-bottom: 10px;">
                            <strong>Hari 1</strong>: <input type="text" name="itineraries[1][title]" placeholder="Judul Kegiatan">
                            <textarea name="itineraries[1][description]" style="width:100%;" placeholder="Deskripsi..."></textarea>
                        </div>
                    </div>
                    <button type="button" class="button" onclick="addDay()">Tambah Hari</button>
                    <p class="submit"><input type="submit" class="button button-primary" value="Simpan Paket"></p>
                </form>
            </div>
        </div>
        <script>
            let dayCount = 1;
            function addDay() {
                dayCount++;
                const container = document.getElementById('itinerary-container');
                const div = document.createElement('div');
                div.className = 'itinerary-day';
                div.style = 'border: 1px solid #eee; padding: 10px; margin-bottom: 10px;';
                div.innerHTML = `<strong>Hari ${dayCount}</strong>: <input type="text" name="itineraries[${dayCount}][title]" placeholder="Judul Kegiatan">
                                 <textarea name="itineraries[${dayCount}][description]" style="width:100%;" placeholder="Deskripsi..."></textarea>`;
                container.appendChild(div);
            }
        </script>
        <?php
    }

    public function display_bookings() { echo '<div class="wrap"><h1>Bookings</h1></div>'; }
}
