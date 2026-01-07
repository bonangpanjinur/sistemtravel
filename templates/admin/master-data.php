<div class="wrap">
    <h1 class="wp-heading-inline">Master Data Management</h1>
    
    <nav class="nav-tab-wrapper">
        <a href="?page=umh-master&tab=hotels" class="nav-tab <?php echo $active_tab === 'hotels' ? 'nav-tab-active' : ''; ?>">Hotels</a>
        <a href="?page=umh-master&tab=airlines" class="nav-tab <?php echo $active_tab === 'airlines' ? 'nav-tab-active' : ''; ?>">Airlines</a>
    </nav>

    <?php if ($active_tab === 'hotels'): ?>
        <div class="tab-content" style="margin-top: 20px; background:#fff; padding:20px; border:1px solid #ccd0d4; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
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
            <div style="background-color:#fff; margin:5% auto; padding:20px; width:600px; border-radius:5px; max-height:90vh; overflow-y:auto; box-shadow: 0 5px 15px rgba(0,0,0,0.3);">
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
                            <label style="font-weight:600;">Rating (Bintang)</label>
                            <input type="number" name="rating" id="hotel-rating" min="1" max="5" required class="widefat" placeholder="5">
                        </div>
                    </div>

                    <div style="margin-bottom:15px;">
                        <label style="font-weight:600;">Lokasi (Kota/Area)</label>
                        <input type="text" name="location" id="hotel-location" required class="widefat" placeholder="Contoh: Mekkah, 500m dari Masjidil Haram">
                    </div>

                    <div style="margin-bottom:15px;">
                        <label style="font-weight:600;">Deskripsi & Fasilitas</label>
                        <textarea name="description" id="hotel-description" rows="4" class="widefat" placeholder="Jelaskan fasilitas hotel, jarak ke masjid, dll..."></textarea>
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
                                <span class="dashicons dashicons-format-image" style="color:#ccc; font-size:30px; height:30px; width:30px;"></span>
                            </div>
                        </div>
                    </div>

                    <div style="margin-bottom:20px;">
                        <label style="font-weight:600;">Google Maps Embed Code</label>
                        <p class="description" style="margin-top:2px; margin-bottom:5px;">Buka Google Maps > Share > Embed a map > Copy HTML.</p>
                        <textarea name="map_embed_code" id="hotel-map" rows="3" class="widefat" placeholder='<iframe src="https://www.google.com/maps/embed?..." width="600" height="450" ...></iframe>'></textarea>
                    </div>

                    <div style="text-align:right; border-top:1px solid #eee; padding-top:15px;">
                        <button type="button" class="button" onclick="document.getElementById('hotel-modal').style.display='none'">Batal</button>
                        <button type="submit" class="button button-primary">Simpan Data Hotel</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Bagian Maskapai -->
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
                    <button type="submit" class="button button-primary">Simpan</button>
                    <button type="button" class="button" onclick="document.getElementById('airline-modal').style.display='none'">Batal</button>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
// 1. Toggle Map Preview
function toggleMap(id) {
    var el = document.getElementById('map-preview-' + id);
    if(el.style.display === 'none') {
        el.style.display = 'block';
    } else {
        el.style.display = 'none';
    }
}

// 2. WordPress Media Uploader Logic
jQuery(document).ready(function($){
    var mediaUploader;
    
    $('#upload-hotel-image').click(function(e) {
        e.preventDefault();
        
        // Jika uploader sudah ada, buka saja
        if (mediaUploader) {
            mediaUploader.open();
            return;
        }
        
        // Inisialisasi wp.media
        mediaUploader = wp.media.frames.file_frame = wp.media({
            title: 'Pilih Foto Hotel',
            button: { text: 'Gunakan Foto Ini' },
            multiple: false // Single file only
        });
        
        // Saat gambar dipilih
        mediaUploader.on('select', function() {
            var attachment = mediaUploader.state().get('selection').first().toJSON();
            $('#hotel-image-url').val(attachment.url);
            $('#hotel-image-preview').html('<img src="' + attachment.url + '" style="width:100%; height:100%; object-fit:cover;">');
        });
        
        mediaUploader.open();
    });
});

// 3. Modal Logic
function openHotelModal(data = null) {
    const modal = document.getElementById('hotel-modal');
    const title = document.getElementById('hotel-modal-title');
    
    if (data) {
        title.innerText = 'Edit Hotel';
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
        title.innerText = 'Tambah Hotel Baru';
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
    const title = document.getElementById('airline-modal-title');
    if(data) {
        title.innerText = 'Edit Maskapai';
        document.getElementById('airline-id').value = data.id;
        document.getElementById('airline-name').value = data.name;
        document.getElementById('airline-code').value = data.code;
    } else {
         title.innerText = 'Tambah Maskapai';
         document.getElementById('airline-id').value = '';
         document.getElementById('airline-name').value = '';
         document.getElementById('airline-code').value = '';
    }
    modal.style.display = 'block';
}
</script>