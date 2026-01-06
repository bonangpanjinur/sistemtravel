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
        add_action('admin_post_umh_save_master', [$this, 'handle_save_master']);
    }

    public function add_plugin_admin_menu() {
        add_menu_page('Umroh Management', 'Umroh Mgmt', 'manage_options', 'umh-dashboard', [$this, 'display_dashboard'], 'dashicons-airplane', 6);
        add_submenu_page('umh-dashboard', 'Master Data', 'Master Data', 'manage_options', 'umh-master', [$this, 'display_master_data']);
        add_submenu_page('umh-dashboard', 'Packages', 'Packages', 'manage_options', 'umh-packages', [$this, 'display_packages']);
        add_submenu_page('umh-dashboard', 'Bookings', 'Bookings', 'manage_options', 'umh-bookings', [$this, 'display_bookings']);
        add_submenu_page('umh-dashboard', 'Finance', 'Finance', 'manage_options', 'umh-finance', [$this, 'display_finance']);
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
        $total_packages = $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->wpdb->prefix}umh_packages");
        $total_bookings = $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->wpdb->prefix}umh_bookings");
        $total_revenue = $this->wpdb->get_var("SELECT SUM(amount) FROM {$this->wpdb->prefix}umh_finance WHERE type = 'income'");
        ?>
        <div class="wrap">
            <h1>Umroh Management Dashboard</h1>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-top: 20px;">
                <div style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-left: 4px solid #2271b1;">
                    <h3>Total Paket</h3>
                    <p style="font-size: 24px; font-weight: bold;"><?php echo $total_packages; ?></p>
                </div>
                <div style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-left: 4px solid #d63638;">
                    <h3>Total Booking</h3>
                    <p style="font-size: 24px; font-weight: bold;"><?php echo $total_bookings; ?></p>
                </div>
                <div style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-left: 4px solid #673ab7;">
                    <h3>Total Pendapatan</h3>
                    <p style="font-size: 24px; font-weight: bold;">Rp <?php echo number_format($total_revenue, 0, ',', '.'); ?></p>
                </div>
            </div>
        </div>
        <?php
    }

    public function handle_save_master() {
        if (!current_user_can('manage_options')) return;
        check_admin_referer('umh_master_nonce');

        $tab = sanitize_text_field($_POST['tab']);
        $name = sanitize_text_field($_POST['name']);
        
        $this->wpdb->insert("{$this->wpdb->prefix}umh_master_{$tab}", ['name' => $name]);
        
        wp_redirect(admin_url("admin.php?page=umh-master&tab={$tab}"));
        exit;
    }

    public function ajax_save_package() {
        check_ajax_referer('umh_package_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');

        $package_logic = new UMH_Package();
        $package_id = $package_logic->save_package($_POST);

        if ($package_id) {
            wp_send_json_success(['package_id' => $package_id, 'message' => 'Paket berhasil disimpan!']);
        } else {
            wp_send_json_error('Gagal menyimpan paket.');
        }
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
                <a href="?page=umh-master&tab=locations" class="nav-tab <?php echo $tab == 'locations' ? 'nav-tab-active' : ''; ?>">Locations</a>
                <a href="?page=umh-master&tab=mutawwifs" class="nav-tab <?php echo $tab == 'mutawwifs' ? 'nav-tab-active' : ''; ?>">Mutawwifs</a>
            </h2>

            <div style="display: flex; gap: 20px; margin-top: 20px;">
                <div style="flex: 1; background: #fff; padding: 20px; border: 1px solid #ccd0d4;">
                    <h3>Tambah <?php echo ucfirst(substr($tab, 0, -1)); ?> Baru</h3>
                    <form action="<?php echo admin_url('admin-post.php'); ?>" method="post">
                        <input type="hidden" name="action" value="umh_save_master">
                        <input type="hidden" name="tab" value="<?php echo $tab; ?>">
                        <?php wp_nonce_field('umh_master_nonce'); ?>
                        <table class="form-table">
                            <tr>
                                <th>Nama</th>
                                <td><input type="text" name="name" class="regular-text" required></td>
                            </tr>
                        </table>
                        <p class="submit"><input type="submit" class="button button-primary" value="Tambah"></p>
                    </form>
                </div>

                <div style="flex: 2; background: #fff; padding: 20px; border: 1px solid #ccd0d4;">
                    <table class="wp-list-table widefat fixed striped">
                        <thead><tr><th>ID</th><th>Nama</th><th>Aksi</th></tr></thead>
                        <tbody>
                            <?php if (empty($items)): ?>
                                <tr><td colspan="3">Belum ada data.</td></tr>
                            <?php else: ?>
                                <?php foreach ($items as $item): ?>
                                <tr>
                                    <td><?php echo $item->id; ?></td>
                                    <td><?php echo $item->name; ?></td>
                                    <td><a href="<?php echo wp_nonce_url("?page=umh-master&tab=$tab&action=delete&table=master_$tab&id=$item->id", 'umh_nonce'); ?>" style="color:red;" onclick="return confirm('Yakin ingin menghapus?')">Hapus</a></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
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

            document.getElementById('umh-package-form').addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                formData.append('action', 'umh_save_package');
                formData.append('nonce', '<?php echo wp_create_nonce("umh_package_nonce"); ?>');

                fetch(ajaxurl, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.data.message);
                        location.reload();
                    } else {
                        alert('Error: ' + data.data);
                    }
                });
            });
        </script>
        <?php
    }

    public function display_bookings() {
        $bookings = $this->wpdb->get_results("
            SELECT b.*, p.name as package_name 
            FROM {$this->wpdb->prefix}umh_bookings b
            LEFT JOIN {$this->wpdb->prefix}umh_departures d ON b.departure_id = d.id
            LEFT JOIN {$this->wpdb->prefix}umh_packages p ON d.package_id = p.id
            ORDER BY b.created_at DESC
        ");
        ?>
        <div class="wrap">
            <h1>Bookings</h1>
            <div class="umh-content" style="margin-top: 20px; background: #fff; padding: 20px; border: 1px solid #ccd0d4;">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Kode Booking</th>
                            <th>Paket</th>
                            <th>Total Bayar</th>
                            <th>Status</th>
                            <th>Tanggal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($bookings)): ?>
                            <tr><td colspan="5">Belum ada booking.</td></tr>
                        <?php else: ?>
                            <?php foreach ($bookings as $booking): ?>
                            <tr>
                                <td><strong><?php echo $booking->booking_code; ?></strong></td>
                                <td><?php echo $booking->package_name ?: 'N/A'; ?></td>
                                <td>Rp <?php echo number_format($booking->total_amount, 0, ',', '.'); ?></td>
                                <td><span class="status-<?php echo $booking->status; ?>" style="padding: 3px 8px; border-radius: 3px; background: #eee;"><?php echo ucfirst($booking->status); ?></span></td>
                                <td><?php echo date('d M Y H:i', strtotime($booking->created_at)); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }

    public function display_finance() {
        $transactions = UMH_Helper::get_all('finance');
        ?>
        <div class="wrap">
            <h1>Finance & Transactions</h1>
            <div class="umh-content" style="margin-top: 20px; background: #fff; padding: 20px; border: 1px solid #ccd0d4;">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Tipe</th>
                            <th>Jumlah</th>
                            <th>Metode</th>
                            <th>Tanggal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($transactions)): ?>
                            <tr><td colspan="5">Belum ada transaksi.</td></tr>
                        <?php else: ?>
                            <?php foreach ($transactions as $tx): ?>
                            <tr>
                                <td><?php echo $tx->id; ?></td>
                                <td><span style="color: <?php echo $tx->type == 'income' ? 'green' : 'red'; ?>;"><?php echo strtoupper($tx->type); ?></span></td>
                                <td>Rp <?php echo number_format($tx->amount, 0, ',', '.'); ?></td>
                                <td><?php echo $tx->payment_method; ?></td>
                                <td><?php echo date('d M Y H:i', strtotime($tx->transaction_date)); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }
}
