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
    }

    public function add_plugin_admin_menu() {
        add_menu_page('Umroh Management', 'Umroh Mgmt', 'manage_options', 'umh-dashboard', [$this, 'display_dashboard'], 'dashicons-airplane', 6);
        add_submenu_page('umh-dashboard', 'Master Data', 'Master Data', 'manage_options', 'umh-master', [$this, 'display_master_data']);
        add_submenu_page('umh-dashboard', 'Packages', 'Packages', 'manage_options', 'umh-packages', [$this, 'display_packages']);
        add_submenu_page('umh-dashboard', 'Bookings', 'Bookings', 'manage_options', 'umh-bookings', [$this, 'display_bookings']);
    }

    public function handle_crud_actions() {
        if (!isset($_POST['umh_action']) || !check_admin_referer('umh_nonce')) return;

        $action = $_POST['umh_action'];
        $table = $_POST['umh_table'];

        if ($action == 'save_hotel') {
            $this->wpdb->replace("{$this->wpdb->prefix}umh_master_hotels", [
                'id' => $_POST['id'] ?: null,
                'name' => sanitize_text_field($_POST['name']),
                'star_rating' => intval($_POST['star_rating']),
                'facilities' => sanitize_textarea_field($_POST['facilities'])
            ]);
            wp_redirect(admin_url('admin.php?page=umh-master&tab=hotels&message=success'));
            exit;
        }

        if (isset($_GET['action']) && $_GET['action'] == 'delete') {
            $id = intval($_GET['id']);
            $table = sanitize_text_field($_GET['table']);
            $this->wpdb->delete("{$this->wpdb->prefix}umh_{$table}", ['id' => $id]);
            wp_redirect(admin_url('admin.php?page=umh-master&tab=' . str_replace('master_', '', $table) . '&message=deleted'));
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
                <h3>Tambah/Edit <?php echo ucfirst($tab); ?></h3>
                <form method="post">
                    <?php wp_nonce_field('umh_nonce'); ?>
                    <input type="hidden" name="umh_action" value="save_hotel">
                    <input type="hidden" name="umh_table" value="master_hotels">
                    <table class="form-table">
                        <tr>
                            <th>Nama</th>
                            <td><input type="text" name="name" class="regular-text" required></td>
                        </tr>
                        <?php if ($tab == 'hotels'): ?>
                        <tr>
                            <th>Rating Bintang</th>
                            <td><input type="number" name="star_rating" min="1" max="5" value="5"></td>
                        </tr>
                        <?php endif; ?>
                    </table>
                    <p class="submit"><input type="submit" class="button button-primary" value="Simpan Data"></p>
                </form>

                <hr>

                <h3>Daftar Data</h3>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nama</th>
                            <?php if ($tab == 'hotels'): ?><th>Rating</th><?php endif; ?>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($items): foreach ($items as $item): ?>
                        <tr>
                            <td><?php echo $item->id; ?></td>
                            <td><?php echo $item->name; ?></td>
                            <?php if ($tab == 'hotels'): ?><td><?php echo $item->star_rating; ?> ★</td><?php endif; ?>
                            <td>
                                <a href="?page=umh-master&tab=<?php echo $tab; ?>&action=edit&id=<?php echo $item->id; ?>">Edit</a> | 
                                <a href="<?php echo wp_nonce_url("?page=umh-master&tab=$tab&action=delete&table=master_$tab&id=$item->id", 'umh_nonce'); ?>" style="color:red;">Hapus</a>
                            </td>
                        </tr>
                        <?php endforeach; else: ?>
                        <tr><td colspan="4">Belum ada data.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }

    public function display_packages() { echo '<div class="wrap"><h1>Packages</h1></div>'; }
    public function display_bookings() { echo '<div class="wrap"><h1>Bookings</h1></div>'; }
}
