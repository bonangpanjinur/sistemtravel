<div class="wrap">
    <h1 class="wp-heading-inline">Master Data Management</h1>
    
    <nav class="nav-tab-wrapper">
        <a href="?page=umh-master&tab=hotels" class="nav-tab <?php echo $active_tab === 'hotels' ? 'nav-tab-active' : ''; ?>">Hotels</a>
        <a href="?page=umh-master&tab=airlines" class="nav-tab <?php echo $active_tab === 'airlines' ? 'nav-tab-active' : ''; ?>">Airlines</a>
        <a href="?page=umh-master&tab=muthawifs" class="nav-tab <?php echo $active_tab === 'muthawifs' ? 'nav-tab-active' : ''; ?>">Muthawif (Ustadz)</a>
        <a href="?page=umh-master&tab=bus" class="nav-tab <?php echo $active_tab === 'bus' ? 'nav-tab-active' : ''; ?>">Bus Provider</a>
        <a href="?page=umh-master&tab=airports" class="nav-tab <?php echo $active_tab === 'airports' ? 'nav-tab-active' : ''; ?>">Bandara</a>
    </nav>

    <!-- ================= TAB HOTELS ================= -->
    <?php if ($active_tab === 'hotels'): ?>
        <div class="tab-content" style="margin-top: 20px; background:#fff; padding:20px; border:1px solid #ccd0d4;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom:15px;">
                <h3>Daftar Hotel</h3>
                <button class="button button-primary" onclick="openHotelModal()">Tambah Hotel Baru</button>
            </div>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th width="80">Foto</th>
                        <th>Nama Hotel</th>
                        <th>Lokasi</th>
                        <th>Rating</th>
                        <th>Preview Map</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($hotels)): ?>
                        <?php foreach ($hotels as $hotel): ?>
                            <tr>
                                <td>
                                    <?php if(!empty($hotel->image_url)): ?>
                                        <img src="<?php echo esc_url($hotel->image_url); ?>" style="width:60px; height:40px; object-fit:cover; border-radius:4px;">
                                    <?php else: ?>
                                        <span class="dashicons dashicons-building" style="font-size:2rem; color:#ccc;"></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?php echo esc_html($hotel->name); ?></strong><br>
                                    <small><?php echo isset($hotel->description) ? wp_trim_words($hotel->description, 8) : '-'; ?></small>
                                </td>
                                <td><?php echo esc_html($hotel->location); ?></td>
                                <td>
                                    <?php for($i=0; $i<$hotel->rating; $i++) echo '<span class="dashicons dashicons-star-filled" style="color:#f1c40f; font-size:14px;"></span>'; ?>
                                </td>
                                <td>
                                    <?php if(!empty($hotel->map_embed_code)): ?>
                                        <a href="#" class="button button-small" onclick="toggleMap(<?php echo $hotel->id; ?>); return false;">Lihat Map</a>
                                        <div id="map-preview-<?php echo $hotel->id; ?>" style="display:none; margin-top:5px; width:200px; height:150px; overflow:hidden; border:1px solid #ddd;">
                                            <?php echo $hotel->map_embed_code; ?>
                                        </div>
                                    <?php else: ?>
                                        <span style="color:#999;">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="button" onclick='openHotelModal(<?php echo json_encode($hotel); ?>)'>Edit</button>
                                    <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=umh_delete_hotel&id=' . $hotel->id), 'umh_master_nonce'); ?>" class="button button-link-delete" onclick="return confirm('Yakin ingin menghapus data hotel ini?')">Hapus</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6">Belum ada data hotel. Silakan tambah data baru.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Modal Hotel -->
        <div id="hotel-modal" class="umh-modal" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; background-color:rgba(0,0,0,0.5);">
            <div style="background-color:#fff; margin:5% auto; padding:20px; width:600px; border-radius:5px; max-height:90vh; overflow-y:auto;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; border-bottom:1px solid #eee; padding-bottom:10px;">
                    <h3 id="hotel-modal-title" style="margin:0;">Tambah Hotel</h3>
                    <button type="button" class="button-link" onclick="document.getElementById('hotel-modal').style.display='none'"><span class="dashicons dashicons-no-alt"></span></button>
                </div>
                
                <form action="<?php echo admin_url('admin-post.php'); ?>" method="post">
                    <input type="hidden" name="action" value="umh_save_hotel">
                    <input type="hidden" name="id" id="hotel-id" value="">
                    <?php wp_nonce_field('umh_master_nonce'); ?>

                    <div style="display:flex; gap:15px; margin-bottom:15px;">
                        <div style="flex:2;">
                            <label style="font-weight:600;">Nama Hotel</label>
                            <input type="text" name="name" id="hotel-name" required class="widefat" placeholder="Misal: Hilton Convention Makkah">
                        </div>
                        <div style="flex:1;">
                            <label style="font-weight:600;">Rating</label>
                            <input type="number" name="rating" id="hotel-rating" min="1" max="5" required class="widefat" placeholder="5">
                        </div>
                    </div>

                    <div style="margin-bottom:15px;">
                        <label style="font-weight:600;">Lokasi (Kota/Area)</label>
                        <input type="text" name="location" id="hotel-location" required class="widefat" placeholder="Contoh: Mekkah, 500m dari Masjidil Haram">
                    </div>

                    <div style="margin-bottom:15px;">
                        <label style="font-weight:600;">Deskripsi & Fasilitas</label>
                        <textarea name="description" id="hotel-description" rows="4" class="widefat"></textarea>
                    </div>

                    <div style="margin-bottom:15px; border:1px solid #ddd; padding:15px; background:#f9f9f9; border-radius:4px;">
                        <label style="font-weight:600; display:block; margin-bottom:5px;">Foto Hotel</label>
                        <div style="display:flex; gap:10px; align-items:flex-start;">
                            <div style="flex:1;">
                                <input type="text" name="image_url" id="hotel-image-url" class="widefat" placeholder="URL Gambar..." readonly>
                                <button type="button" class="button" id="upload-hotel-image" style="margin-top:8px;">
                                    <span class="dashicons dashicons-upload"></span> Pilih / Upload Foto
                                </button>
                            </div>
                            <div id="hotel-image-preview" style="width:100px; height:80px; background:#eee; display:flex; align-items:center; justify-content:center; overflow:hidden; border:1px solid #ccc; border-radius:4px;">
                                <span class="dashicons dashicons-format-image" style="color:#ccc; font-size:30px;"></span>
                            </div>
                        </div>
                    </div>

                    <div style="margin-bottom:20px;">
                        <label style="font-weight:600;">Google Maps Embed Code</label>
                        <textarea name="map_embed_code" id="hotel-map" rows="3" class="widefat" placeholder='<iframe src="https://www.google.com/maps/embed?..." ...></iframe>'></textarea>
                    </div>

                    <div style="text-align:right; border-top:1px solid #eee; padding-top:15px;">
                        <button type="button" class="button" onclick="document.getElementById('hotel-modal').style.display='none'">Batal</button>
                        <button type="submit" class="button button-primary">Simpan Data Hotel</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- ================= TAB AIRLINES ================= -->
    <?php if ($active_tab === 'airlines'): ?>
         <div class="tab-content" style="margin-top: 20px; background:#fff; padding:20px; border:1px solid #ccd0d4;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom:15px;">
                <h3>Daftar Maskapai</h3>
                <button class="button button-primary" onclick="openAirlineModal()">Tambah Maskapai</button>
            </div>
             <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Nama Maskapai</th>
                        <th>Kode</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($airlines)): ?>
                        <?php foreach ($airlines as $airline): ?>
                            <tr>
                                <td><?php echo esc_html($airline->name); ?></td>
                                <td><?php echo esc_html($airline->code); ?></td>
                                <td>
                                    <button class="button" onclick='openAirlineModal(<?php echo json_encode($airline); ?>)'>Edit</button>
                                    <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=umh_delete_airline&id=' . $airline->id), 'umh_master_nonce'); ?>" class="button button-link-delete" onclick="return confirm('Yakin ingin menghapus?')">Hapus</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="3">Belum ada data maskapai.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
         </div>
         
         <!-- Modal Airline -->
         <div id="airline-modal" class="umh-modal" style="display:none; position:fixed; z-index:1000; left:0; top:0; width:100%; height:100%; background-color:rgba(0,0,0,0.5);">
            <div style="background-color:#fff; margin:10% auto; padding:20px; width:400px; border-radius:5px;">
                <h3 id="airline-modal-title">Tambah Maskapai</h3>
                <form action="<?php echo admin_url('admin-post.php'); ?>" method="post">
                    <input type="hidden" name="action" value="umh_save_airline">
                    <input type="hidden" name="id" id="airline-id" value="">
                    <?php wp_nonce_field('umh_master_nonce'); ?>
                    <div style="margin-bottom:10px;">
                        <label>Nama Maskapai</label><br>
                        <input type="text" name="name" id="airline-name" required style="width:100%;">
                    </div>
                    <div style="margin-bottom:10px;">
                        <label>Kode</label><br>
                        <input type="text" name="code" id="airline-code" required style="width:100%;">
                    </div>
                    <div style="text-align:right;">
                        <button type="button" class="button" onclick="document.getElementById('airline-modal').style.display='none'">Batal</button>
                        <button type="submit" class="button button-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <!-- ================= TAB MUTHAWIFS ================= -->
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
                    <p><label>Sertifikasi</label><br><input type="text" name="certification" id="mut-cert" class="widefat"></p>
                    
                    <div style="text-align:right;">
                        <button type="button" class="button" onclick="document.getElementById('muthawif-modal').style.display='none'">Batal</button>
                        <button type="submit" class="button button-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <!-- ================= TAB BUS ================= -->
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
                    
                    <p><label>Nama Perusahaan</label><br><input type="text" name="company_name" id="bus-name" class="widefat" required></p>
                    <p><label>Jenis Bus</label><br>
                        <select name="bus_type" id="bus-type" class="widefat">
                            <option value="VIP">VIP (Luxury)</option>
                            <option value="Executive">Executive</option>
                            <option value="Standard">Standard</option>
                        </select>
                    </p>
                    <p><label>Kapasitas Kursi</label><br><input type="number" name="seat_capacity" id="bus-seat" class="widefat" value="45"></p>
                    <p><label>Kontak Person</label><br><input type="text" name="contact_person" id="bus-contact" class="widefat"></p>
                    
                    <div style="text-align:right;">
                        <button type="button" class="button" onclick="document.getElementById('bus-modal').style.display='none'">Batal</button>
                        <button type="submit" class="button button-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <!-- ================= TAB AIRPORTS ================= -->
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

<script>
// --- Toggle Map Preview ---
function toggleMap(id) {
    var el = document.getElementById('map-preview-' + id);
    if(el.style.display === 'none') {
        el.style.display = 'block';
    } else {
        el.style.display = 'none';
    }
}

// --- WP Media Uploader ---
jQuery(document).ready(function($){
    var mediaUploader;
    $('#upload-hotel-image').click(function(e) {
        e.preventDefault();
        if (mediaUploader) {
            mediaUploader.open();
            return;
        }
        mediaUploader = wp.media.frames.file_frame = wp.media({
            title: 'Pilih Foto Hotel',
            button: { text: 'Gunakan Foto Ini' },
            multiple: false
        });
        mediaUploader.on('select', function() {
            var attachment = mediaUploader.state().get('selection').first().toJSON();
            $('#hotel-image-url').val(attachment.url);
            $('#hotel-image-preview').html('<img src="' + attachment.url + '" style="width:100%; height:100%; object-fit:cover;">');
        });
        mediaUploader.open();
    });
});

// --- Modal Functions ---

function openHotelModal(data = null) {
    const modal = document.getElementById('hotel-modal');
    if (data) {
        document.getElementById('hotel-modal-title').innerText = 'Edit Hotel';
        document.getElementById('hotel-id').value = data.id;
        document.getElementById('hotel-name').value = data.name;
        document.getElementById('hotel-location').value = data.location;
        document.getElementById('hotel-rating').value = data.rating;
        document.getElementById('hotel-description').value = data.description || '';
        document.getElementById('hotel-image-url').value = data.image_url || '';
        document.getElementById('hotel-map').value = data.map_embed_code || '';
        
        if(data.image_url) {
            document.getElementById('hotel-image-preview').innerHTML = '<img src="' + data.image_url + '" style="width:100%; height:100%; object-fit:cover;">';
        } else {
             document.getElementById('hotel-image-preview').innerHTML = '<span class="dashicons dashicons-format-image" style="color:#ccc; font-size:30px;"></span>';
        }
    } else {
        document.getElementById('hotel-modal-title').innerText = 'Tambah Hotel';
        document.getElementById('hotel-id').value = '';
        document.getElementById('hotel-name').value = '';
        document.getElementById('hotel-location').value = '';
        document.getElementById('hotel-rating').value = '';
        document.getElementById('hotel-description').value = '';
        document.getElementById('hotel-image-url').value = '';
        document.getElementById('hotel-map').value = '';
        document.getElementById('hotel-image-preview').innerHTML = '<span class="dashicons dashicons-format-image" style="color:#ccc; font-size:30px;"></span>';
    }
    modal.style.display = 'block';
}

function openAirlineModal(data = null) {
    const modal = document.getElementById('airline-modal');
    if(data) {
        document.getElementById('airline-modal-title').innerText = 'Edit Maskapai';
        document.getElementById('airline-id').value = data.id;
        document.getElementById('airline-name').value = data.name;
        document.getElementById('airline-code').value = data.code;
    } else {
         document.getElementById('airline-modal-title').innerText = 'Tambah Maskapai';
         document.getElementById('airline-id').value = '';
         document.getElementById('airline-name').value = '';
         document.getElementById('airline-code').value = '';
    }
    modal.style.display = 'block';
}

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

// Tutup modal saat klik area luar
window.onclick = function(event) {
    if (event.target.classList.contains('umh-modal')) {
        event.target.style.display = "none";
    }
}
</script>