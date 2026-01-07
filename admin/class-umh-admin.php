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
        add_action('wp_ajax_umh_save_booking', [$this, 'ajax_save_booking']);
        add_action('admin_post_umh_save_master', [$this, 'handle_save_master']);
        add_action('admin_post_umh_save_departure', [$this, 'handle_save_departure']);
        add_action('admin_post_umh_save_payment', [$this, 'handle_save_payment']);
    }

    public function add_plugin_admin_menu() {
        add_menu_page('Umroh Management', 'Umroh Mgmt', 'manage_options', 'umh-dashboard', [$this, 'display_dashboard'], 'dashicons-airplane', 6);
        add_submenu_page('umh-dashboard', 'Master Data', 'Master Data', 'manage_options', 'umh-master', [$this, 'display_master_data']);
        add_submenu_page('umh-dashboard', 'Packages', 'Packages', 'manage_options', 'umh-packages', [$this, 'display_packages']);
        add_submenu_page('umh-dashboard', 'Departures', 'Departures', 'manage_options', 'umh-departures', [$this, 'display_departures']);
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

        if (isset($_GET['action']) && $_GET['action'] == 'verify' && check_admin_referer('umh_nonce')) {
            $id = intval($_GET['id']);
            $finance_logic = new UMH_Finance();
            $finance_logic->verify_payment($id);
            wp_redirect(remove_query_arg(['action', 'id', '_wpnonce']));
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

    public function handle_save_payment() {
        if (!current_user_can('manage_options')) return;
        check_admin_referer('umh_payment_nonce');

        $finance_logic = new UMH_Finance();
        $finance_logic->record_payment($_POST);

        wp_redirect(admin_url("admin.php?page=umh-finance"));
        exit;
    }

    public function handle_save_departure() {
        if (!current_user_can('manage_options')) return;
        check_admin_referer('umh_departure_nonce');

        $package_id = intval($_POST['package_id']);
        $departure_date = sanitize_text_field($_POST['departure_date']);
        $return_date = sanitize_text_field($_POST['return_date']);
        $quota = intval($_POST['quota']);
        $price_quad = floatval($_POST['price_quad']);

        $this->wpdb->insert("{$this->wpdb->prefix}umh_departures", [
            'package_id' => $package_id,
            'departure_date' => $departure_date,
            'return_date' => $return_date,
            'quota' => $quota,
            'available_seats' => $quota,
            'price_quad' => $price_quad,
            'status' => 'open'
        ]);

        wp_redirect(admin_url("admin.php?page=umh-departures"));
        exit;
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

    public function ajax_save_booking() {
        check_ajax_referer('umh_booking_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');

        $booking_logic = new UMH_Booking();
        $booking_id = $booking_logic->create_booking($_POST);

        if ($booking_id) {
            $booking = $this->wpdb->get_row($this->wpdb->prepare("SELECT booking_code FROM {$this->wpdb->prefix}umh_bookings WHERE id = %d", $booking_id));
            wp_send_json_success(['booking_id' => $booking_id, 'booking_code' => $booking->booking_code]);
        } else {
            wp_send_json_error('Gagal membuat booking.');
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

    public function display_departures() {
        $departures = $this->wpdb->get_results("
            SELECT d.*, p.name as package_name 
            FROM {$this->wpdb->prefix}umh_departures d
            LEFT JOIN {$this->wpdb->prefix}umh_packages p ON d.package_id = p.id
            ORDER BY d.departure_date ASC
        ");
        $packages = UMH_Helper::get_all('packages');
        ?>
        <div class="wrap">
            <h1>Departure Management</h1>
            <div style="display: flex; gap: 20px; margin-top: 20px;">
                <div style="flex: 1; background: #fff; padding: 20px; border: 1px solid #ccd0d4;">
                    <h3>Tambah Keberangkatan Baru</h3>
                    <form action="<?php echo admin_url('admin-post.php'); ?>" method="post">
                        <input type="hidden" name="action" value="umh_save_departure">
                        <?php wp_nonce_field('umh_departure_nonce'); ?>
                        <table class="form-table">
                            <tr>
                                <th>Paket</th>
                                <td>
                                    <select name="package_id" required>
                                        <option value="">Pilih Paket</option>
                                        <?php foreach ($packages as $pkg): ?>
                                            <option value="<?php echo $pkg->id; ?>"><?php echo $pkg->name; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th>Tanggal Berangkat</th>
                                <td><input type="date" name="departure_date" required></td>
                            </tr>
                            <tr>
                                <th>Tanggal Pulang</th>
                                <td><input type="date" name="return_date" required></td>
                            </tr>
                            <tr>
                                <th>Kuota (Seat)</th>
                                <td><input type="number" name="quota" value="45" required></td>
                            </tr>
                            <tr>
                                <th>Harga Quad</th>
                                <td><input type="number" name="price_quad" value="0"></td>
                            </tr>
                        </table>
                        <p class="submit"><input type="submit" class="button button-primary" value="Tambah Keberangkatan"></p>
                    </form>
                </div>
                <div style="flex: 2; background: #fff; padding: 20px; border: 1px solid #ccd0d4;">
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Paket</th>
                                <th>Tanggal</th>
                                <th>Kuota</th>
                                <th>Tersedia</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($departures)): ?>
                                <tr><td colspan="6">Belum ada jadwal keberangkatan.</td></tr>
                            <?php else: ?>
                                <?php foreach ($departures as $dep): ?>
                                    <tr>
                                        <td><?php echo $dep->package_name; ?></td>
                                        <td><?php echo date('d M Y', strtotime($dep->departure_date)); ?></td>
                                        <td><?php echo $dep->quota; ?></td>
                                        <td><?php echo $dep->available_seats; ?></td>
                                        <td><span class="status-<?php echo $dep->status; ?>"><?php echo ucfirst($dep->status); ?></span></td>
                                        <td>
                                            <a href="<?php echo wp_nonce_url("?page=umh-departures&action=delete&table=departures&id=$dep->id", 'umh_nonce'); ?>" style="color:red;" onclick="return confirm('Yakin ingin menghapus?')">Hapus</a>
                                        </td>
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

    public function display_bookings() {
        $bookings = $this->wpdb->get_results("
            SELECT b.*, p.name as package_name 
            FROM {$this->wpdb->prefix}umh_bookings b
            LEFT JOIN {$this->wpdb->prefix}umh_departures d ON b.departure_id = d.id
            LEFT JOIN {$this->wpdb->prefix}umh_packages p ON d.package_id = p.id
            ORDER BY b.created_at DESC
        ");
        $departures = $this->wpdb->get_results("
            SELECT d.*, p.name as package_name 
            FROM {$this->wpdb->prefix}umh_departures d
            JOIN {$this->wpdb->prefix}umh_packages p ON d.package_id = p.id
            WHERE d.status = 'open' AND d.available_seats > 0
        ");
        ?>
        <div class="wrap">
            <h1>Bookings</h1>
            
            <div style="margin-top: 20px; background: #fff; padding: 20px; border: 1px solid #ccd0d4;">
                <h3>Buat Booking Baru</h3>
                <form id="umh-booking-form">
                    <table class="form-table">
                        <tr>
                            <th>Pilih Keberangkatan</th>
                            <td>
                                <select name="departure_id" id="departure_id" required>
                                    <option value="">-- Pilih Jadwal --</option>
                                    <?php foreach ($departures as $dep): ?>
                                        <option value="<?php echo $dep->id; ?>" data-price="<?php echo $dep->price_quad; ?>">
                                            <?php echo $dep->package_name; ?> (<?php echo date('d M Y', strtotime($dep->departure_date)); ?>) - Sisa: <?php echo $dep->available_seats; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th>Nama Kontak</th>
                            <td><input type="text" name="contact_name" required></td>
                        </tr>
                        <tr>
                            <th>WhatsApp / HP</th>
                            <td><input type="text" name="contact_phone" required></td>
                        </tr>
                    </table>

                    <h4>Data Jamaah</h4>
                    <div id="jamaah-container">
                        <div class="jamaah-row" style="border: 1px solid #eee; padding: 15px; margin-bottom: 10px;">
                            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px;">
                                <input type="text" name="passengers[0][nik]" placeholder="NIK" required>
                                <input type="text" name="passengers[0][full_name]" placeholder="Nama Lengkap" required>
                                <select name="passengers[0][gender]">
                                    <option value="L">Laki-laki</option>
                                    <option value="P">Perempuan</option>
                                </select>
                                <input type="hidden" name="passengers[0][room_type]" value="Quad">
                                <input type="hidden" name="passengers[0][price]" class="pax-price" value="0">
                            </div>
                        </div>
                    </div>
                    <button type="button" class="button" onclick="addJamaah()">+ Tambah Jamaah</button>
                    <p class="submit"><input type="submit" class="button button-primary" value="Simpan Booking"></p>
                </form>
            </div>

            <div style="margin-top: 20px; background: #fff; padding: 20px; border: 1px solid #ccd0d4;">
                <h3>Daftar Booking</h3>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Kode Booking</th>
                            <th>Paket</th>
                            <th>Kontak</th>
                            <th>Pax</th>
                            <th>Total Harga</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($bookings)): ?>
                            <tr><td colspan="7">Belum ada booking.</td></tr>
                        <?php else: ?>
                            <?php foreach ($bookings as $booking): ?>
                                <tr>
                                    <td><strong><?php echo $booking->booking_code; ?></strong></td>
                                    <td><?php echo $booking->package_name; ?></td>
                                    <td><?php echo $booking->contact_name; ?> (<?php echo $booking->contact_phone; ?>)</td>
                                    <td><?php echo $booking->total_pax; ?></td>
                                    <td>Rp <?php echo number_format($booking->total_price, 0, ',', '.'); ?></td>
                                    <td><span class="status-<?php echo $booking->status; ?>"><?php echo ucfirst($booking->status); ?></span></td>
                                    <td>
                                        <a href="<?php echo wp_nonce_url("?page=umh-bookings&action=delete&table=bookings&id=$booking->id", 'umh_nonce'); ?>" style="color:red;" onclick="return confirm('Yakin ingin menghapus?')">Hapus</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <script>
            let jamaahCount = 1;
            function addJamaah() {
                const container = document.getElementById('jamaah-container');
                const div = document.createElement('div');
                div.className = 'jamaah-row';
                div.style = 'border: 1px solid #eee; padding: 15px; margin-bottom: 10px;';
                const price = document.querySelector('#departure_id option:checked')?.dataset.price || 0;
                div.innerHTML = `
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px;">
                        <input type="text" name="passengers[${jamaahCount}][nik]" placeholder="NIK" required>
                        <input type="text" name="passengers[${jamaahCount}][full_name]" placeholder="Nama Lengkap" required>
                        <select name="passengers[${jamaahCount}][gender]">
                            <option value="L">Laki-laki</option>
                            <option value="P">Perempuan</option>
                        </select>
                        <input type="hidden" name="passengers[${jamaahCount}][room_type]" value="Quad">
                        <input type="hidden" name="passengers[${jamaahCount}][price]" class="pax-price" value="${price}">
                    </div>
                `;
                container.appendChild(div);
                jamaahCount++;
            }

            document.getElementById('umh-booking-form').addEventListener('submit', function(e) {
                e.preventDefault();
                const departure = document.getElementById('departure_id');
                const price = departure.options[departure.selectedIndex].dataset.price;
                document.querySelectorAll('.pax-price').forEach(el => el.value = price);

                const formData = new FormData(this);
                formData.append('action', 'umh_save_booking');
                formData.append('nonce', '<?php echo wp_create_nonce("umh_booking_nonce"); ?>');

                fetch(ajaxurl, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Booking Berhasil! Kode: ' + data.data.booking_code);
                        location.reload();
                    } else {
                        alert('Error: ' + data.data);
                    }
                });
            });
        </script>
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

       public function display_finance() {
        $transactions = $this->wpdb->get_results("
            SELECT f.*, b.booking_code 
            FROM {$this->wpdb->prefix}umh_finance f
            LEFT JOIN {$this->wpdb->prefix}umh_bookings b ON f.booking_id = b.id
            ORDER BY f.transaction_date DESC
        ");
        $bookings = $this->wpdb->get_results("SELECT id, booking_code FROM {$this->wpdb->prefix}umh_bookings WHERE status != 'cancelled'");
        ?>
        <div class="wrap">
            <h1>Finance Management</h1>
            
            <div style="display: flex; gap: 20px; margin-top: 20px;">
                <div style="flex: 1; background: #fff; padding: 20px; border: 1px solid #ccd0d4;">
                    <h3>Catat Pembayaran</h3>
                    <form action="<?php echo admin_url('admin-post.php'); ?>" method="post">
                        <input type="hidden" name="action" value="umh_save_payment">
                        <?php wp_nonce_field('umh_payment_nonce'); ?>
                        <table class="form-table">
                            <tr>
                                <th>Booking</th>
                                <td>
                                    <select name="booking_id" required>
                                        <option value="">-- Pilih Booking --</option>
                                        <?php foreach ($bookings as $b): ?>
                                            <option value="<?php echo $b->id; ?>"><?php echo $b->booking_code; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th>Jumlah (IDR)</th>
                                <td><input type="number" name="amount" required></td>
                            </tr>
                            <tr>
                                <th>Metode</th>
                                <td>
                                    <select name="payment_method">
                                        <option value="Transfer Bank">Transfer Bank</option>
                                        <option value="Tunai">Tunai</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th>Status</th>
                                <td>
                                    <select name="status">
                                        <option value="pending">Pending (Perlu Verifikasi)</option>
                                        <option value="verified">Verified (Langsung Lunas)</option>
                                    </select>
                                </td>
                            </tr>
                        </table>
                        <p class="submit"><input type="submit" class="button button-primary" value="Simpan Pembayaran"></p>
                    </form>
                </div>

                <div style="flex: 2; background: #fff; padding: 20px; border: 1px solid #ccd0d4;">
                    <h3>Riwayat Transaksi</h3>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Booking</th>
                                <th>Jumlah</th>
                                <th>Metode</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($transactions)): ?>
                                <tr><td colspan="6">Belum ada transaksi.</td></tr>
                            <?php else: ?>
                                <?php foreach ($transactions as $trx): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y H:i', strtotime($trx->transaction_date)); ?></td>
                                        <td><?php echo $trx->booking_code; ?></td>
                                        <td>Rp <?php echo number_format($trx->amount, 0, ',', '.'); ?></td>
                                        <td><?php echo $trx->payment_method; ?></td>
                                        <td><span class="status-<?php echo $trx->status; ?>"><?php echo ucfirst($trx->status); ?></span></td>
                                        <td>
                                            <?php if ($trx->status == 'pending'): ?>
                                                <a href="<?php echo wp_nonce_url("?page=umh-finance&action=verify&id=$trx->id", 'umh_nonce'); ?>" class="button button-small">Verifikasi</a>
                                            <?php endif; ?>
                                        </td>
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
}
