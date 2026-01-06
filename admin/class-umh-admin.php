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
    }

    public function add_plugin_admin_menu() {
        add_menu_page(
            'Umroh Management',
            'Umroh Mgmt',
            'manage_options',
            'umh-dashboard',
            [$this, 'display_dashboard'],
            'dashicons-airplane',
            6
        );

        add_submenu_page('umh-dashboard', 'Master Data', 'Master Data', 'manage_options', 'umh-master', [$this, 'display_master_data']);
        add_submenu_page('umh-dashboard', 'Packages', 'Packages', 'manage_options', 'umh-packages', [$this, 'display_packages']);
        add_submenu_page('umh-dashboard', 'CRM & Leads', 'CRM & Leads', 'manage_options', 'umh-crm', [$this, 'display_crm']);
        add_submenu_page('umh-dashboard', 'Bookings', 'Bookings', 'manage_options', 'umh-bookings', [$this, 'display_bookings']);
        add_submenu_page('umh-dashboard', 'Finance', 'Finance', 'manage_options', 'umh-finance', [$this, 'display_finance']);
        add_submenu_page('umh-dashboard', 'Operasional', 'Operasional', 'manage_options', 'umh-ops', [$this, 'display_ops']);
        add_submenu_page('umh-dashboard', 'Agents & HR', 'Agents & HR', 'manage_options', 'umh-hr', [$this, 'display_hr']);
        add_submenu_page('umh-dashboard', 'Settings', 'Settings', 'manage_options', 'umh-settings', [$this, 'display_settings']);
    }

    public function display_dashboard() {
        ?>
        <div class="wrap">
            <h1>Umroh Management Dashboard</h1>
            <div class="welcome-panel">
                <div class="welcome-panel-content">
                    <h2>Selamat Datang di Sistem Manajemen Umroh Enterprise</h2>
                    <p class="about-description">Kelola seluruh aspek bisnis travel umroh Anda dalam satu platform terintegrasi.</p>
                </div>
            </div>
            <div class="umh-stats-grid" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-top: 20px;">
                <div class="card" style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px;">
                    <h3>Total Jamaah</h3>
                    <p style="font-size: 24px; font-weight: bold;">0</p>
                </div>
                <div class="card" style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px;">
                    <h3>Booking Aktif</h3>
                    <p style="font-size: 24px; font-weight: bold;">0</p>
                </div>
                <div class="card" style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px;">
                    <h3>Leads Baru</h3>
                    <p style="font-size: 24px; font-weight: bold;">0</p>
                </div>
                <div class="card" style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px;">
                    <h3>Revenue Bulan Ini</h3>
                    <p style="font-size: 24px; font-weight: bold;">Rp 0</p>
                </div>
            </div>
        </div>
        <?php
    }

    public function display_master_data() {
        $tab = isset($_GET['tab']) ? $_GET['tab'] : 'hotels';
        ?>
        <div class="wrap">
            <h1>Master Data Management</h1>
            <h2 class="nav-tab-wrapper">
                <a href="?page=umh-master&tab=hotels" class="nav-tab <?php echo $tab == 'hotels' ? 'nav-tab-active' : ''; ?>">Hotels</a>
                <a href="?page=umh-master&tab=airlines" class="nav-tab <?php echo $tab == 'airlines' ? 'nav-tab-active' : ''; ?>">Airlines</a>
                <a href="?page=umh-master&tab=locations" class="nav-tab <?php echo $tab == 'locations' ? 'nav-tab-active' : ''; ?>">Locations</a>
                <a href="?page=umh-master&tab=mutawwifs" class="nav-tab <?php echo $tab == 'mutawwifs' ? 'nav-tab-active' : ''; ?>">Mutawwifs</a>
            </h2>
            <div class="tab-content" style="margin-top: 20px;">
                <?php $this->render_master_tab($tab); ?>
            </div>
        </div>
        <?php
    }

    private function render_master_tab($tab) {
        switch ($tab) {
            case 'hotels':
                echo '<h3>Daftar Hotel</h3><button class="button button-primary">Tambah Hotel</button>';
                break;
            case 'airlines':
                echo '<h3>Daftar Maskapai</h3><button class="button button-primary">Tambah Maskapai</button>';
                break;
            case 'locations':
                echo '<h3>Daftar Lokasi</h3><button class="button button-primary">Tambah Lokasi</button>';
                break;
            case 'mutawwifs':
                echo '<h3>Daftar Mutawwif</h3><button class="button button-primary">Tambah Mutawwif</button>';
                break;
        }
    }

    public function display_packages() {
        echo '<div class="wrap"><h1>Package Factory</h1><p>Rakit paket umroh dengan itinerary dinamis.</p></div>';
    }

    public function display_crm() {
        echo '<div class="wrap"><h1>CRM & Lead Management</h1><p>Pantau prospek melalui Kanban Board.</p></div>';
    }

    public function display_bookings() {
        echo '<div class="wrap"><h1>Booking Engine</h1><p>Kelola pendaftaran jamaah dan manifest.</p></div>';
    }

    public function display_finance() {
        echo '<div class="wrap"><h1>Finance & Invoicing</h1><p>Kelola arus kas dan pembayaran.</p></div>';
    }

    public function display_ops() {
        echo '<div class="wrap"><h1>Operasional Keberangkatan</h1><p>Rooming list, Visa, dan Logistik.</p></div>';
    }

    public function display_hr() {
        echo '<div class="wrap"><h1>Agents & HR Management</h1><p>Kelola agen mitra dan SDM internal.</p></div>';
    }

    public function display_settings() {
        echo '<div class="wrap"><h1>System Settings</h1></div>';
    }
}
