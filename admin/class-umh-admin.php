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
        add_submenu_page('umh-dashboard', 'CRM & Leads', 'CRM & Leads', 'manage_options', 'umh-crm', [$this, 'display_crm']);
        add_submenu_page('umh-dashboard', 'Savings', 'Savings', 'manage_options', 'umh-savings', [$this, 'display_savings']);
        add_submenu_page('umh-dashboard', 'Operational', 'Operational', 'manage_options', 'umh-operational', [$this, 'display_operational']);
        add_submenu_page('umh-dashboard', 'Agents & HR', 'Agents & HR', 'manage_options', 'umh-agents-hr', [$this, 'display_agents_hr']);
        add_submenu_page('umh-dashboard', 'Special Services', 'Special Services', 'manage_options', 'umh-special-services', [$this, 'display_special_services']);
        add_submenu_page('umh-dashboard', 'Customer Care', 'Customer Care', 'manage_options', 'umh-customer-care', [$this, 'display_customer_care']);
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
        $total_revenue = $this->wpdb->get_var("SELECT SUM(amount) FROM {$this->wpdb->prefix}umh_finance WHERE transaction_type = 'income' AND status = 'verified'");
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
        
        $table_name = (in_array($tab, ['branches', 'coupons', 'booking_addons'])) ? "{$this->wpdb->prefix}umh_{$tab}" : "{$this->wpdb->prefix}umh_master_{$tab}";
        
        $data = ['name' => $name];
        if ($tab == 'coupons') {
            $data = [
                'code' => sanitize_text_field($_POST['code']),
                'value' => floatval($_POST['value']),
                'type' => sanitize_text_field($_POST['type'])
            ];
        } elseif ($tab == 'booking_addons') {
            $data = [
                'name' => $name,
                'price' => floatval($_POST['price'])
            ];
        }
        
        $this->wpdb->insert($table_name, $data);
        
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
        $table_name = (in_array($tab, ['branches', 'coupons', 'booking_addons'])) ? $tab : 'master_' . $tab;
        $items = UMH_Helper::get_all($table_name);
        ?>
        <div class="wrap">
            <h1>Master Data: <?php echo ucfirst(str_replace('_', ' ', $tab)); ?></h1>
            <h2 class="nav-tab-wrapper">
                <a href="?page=umh-master&tab=hotels" class="nav-tab <?php echo $tab == 'hotels' ? 'nav-tab-active' : ''; ?>">Hotels</a>
                <a href="?page=umh-master&tab=airlines" class="nav-tab <?php echo $tab == 'airlines' ? 'nav-tab-active' : ''; ?>">Airlines</a>
                <a href="?page=umh-master&tab=locations" class="nav-tab <?php echo $tab == 'locations' ? 'nav-tab-active' : ''; ?>">Locations</a>
                <a href="?page=umh-master&tab=mutawwifs" class="nav-tab <?php echo $tab == 'mutawwifs' ? 'nav-tab-active' : ''; ?>">Mutawwifs</a>
                <a href="?page=umh-master&tab=branches" class="nav-tab <?php echo $tab == 'branches' ? 'nav-tab-active' : ''; ?>">Branches</a>
                <a href="?page=umh-master&tab=coupons" class="nav-tab <?php echo $tab == 'coupons' ? 'nav-tab-active' : ''; ?>">Coupons</a>
                <a href="?page=umh-master&tab=booking_addons" class="nav-tab <?php echo $tab == 'booking_addons' ? 'nav-tab-active' : ''; ?>">Add-ons</a>
            </h2>

            <div style="display: flex; gap: 20px; margin-top: 20px;">
                <div style="flex: 1; background: #fff; padding: 20px; border: 1px solid #ccd0d4;">
                    <h3>Tambah <?php echo ucfirst(substr($tab, 0, -1)); ?> Baru</h3>
                    <form action="<?php echo admin_url('admin-post.php'); ?>" method="post">
                        <input type="hidden" name="action" value="umh_save_master">
                        <input type="hidden" name="tab" value="<?php echo $tab; ?>">
                        <?php wp_nonce_field('umh_master_nonce'); ?>
                        <table class="form-table">
                            <?php if ($tab == 'coupons'): ?>
                                <tr>
                                    <th>Kode Kupon</th>
                                    <td><input type="text" name="code" class="regular-text" required></td>
                                </tr>
                                <tr>
                                    <th>Tipe</th>
                                    <td>
                                        <select name="type">
                                            <option value="nominal">Nominal</option>
                                            <option value="percent">Persen</option>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Nilai</th>
                                    <td><input type="number" name="value" class="regular-text" required></td>
                                </tr>
                            <?php elseif ($tab == 'booking_addons'): ?>
                                <tr>
                                    <th>Nama Layanan</th>
                                    <td><input type="text" name="name" class="regular-text" required></td>
                                </tr>
                                <tr>
                                    <th>Harga</th>
                                    <td><input type="number" name="price" class="regular-text" required></td>
                                </tr>
                            <?php else: ?>
                                <tr>
                                    <th>Nama</th>
                                    <td><input type="text" name="name" class="regular-text" required></td>
                                </tr>
                            <?php endif; ?>
                        </table>
                        <p class="submit"><input type="submit" class="button button-primary" value="Tambah"></p>
                    </form>
                </div>

                <div style="flex: 2; background: #fff; padding: 20px; border: 1px solid #ccd0d4;">
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <?php if ($tab == 'coupons'): ?>
                                    <th>Kode</th>
                                    <th>Nilai</th>
                                <?php elseif ($tab == 'booking_addons'): ?>
                                    <th>Nama</th>
                                    <th>Harga</th>
                                <?php else: ?>
                                    <th>Nama</th>
                                <?php endif; ?>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($items)): ?>
                                <tr><td colspan="<?php echo ($tab == 'coupons' || $tab == 'booking_addons') ? 4 : 3; ?>">Belum ada data.</td></tr>
                            <?php else: ?>
                                <?php foreach ($items as $item): ?>
                                <tr>
                                    <td><?php echo $item->id; ?></td>
                                    <?php if ($tab == 'coupons'): ?>
                                        <td><?php echo $item->code; ?></td>
                                        <td><?php echo $item->type == 'percent' ? $item->value . '%' : 'Rp ' . number_format($item->value, 0, ',', '.'); ?></td>
                                    <?php elseif ($tab == 'booking_addons'): ?>
                                        <td><?php echo $item->name; ?></td>
                                        <td>Rp <?php echo number_format($item->price, 0, ',', '.'); ?></td>
                                    <?php else: ?>
                                        <td><?php echo $item->name; ?></td>
                                    <?php endif; ?>
                                    <td><a href="<?php echo wp_nonce_url("?page=umh-master&tab=$tab&action=delete&table=$table_name&id=$item->id", 'umh_nonce'); ?>" style="color:red;" onclick="return confirm('Yakin ingin menghapus?')">Hapus</a></td>
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
        $hotels = UMH_Helper::get_all('master_hotels');
        $airlines = UMH_Helper::get_all('master_airlines');
        ?>
        <div class="wrap">
            <h1>Package Factory</h1>
            <div class="umh-content" style="margin-top: 20px; background: #fff; padding: 20px; border: 1px solid #ccd0d4;">
                <h3>Buat Paket Baru</h3>
                <form id="umh-package-form">
                    <div style="display: flex; gap: 30px;">
                        <div style="flex: 1;">
                            <h4>Informasi Dasar</h4>
                            <table class="form-table">
                                <tr><th>Nama Paket</th><td><input type="text" name="name" class="regular-text" required></td></tr>
                                <tr><th>Durasi (Hari)</th><td><input type="number" name="duration_days" value="9"></td></tr>
                                <tr>
                                    <th>Maskapai</th>
                                    <td>
                                        <select name="airline_id">
                                            <option value="">Pilih Maskapai</option>
                                            <?php foreach ($airlines as $airline): ?>
                                                <option value="<?php echo $airline->id; ?>"><?php echo $airline->name; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Hotel Makkah</th>
                                    <td>
                                        <select name="hotel_makkah_id">
                                            <option value="">Pilih Hotel</option>
                                            <?php foreach ($hotels as $hotel): ?>
                                                <option value="<?php echo $hotel->id; ?>"><?php echo $hotel->name; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Hotel Madinah</th>
                                    <td>
                                        <select name="hotel_madinah_id">
                                            <option value="">Pilih Hotel</option>
                                            <?php foreach ($hotels as $hotel): ?>
                                                <option value="<?php echo $hotel->id; ?>"><?php echo $hotel->name; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div style="flex: 1;">
                            <h4>Harga Dasar (IDR)</h4>
                            <table class="form-table">
                                <tr><th>Quad</th><td><input type="number" name="base_price_quad" value="0"></td></tr>
                                <tr><th>Triple</th><td><input type="number" name="base_price_triple" value="0"></td></tr>
                                <tr><th>Double</th><td><input type="number" name="base_price_double" value="0"></td></tr>
                            </table>
                        </div>
                    </div>

                    <div style="display: flex; gap: 30px; margin-top: 20px;">
                        <div style="flex: 1;">
                            <h4>Itinerary</h4>
                            <div id="itinerary-container">
                                <div class="itinerary-day" style="border: 1px solid #eee; padding: 10px; margin-bottom: 10px;">
                                    <strong>Hari 1</strong>: <input type="text" name="itineraries[1][title]" placeholder="Judul Kegiatan">
                                    <textarea name="itineraries[1][description]" style="width:100%;" placeholder="Deskripsi..."></textarea>
                                </div>
                            </div>
                            <button type="button" class="button" onclick="addDay()">Tambah Hari</button>
                        </div>
                        <div style="flex: 1;">
                            <h4>Fasilitas</h4>
                            <div style="display: flex; gap: 20px;">
                                <div style="flex: 1;">
                                    <h5>Termasuk (Include)</h5>
                                    <div id="include-container">
                                        <input type="text" name="facilities[include][]" placeholder="Contoh: Tiket Pesawat" style="width:100%; margin-bottom:5px;">
                                    </div>
                                    <button type="button" class="button" onclick="addFacility('include')">Tambah Include</button>
                                </div>
                                <div style="flex: 1;">
                                    <h5>Tidak Termasuk (Exclude)</h5>
                                    <div id="exclude-container">
                                        <input type="text" name="facilities[exclude][]" placeholder="Contoh: Paspor" style="width:100%; margin-bottom:5px;">
                                    </div>
                                    <button type="button" class="button" onclick="addFacility('exclude')">Tambah Exclude</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
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

            function addFacility(type) {
                const container = document.getElementById(type + '-container');
                const input = document.createElement('input');
                input.type = 'text';
                input.name = `facilities[${type}][]`;
                input.style = 'width:100%; margin-bottom:5px;';
                container.appendChild(input);
            }
        </script>
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
                            <th>Total Harga</th>
                            <th>Total Bayar</th>
                            <th>Status</th>
                            <th>Tanggal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($bookings)): ?>
                            <tr><td colspan="6">Belum ada booking.</td></tr>
                        <?php else: ?>
                            <?php foreach ($bookings as $booking): ?>
                            <tr>
                                <td><strong><?php echo $booking->booking_code; ?></strong></td>
                                <td><?php echo $booking->package_name ?: 'N/A'; ?></td>
                                <td>Rp <?php echo number_format($booking->total_price, 0, ',', '.'); ?></td>
                                <td>Rp <?php echo number_format($booking->total_paid, 0, ',', '.'); ?></td>
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
        $finance = UMH_Helper::get_all('finance');
        ?>
        <div class="wrap">
            <h1>Finance</h1>
            <div class="umh-content" style="margin-top: 20px; background: #fff; padding: 20px; border: 1px solid #ccd0d4;">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Tipe</th>
                            <th>Kategori</th>
                            <th>Jumlah</th>
                            <th>Status</th>
                            <th>Tanggal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($finance)): ?>
                            <tr><td colspan="6">Belum ada transaksi.</td></tr>
                        <?php else: ?>
                            <?php foreach ($finance as $item): ?>
                            <tr>
                                <td><?php echo $item->id; ?></td>
                                <td><?php echo ucfirst($item->transaction_type); ?></td>
                                <td><?php echo str_replace('_', ' ', ucfirst($item->category)); ?></td>
                                <td>Rp <?php echo number_format($item->amount, 0, ',', '.'); ?></td>
                                <td><span class="status-<?php echo $item->status; ?>"><?php echo ucfirst($item->status); ?></span></td>
                                <td><?php echo $item->transaction_date; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }

    public function display_crm() {
        ?>
        <div class="wrap">
            <h1>CRM & Lead Management</h1>
            <p>Fitur ini sedang dalam pengembangan.</p>
        </div>
        <?php
    }

    public function display_savings() {
        ?>
        <div class="wrap">
            <h1>Savings Management</h1>
            <p>Fitur ini sedang dalam pengembangan.</p>
        </div>
        <?php
    }

    public function display_operational() {
        ?>
        <div class="wrap">
            <h1>Operational Management</h1>
            <p>Fitur ini sedang dalam pengembangan.</p>
        </div>
        <?php
    }

    public function display_agents_hr() {
        ?>
        <div class="wrap">
            <h1>Agents & HR Management</h1>
            <p>Fitur ini sedang dalam pengembangan.</p>
        </div>
        <?php
    }

    public function display_special_services() {
        ?>
        <div class="wrap">
            <h1>Special Services</h1>
            <p>Fitur ini sedang dalam pengembangan.</p>
        </div>
        <?php
    }

    public function display_customer_care() {
        ?>
        <div class="wrap">
            <h1>Customer Care</h1>
            <p>Fitur ini sedang dalam pengembangan.</p>
        </div>
        <?php
    }