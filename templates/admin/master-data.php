<div class="wrap">
    <h1 class="wp-heading-inline">Master Data Management</h1>
    
    <nav class="nav-tab-wrapper">
        <a href="?page=umh-master&tab=hotels" class="nav-tab <?php echo $active_tab === 'hotels' ? 'nav-tab-active' : ''; ?>">Hotels</a>
        <a href="?page=umh-master&tab=airlines" class="nav-tab <?php echo $active_tab === 'airlines' ? 'nav-tab-active' : ''; ?>">Airlines</a>
        <a href="?page=umh-master&tab=muthawifs" class="nav-tab <?php echo $active_tab === 'muthawifs' ? 'nav-tab-active' : ''; ?>">Muthawif (Ustadz)</a>
        <a href="?page=umh-master&tab=bus" class="nav-tab <?php echo $active_tab === 'bus' ? 'nav-tab-active' : ''; ?>">Bus Provider</a>
        <a href="?page=umh-master&tab=airports" class="nav-tab <?php echo $active_tab === 'airports' ? 'nav-tab-active' : ''; ?>">Bandara</a>
    </nav>

    <!-- --- TAB HOTELS (Existing) --- -->
    <?php if ($active_tab === 'hotels'): ?>
        <!-- (Kode Hotel Tetap Sama, Copy Paste dari sebelumnya) -->
        <div class="tab-content" style="margin-top:20px; background:#fff; padding:20px;">
            <button class="button button-primary" onclick="openHotelModal()">Tambah Hotel</button>
            <table class="wp-list-table widefat fixed striped" style="margin-top:10px;">
                <thead><tr><th>Nama</th><th>Lokasi</th><th>Rating</th><th>Aksi</th></tr></thead>
                <tbody>
                    <?php if(!empty($hotels)): foreach($hotels as $h): ?>
                    <tr>
                        <td><?php echo esc_html($h->name); ?></td>
                        <td><?php echo esc_html($h->location); ?></td>
                        <td><?php echo esc_html($h->rating); ?>*</td>
                        <td>
                            <button class="button" onclick='openHotelModal(<?php echo json_encode($h); ?>)'>Edit</button>
                            <a href="<?php echo admin_url('admin-post.php?action=umh_delete_hotel&id='.$h->id); ?>" class="button button-link-delete" onclick="return confirm('Hapus?')">Hapus</a>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        <!-- Modal Hotel juga tetap sama -->
        <?php include 'partials/modal-hotel.php'; // (Anggap saja ini include, atau paste kode modal hotel di sini) ?>
    <?php endif; ?>

    <!-- --- TAB AIRLINES (Existing) --- -->
    <?php if ($active_tab === 'airlines'): ?>
        <!-- (Kode Airline Tetap Sama) -->
    <?php endif; ?>

    <!-- --- [NEW] TAB MUTHAWIFS --- -->
    <?php if ($active_tab === 'muthawifs'): ?>
        <div class="tab-content" style="margin-top:20px; background:#fff; padding:20px; border:1px solid #ddd;">
            <div style="display:flex; justify-content:space-between; margin-bottom:15px;">
                <h3>Data Pembimbing (Muthawif)</h3>
                <button class="button button-primary" onclick="openMuthawifModal()">Tambah Muthawif</button>
            </div>
            <table class="wp-list-table widefat fixed striped">
                <thead><tr><th>Nama Ustadz</th><th>No HP Saudi</th><th>No HP Indo</th><th>Sertifikasi</th><th>Aksi</th></tr></thead>
                <tbody>
                    <?php if (!empty($muthawifs)): foreach ($muthawifs as $m): ?>
                    <tr>
                        <td><strong><?php echo esc_html($m->name); ?></strong></td>
                        <td><?php echo esc_html($m->phone_saudi); ?></td>
                        <td><?php echo esc_html($m->phone_indo); ?></td>
                        <td><span class="umh-badge"><?php echo esc_html($m->certification); ?></span></td>
                        <td>
                            <button class="button" onclick='openMuthawifModal(<?php echo json_encode($m); ?>)'>Edit</button>
                            <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=umh_delete_muthawif&id='.$m->id), 'umh_master_nonce'); ?>" class="button button-link-delete" onclick="return confirm('Hapus data ustadz?')">Hapus</a>
                        </td>
                    </tr>
                    <?php endforeach; else: echo '<tr><td colspan="5">Belum ada data.</td></tr>'; endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Modal Muthawif -->
        <div id="muthawif-modal" class="umh-modal" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.5);">
            <div style="background:#fff; margin:10% auto; padding:20px; width:400px; border-radius:5px;">
                <h3 id="mut-title">Tambah Muthawif</h3>
                <form action="<?php echo admin_url('admin-post.php'); ?>" method="post">
                    <input type="hidden" name="action" value="umh_save_muthawif">
                    <input type="hidden" name="id" id="mut-id">
                    <?php wp_nonce_field('umh_master_nonce'); ?>
                    
                    <p><label>Nama Lengkap</label><br><input type="text" name="name" id="mut-name" class="widefat" required></p>
                    <p><label>No HP Saudi (+966)</label><br><input type="text" name="phone_saudi" id="mut-saudi" class="widefat"></p>
                    <p><label>No HP Indo (+62)</label><br><input type="text" name="phone_indo" id="mut-indo" class="widefat"></p>
                    <p><label>Sertifikasi (Kemenag/Asosiasi)</label><br><input type="text" name="certification" id="mut-cert" class="widefat"></p>
                    
                    <div style="text-align:right;">
                        <button type="button" class="button" onclick="document.getElementById('muthawif-modal').style.display='none'">Batal</button>
                        <button type="submit" class="button button-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <!-- --- [NEW] TAB BUS --- -->
    <?php if ($active_tab === 'bus'): ?>
        <div class="tab-content" style="margin-top:20px; background:#fff; padding:20px; border:1px solid #ddd;">
            <div style="display:flex; justify-content:space-between; margin-bottom:15px;">
                <h3>Provider Transportasi (Bus)</h3>
                <button class="button button-primary" onclick="openBusModal()">Tambah Provider</button>
            </div>
            <table class="wp-list-table widefat fixed striped">
                <thead><tr><th>Nama Perusahaan</th><th>Jenis Bus</th><th>Kapasitas</th><th>Kontak Person</th><th>Aksi</th></tr></thead>
                <tbody>
                    <?php if (!empty($bus_providers)): foreach ($bus_providers as $b): ?>
                    <tr>
                        <td><strong><?php echo esc_html($b->company_name); ?></strong></td>
                        <td><?php echo esc_html($b->bus_type); ?></td>
                        <td><?php echo esc_html($b->seat_capacity); ?> Seat</td>
                        <td><?php echo esc_html($b->contact_person); ?></td>
                        <td>
                            <button class="button" onclick='openBusModal(<?php echo json_encode($b); ?>)'>Edit</button>
                            <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=umh_delete_bus&id='.$b->id), 'umh_master_nonce'); ?>" class="button button-link-delete" onclick="return confirm('Hapus?')">Hapus</a>
                        </td>
                    </tr>
                    <?php endforeach; else: echo '<tr><td colspan="5">Belum ada data.</td></tr>'; endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Modal Bus -->
        <div id="bus-modal" class="umh-modal" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.5);">
            <div style="background:#fff; margin:10% auto; padding:20px; width:400px; border-radius:5px;">
                <h3 id="bus-title">Tambah Bus Provider</h3>
                <form action="<?php echo admin_url('admin-post.php'); ?>" method="post">
                    <input type="hidden" name="action" value="umh_save_bus">
                    <input type="hidden" name="id" id="bus-id">
                    <?php wp_nonce_field('umh_master_nonce'); ?>
                    
                    <p><label>Nama Perusahaan (Misal: Saptco)</label><br><input type="text" name="company_name" id="bus-name" class="widefat" required></p>
                    <p><label>Jenis Bus</label><br>
                        <select name="bus_type" id="bus-type" class="widefat">
                            <option value="VIP">VIP (Luxury)</option>
                            <option value="Executive">Executive</option>
                            <option value="Standard">Standard</option>
                        </select>
                    </p>
                    <p><label>Kapasitas Kursi</label><br><input type="number" name="seat_capacity" id="bus-seat" class="widefat" value="45"></p>
                    <p><label>Kontak Person (Nama & HP)</label><br><input type="text" name="contact_person" id="bus-contact" class="widefat"></p>
                    
                    <div style="text-align:right;">
                        <button type="button" class="button" onclick="document.getElementById('bus-modal').style.display='none'">Batal</button>
                        <button type="submit" class="button button-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <!-- --- [NEW] TAB AIRPORTS --- -->
    <?php if ($active_tab === 'airports'): ?>
        <div class="tab-content" style="margin-top:20px; background:#fff; padding:20px; border:1px solid #ddd;">
            <div style="display:flex; justify-content:space-between; margin-bottom:15px;">
                <h3>Master Data Bandara</h3>
                <button class="button button-primary" onclick="openAirportModal()">Tambah Bandara</button>
            </div>
            <table class="wp-list-table widefat fixed striped">
                <thead><tr><th>Kode IATA</th><th>Nama Bandara</th><th>Kota</th><th>Aksi</th></tr></thead>
                <tbody>
                    <?php if (!empty($airports)): foreach ($airports as $a): ?>
                    <tr>
                        <td><strong style="background:#eee; padding:3px 6px; border-radius:3px;"><?php echo esc_html($a->iata_code); ?></strong></td>
                        <td><?php echo esc_html($a->airport_name); ?></td>
                        <td><?php echo esc_html($a->city); ?></td>
                        <td>
                            <button class="button" onclick='openAirportModal(<?php echo json_encode($a); ?>)'>Edit</button>
                            <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=umh_delete_airport&id='.$a->id), 'umh_master_nonce'); ?>" class="button button-link-delete" onclick="return confirm('Hapus?')">Hapus</a>
                        </td>
                    </tr>
                    <?php endforeach; else: echo '<tr><td colspan="4">Belum ada data.</td></tr>'; endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Modal Airport -->
        <div id="airport-modal" class="umh-modal" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.5);">
            <div style="background:#fff; margin:10% auto; padding:20px; width:400px; border-radius:5px;">
                <h3 id="air-title">Tambah Bandara</h3>
                <form action="<?php echo admin_url('admin-post.php'); ?>" method="post">
                    <input type="hidden" name="action" value="umh_save_airport">
                    <input type="hidden" name="id" id="air-id">
                    <?php wp_nonce_field('umh_master_nonce'); ?>
                    
                    <p><label>Kode IATA (3 Huruf)</label><br><input type="text" name="iata_code" id="air-code" class="widefat" maxlength="3" required placeholder="Ex: JED"></p>
                    <p><label>Nama Bandara</label><br><input type="text" name="airport_name" id="air-name" class="widefat" required></p>
                    <p><label>Kota</label><br><input type="text" name="city" id="air-city" class="widefat" required></p>
                    
                    <div style="text-align:right;">
                        <button type="button" class="button" onclick="document.getElementById('airport-modal').style.display='none'">Batal</button>
                        <button type="submit" class="button button-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

</div>

<!-- SCRIPT PENDUKUNG MODAL -->
<script>
// Fungsi Helper Umum untuk Modal (Bisa digunakan untuk semua)
function openMuthawifModal(data = null) {
    const modal = document.getElementById('muthawif-modal');
    if (data) {
        document.getElementById('mut-title').innerText = 'Edit Muthawif';
        document.getElementById('mut-id').value = data.id;
        document.getElementById('mut-name').value = data.name;
        document.getElementById('mut-saudi').value = data.phone_saudi;
        document.getElementById('mut-indo').value = data.phone_indo;
        document.getElementById('mut-cert').value = data.certification;
    } else {
        document.getElementById('mut-title').innerText = 'Tambah Muthawif';
        document.getElementById('mut-id').value = '';
        document.getElementById('mut-name').value = '';
        document.getElementById('mut-saudi').value = '';
        document.getElementById('mut-indo').value = '';
        document.getElementById('mut-cert').value = '';
    }
    modal.style.display = 'block';
}

function openBusModal(data = null) {
    const modal = document.getElementById('bus-modal');
    if (data) {
        document.getElementById('bus-title').innerText = 'Edit Bus';
        document.getElementById('bus-id').value = data.id;
        document.getElementById('bus-name').value = data.company_name;
        document.getElementById('bus-type').value = data.bus_type;
        document.getElementById('bus-seat').value = data.seat_capacity;
        document.getElementById('bus-contact').value = data.contact_person;
    } else {
        document.getElementById('bus-title').innerText = 'Tambah Bus';
        document.getElementById('bus-id').value = '';
        document.getElementById('bus-name').value = '';
        document.getElementById('bus-type').value = 'VIP';
        document.getElementById('bus-seat').value = '45';
        document.getElementById('bus-contact').value = '';
    }
    modal.style.display = 'block';
}

function openAirportModal(data = null) {
    const modal = document.getElementById('airport-modal');
    if (data) {
        document.getElementById('air-title').innerText = 'Edit Bandara';
        document.getElementById('air-id').value = data.id;
        document.getElementById('air-code').value = data.iata_code;
        document.getElementById('air-name').value = data.airport_name;
        document.getElementById('air-city').value = data.city;
    } else {
        document.getElementById('air-title').innerText = 'Tambah Bandara';
        document.getElementById('air-id').value = '';
        document.getElementById('air-code').value = '';
        document.getElementById('air-name').value = '';
        document.getElementById('air-city').value = '';
    }
    modal.style.display = 'block';
}

// Tutup modal saat klik luar
window.onclick = function(event) {
    if (event.target.classList.contains('umh-modal')) {
        event.target.style.display = "none";
    }
}
</script>