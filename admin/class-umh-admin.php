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
        $leads = $this->wpdb->get_results("SELECT * FROM {$this->wpdb->prefix}umh_leads ORDER BY created_at DESC");
        $statuses = ['new' => 'New', 'contacted' => 'Contacted', 'hot' => 'Hot', 'deal' => 'Deal', 'lost' => 'Lost'];
        
        // Document Tracking Data
        $docs = $this->wpdb->get_results("
            SELECT d.*, j.full_name 
            FROM {$this->wpdb->prefix}umh_doc_tracking d
            JOIN {$this->wpdb->prefix}umh_jamaah j ON d.jamaah_id = j.id
            ORDER BY d.updated_at DESC
        ");
        ?>
        <div class="wrap">
            <h1>CRM & Lead Management</h1>
            
            <h2 class="nav-tab-wrapper">
                <a href="#kanban" class="nav-tab nav-tab-active">Kanban Board</a>
                <a href="#docs" class="nav-tab">Document Tracking</a>
            </h2>

            <div id="kanban-section" class="tab-content">
                <div style="display: flex; gap: 15px; margin-top: 20px; overflow-x: auto; padding-bottom: 20px;">
                    <?php foreach ($statuses as $key => $label): ?>
                        <div style="flex: 1; min-width: 250px; background: #f0f0f1; border-radius: 5px; padding: 10px;">
                            <h3 style="border-bottom: 2px solid #ccc; padding-bottom: 5px;"><?php echo $label; ?></h3>
                            <?php foreach ($leads as $lead): 
                                if ($lead->status !== $key) continue; ?>
                                <div style="background: #fff; padding: 10px; margin-bottom: 10px; border-radius: 3px; border-left: 4px solid #2271b1; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                                    <strong><?php echo $lead->name; ?></strong><br>
                                    <small><?php echo $lead->phone; ?></small><br>
                                    <div style="margin-top: 5px;">
                                        <select onchange="updateLeadStatus(<?php echo $lead->id; ?>, this.value)" style="font-size: 11px;">
                                            <?php foreach ($statuses as $s_key => $s_label): ?>
                                                <option value="<?php echo $s_key; ?>" <?php selected($lead->status, $s_key); ?>><?php echo $s_label; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div id="docs-section" class="tab-content" style="display:none; margin-top: 20px;">
                <div style="background: #fff; padding: 20px; border: 1px solid #ccd0d4;">
                    <h3>Digital Archive & Document Status</h3>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Jamaah</th>
                                <th>Tipe Dokumen</th>
                                <th>Status Fisik</th>
                                <th>Update Terakhir</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($docs)): ?>
                                <tr><td colspan="5">Belum ada data dokumen.</td></tr>
                            <?php else: ?>
                                <?php foreach ($docs as $doc): ?>
                                    <tr>
                                        <td><?php echo $doc->full_name; ?></td>
                                        <td><?php echo strtoupper($doc->doc_type); ?></td>
                                        <td>
                                            <select onchange="updateDocStatus(<?php echo $doc->id; ?>, this.value)">
                                                <option value="jamaah" <?php selected($doc->status, 'jamaah'); ?>>Di Jamaah</option>
                                                <option value="office" <?php selected($doc->status, 'office'); ?>>Di Kantor</option>
                                                <option value="provider" <?php selected($doc->status, 'provider'); ?>>Di Provider</option>
                                                <option value="done" <?php selected($doc->status, 'done'); ?>>Selesai</option>
                                            </select>
                                        </td>
                                        <td><?php echo $doc->updated_at; ?></td>
                                        <td><button class="button button-small">Lihat Scan</button></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <script>
            function updateLeadStatus(id, status) {
                // AJAX implementation here
                alert('Status Lead ' + id + ' diubah ke ' + status);
            }
            function updateDocStatus(id, status) {
                // AJAX implementation here
                alert('Status Dokumen ' + id + ' diubah ke ' + status);
            }
            
            document.querySelectorAll('.nav-tab').forEach(tab => {
                tab.addEventListener('click', function(e) {
                    e.preventDefault();
                    document.querySelectorAll('.nav-tab').forEach(t => t.classList.remove('nav-tab-active'));
                    this.classList.add('nav-tab-active');
                    document.querySelectorAll('.tab-content').forEach(c => c.style.display = 'none');
                    document.querySelector(this.getAttribute('href') + '-section').style.display = 'block';
                });
            });
        </script>
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
        $departures = $this->wpdb->get_results("
            SELECT d.*, p.name as package_name 
            FROM {$this->wpdb->prefix}umh_departures d
            JOIN {$this->wpdb->prefix}umh_packages p ON d.package_id = p.id
            ORDER BY d.departure_date ASC
        ");
        
        $inventory = $this->wpdb->get_results("SELECT * FROM {$this->wpdb->prefix}umh_inventory_items");
        ?>
        <div class="wrap">
            <h1>Operational & Logistics</h1>
            
            <h2 class="nav-tab-wrapper">
                <a href="#rooming" class="nav-tab nav-tab-active">Rooming & Visa</a>
                <a href="#logistics" class="nav-tab">Logistik & Inventory</a>
                <a href="#manasik" class="nav-tab">Manasik & Absensi</a>
            </h2>

            <div id="rooming-section" class="tab-content" style="margin-top: 20px;">
                <div style="background: #fff; padding: 20px; border: 1px solid #ccd0d4;">
                    <h3>Rooming List & Visa Grouping</h3>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Keberangkatan</th>
                                <th>Paket</th>
                                <th>Total Jamaah</th>
                                <th>Status Visa</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($departures as $dep): 
                                $jamaah_count = $this->wpdb->get_var($this->wpdb->prepare("SELECT COUNT(*) FROM {$this->wpdb->prefix}umh_jamaah WHERE departure_id = %d", $dep->id));
                                ?>
                                <tr>
                                    <td><strong><?php echo date('d M Y', strtotime($dep->departure_date)); ?></strong></td>
                                    <td><?php echo $dep->package_name; ?></td>
                                    <td><?php echo $jamaah_count; ?> Jamaah</td>
                                    <td><span class="status-open">Ready to Group</span></td>
                                    <td>
                                        <button class="button button-small">Manage Rooming</button>
                                        <button class="button button-small">Visa Batch</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="logistics-section" class="tab-content" style="display:none; margin-top: 20px;">
                <div style="background: #fff; padding: 20px; border: 1px solid #ccd0d4;">
                    <h3>Inventory Gudang Perlengkapan</h3>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Nama Barang</th>
                                <th>Kategori</th>
                                <th>Stok</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($inventory)): ?>
                                <tr><td colspan="5">Belum ada data inventory.</td></tr>
                            <?php else: ?>
                                <?php foreach ($inventory as $item): ?>
                                    <tr>
                                        <td><?php echo $item->item_code; ?></td>
                                        <td><?php echo $item->item_name; ?></td>
                                        <td><?php echo ucfirst($item->category); ?></td>
                                        <td><?php echo $item->stock_qty; ?></td>
                                        <td>
                                            <?php if ($item->stock_qty <= $item->min_stock_alert): ?>
                                                <span style="color: red; font-weight: bold;">Low Stock!</span>
                                            <?php else: ?>
                                                <span style="color: green;">Safe</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="manasik-section" class="tab-content" style="display:none; margin-top: 20px;">
                <div style="background: #fff; padding: 20px; border: 1px solid #ccd0d4;">
                    <h3>Jadwal Manasik & Absensi QR</h3>
                    <p>Pilih keberangkatan untuk mengelola absensi manasik.</p>
                    <select style="width: 300px;">
                        <?php foreach ($departures as $dep): ?>
                            <option><?php echo date('d M Y', strtotime($dep->departure_date)); ?> - <?php echo $dep->package_name; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button class="button">Generate QR Absensi</button>
                </div>
            </div>
        </div>
        <script>
            document.querySelectorAll('.nav-tab').forEach(tab => {
                tab.addEventListener('click', function(e) {
                    e.preventDefault();
                    document.querySelectorAll('.nav-tab').forEach(t => t.classList.remove('nav-tab-active'));
                    this.classList.add('nav-tab-active');
                    document.querySelectorAll('.tab-content').forEach(c => c.style.display = 'none');
                    document.querySelector(this.getAttribute('href') + '-section').style.display = 'block';
                });
            });
        </script>
        <?php
    }

        public function display_savings() {
        $accounts = $this->wpdb->get_results("
            SELECT s.*, u.display_name as user_name, p.name as package_name 
            FROM {$this->wpdb->prefix}umh_savings_accounts s
            JOIN {$this->wpdb->prefix}users u ON s.user_id = u.ID
            LEFT JOIN {$this->wpdb->prefix}umh_packages p ON s.package_id = p.id
        ");
        ?>
        <div class="wrap">
            <h1>Sistem Tabungan Umroh</h1>
            <div style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; margin-top: 20px;">
                <h3>Daftar Rekening Tabungan (Virtual Account)</h3>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Jamaah</th>
                            <th>Paket Target</th>
                            <th>Target Dana</th>
                            <th>Saldo Saat Ini</th>
                            <th>Progress</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($accounts)): ?>
                            <tr><td colspan="7">Belum ada rekening tabungan.</td></tr>
                        <?php else: ?>
                            <?php foreach ($accounts as $acc): 
                                $progress = ($acc->target_amount > 0) ? ($acc->current_balance / $acc->target_amount) * 100 : 0;
                                ?>
                                <tr>
                                    <td><strong><?php echo $acc->user_name; ?></strong></td>
                                    <td><?php echo $acc->package_name ?: 'Belum ditentukan'; ?></td>
                                    <td>Rp <?php echo number_format($acc->target_amount, 0, ',', '.'); ?></td>
                                    <td>Rp <?php echo number_format($acc->current_balance, 0, ',', '.'); ?></td>
                                    <td>
                                        <div style="background: #eee; width: 100%; height: 10px; border-radius: 5px;">
                                            <div style="background: #4caf50; width: <?php echo min(100, $progress); ?>%; height: 10px; border-radius: 5px;"></div>
                                        </div>
                                        <small><?php echo round($progress, 1); ?>%</small>
                                    </td>
                                    <td><span class="status-<?php echo $acc->status; ?>"><?php echo ucfirst($acc->status); ?></span></td>
                                    <td>
                                        <button class="button button-small">Setor</button>
                                        <?php if ($progress >= 100): ?>
                                            <button class="button button-small button-primary">Konversi ke Booking</button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    } }

    public function display_special_services() {
        $refunds = $this->wpdb->get_results("
            SELECT r.*, b.booking_code, b.contact_name 
            FROM {$this->wpdb->prefix}umh_booking_requests r
            JOIN {$this->wpdb->prefix}umh_bookings b ON r.booking_id = b.id
            WHERE r.request_type = 'refund'
            ORDER BY r.created_at DESC
        ");
        ?>
        <div class="wrap">
            <h1>Refund & Cancellation Management</h1>
            <div style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; margin-top: 20px;">
                <h3>Workflow Pengajuan Refund</h3>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Booking</th>
                            <th>Jamaah/Kontak</th>
                            <th>Alasan</th>
                            <th>Jumlah Refund</th>
                            <th>Status</th>
                            <th>Tanggal</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($refunds)): ?>
                            <tr><td colspan="7">Belum ada pengajuan refund.</td></tr>
                        <?php else: ?>
                            <?php foreach ($refunds as $ref): ?>
                                <tr>
                                    <td><strong><?php echo $ref->booking_code; ?></strong></td>
                                    <td><?php echo $ref->contact_name; ?></td>
                                    <td><?php echo $ref->reason; ?></td>
                                    <td>Rp <?php echo number_format($ref->amount_requested, 0, ',', '.'); ?></td>
                                    <td><span class="status-<?php echo $ref->status; ?>"><?php echo ucfirst($ref->status); ?></span></td>
                                    <td><?php echo date('d/m/Y', strtotime($ref->created_at)); ?></td>
                                    <td>
                                        <?php if ($ref->status == 'pending'): ?>
                                            <button class="button button-small" style="background: #4caf50; color: white;">Setujui</button>
                                            <button class="button button-small" style="background: #f44336; color: white;">Tolak</button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
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
