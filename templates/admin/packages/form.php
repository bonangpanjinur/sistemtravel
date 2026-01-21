<?php 
// Variabel yang di-inject dari Controller via View::render():
// $package, $hotels, $airlines, $pricing, $itinerary, $facilities

$isEdit = !empty($package) && isset($package->id);
// Arahkan ke admin-post.php untuk handle logic, pastikan hook 'admin_post_umh_save_package' terdaftar
$actionUrl = admin_url('admin-post.php'); 
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php echo $isEdit ? 'Edit Paket Umrah' : 'Tambah Paket Baru'; ?></h1>
    <hr class="wp-header-end">

    <form method="post" action="<?php echo esc_url($actionUrl); ?>">
        
        <!-- SECURITY: CSRF Token & Hidden Action -->
        <?php wp_nonce_field('save_package_action', 'umroh_package_nonce'); ?>
        <input type="hidden" name="action" value="umh_save_package"> 
        
        <?php if ($isEdit): ?>
            <input type="hidden" name="id" value="<?php echo esc_attr($package->id); ?>">
        <?php endif; ?>

        <div id="poststuff">
            <div id="post-body" class="metabox-holder columns-2">
                
                <!-- Main Column -->
                <div id="post-body-content">
                    
                    <!-- General Information -->
                    <div class="postbox">
                        <div class="postbox-header"><h2 class="hndle">Informasi Umum</h2></div>
                        <div class="inside">
                            <table class="form-table">
                                <tr>
                                    <th><label for="name">Nama Paket</label></th>
                                    <td>
                                        <input type="text" name="name" id="name" class="large-text" value="<?php echo $isEdit ? esc_attr($package->name) : ''; ?>" required>
                                    </td>
                                </tr>
                                <tr>
                                    <th><label for="description">Deskripsi</label></th>
                                    <td>
                                        <textarea name="description" id="description" class="large-text" rows="5"><?php echo $isEdit ? esc_textarea($package->description) : ''; ?></textarea>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <!-- Pricing Tiers -->
                    <div class="postbox">
                        <div class="postbox-header"><h2 class="hndle">Harga Berdasarkan Tipe Kamar</h2></div>
                        <div class="inside">
                            <table class="form-table">
                                <tr>
                                    <th><label for="price_quad">Quad (Sekamar Berempat)</label></th>
                                    <td>
                                        <input type="number" name="price_quad" id="price_quad" class="regular-text" placeholder="Rp" value="<?php echo isset($pricing['quad']) ? esc_attr($pricing['quad']) : ''; ?>">
                                    </td>
                                </tr>
                                <tr>
                                    <th><label for="price_triple">Triple (Sekamar Bertiga)</label></th>
                                    <td>
                                        <input type="number" name="price_triple" id="price_triple" class="regular-text" placeholder="Rp" value="<?php echo isset($pricing['triple']) ? esc_attr($pricing['triple']) : ''; ?>">
                                    </td>
                                </tr>
                                <tr>
                                    <th><label for="price_double">Double (Sekamar Berdua)</label></th>
                                    <td>
                                        <input type="number" name="price_double" id="price_double" class="regular-text" placeholder="Rp" value="<?php echo isset($pricing['double']) ? esc_attr($pricing['double']) : ''; ?>">
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <!-- Itinerary -->
                    <div class="postbox">
                        <div class="postbox-header"><h2 class="hndle">Itinerary (Rencana Perjalanan)</h2></div>
                        <div class="inside">
                            <div id="itinerary-container">
                                <?php 
                                if (!empty($itinerary) && is_array($itinerary)): 
                                    foreach ($itinerary as $index => $day):
                                        // Handle object or array access
                                        $title = is_object($day) ? $day->title : ($day['title'] ?? '');
                                        $desc = is_object($day) ? $day->description : ($day['description'] ?? '');
                                        $loc = is_object($day) ? $day->location : ($day['location'] ?? '');
                                ?>
                                <div class="itinerary-day" style="border: 1px solid #ccc; padding: 10px; margin-bottom: 10px;">
                                    <h4>Hari ke-<?php echo ($index + 1); ?></h4>
                                    <input type="text" name="itinerary[<?php echo $index; ?>][title]" class="large-text" placeholder="Judul Kegiatan" value="<?php echo esc_attr($title); ?>"><br><br>
                                    <textarea name="itinerary[<?php echo $index; ?>][description]" class="large-text" placeholder="Detail Kegiatan"><?php echo esc_textarea($desc); ?></textarea><br><br>
                                    <input type="text" name="itinerary[<?php echo $index; ?>][location]" class="regular-text" placeholder="Lokasi (Mekkah/Madinah/dll)" value="<?php echo esc_attr($loc); ?>">
                                </div>
                                <?php 
                                    endforeach;
                                endif; 
                                ?>
                            </div>
                            <button type="button" class="button" id="add-day">Tambah Hari</button>
                        </div>
                    </div>

                    <!-- Facilities (Optional) -->
                     <div class="postbox">
                         <div class="postbox-header"><h2 class="hndle">Fasilitas</h2></div>
                         <div class="inside">
                            <p><strong>Termasuk (Included):</strong></p>
                            <div id="facilities-included">
                                <?php if (!empty($facilities['included'])): foreach($facilities['included'] as $inc): 
                                    $val = is_object($inc) ? $inc->facility_name : ($inc['facility_name'] ?? $inc);
                                ?>
                                    <div style="margin-bottom:5px;">
                                        <input type="text" name="facilities[included][]" class="regular-text" value="<?php echo esc_attr($val); ?>">
                                    </div>
                                <?php endforeach; endif; ?>
                                <div style="margin-bottom:5px;">
                                    <input type="text" name="facilities[included][]" class="regular-text" placeholder="Tambah Fasilitas...">
                                </div>
                            </div>
                            <button type="button" class="button-small" onclick="addFacility('included')">+ Tambah</button>
                            
                            <hr>
                            
                            <p><strong>Tidak Termasuk (Excluded):</strong></p>
                            <div id="facilities-excluded">
                                <?php if (!empty($facilities['excluded'])): foreach($facilities['excluded'] as $exc): 
                                    $val = is_object($exc) ? $exc->facility_name : ($exc['facility_name'] ?? $exc);
                                ?>
                                    <div style="margin-bottom:5px;">
                                        <input type="text" name="facilities[excluded][]" class="regular-text" value="<?php echo esc_attr($val); ?>">
                                    </div>
                                <?php endforeach; endif; ?>
                                <div style="margin-bottom:5px;">
                                    <input type="text" name="facilities[excluded][]" class="regular-text" placeholder="Tambah Fasilitas...">
                                </div>
                            </div>
                            <button type="button" class="button-small" onclick="addFacility('excluded')">+ Tambah</button>
                         </div>
                    </div>
                </div>

                <!-- Sidebar Column -->
                <div id="postbox-container-1" class="postbox-container">
                    
                    <!-- Components -->
                    <div class="postbox">
                        <div class="postbox-header"><h2 class="hndle">Komponen Paket</h2></div>
                        <div class="inside">
                            <p><strong>Hotel Mekkah</strong><br>
                            <select name="hotel_mekkah_id" class="widefat">
                                <option value="">Pilih Hotel...</option>
                                <?php if(!empty($hotels)): foreach ($hotels as $hotel): ?>
                                    <option value="<?php echo $hotel->id; ?>" <?php selected($isEdit ? $package->hotel_mekkah_id : '', $hotel->id); ?>><?php echo esc_html($hotel->name); ?></option>
                                <?php endforeach; endif; ?>
                            </select></p>

                            <p><strong>Hotel Madinah</strong><br>
                            <select name="hotel_madinah_id" class="widefat">
                                <option value="">Pilih Hotel...</option>
                                <?php if(!empty($hotels)): foreach ($hotels as $hotel): ?>
                                    <option value="<?php echo $hotel->id; ?>" <?php selected($isEdit ? $package->hotel_madinah_id : '', $hotel->id); ?>><?php echo esc_html($hotel->name); ?></option>
                                <?php endforeach; endif; ?>
                            </select></p>

                            <p><strong>Maskapai</strong><br>
                            <select name="airline_id" class="widefat">
                                <option value="">Pilih Maskapai...</option>
                                <?php if(!empty($airlines)): foreach ($airlines as $airline): ?>
                                    <option value="<?php echo $airline->id; ?>" <?php selected($isEdit ? $package->airline_id : '', $airline->id); ?>><?php echo esc_html($airline->name); ?></option>
                                <?php endforeach; endif; ?>
                            </select></p>

                            <p><strong>Bandara Keberangkatan</strong><br>
                            <input type="text" name="departure_airport" class="widefat" placeholder="Misal: CGK" value="<?php echo $isEdit ? esc_attr($package->departure_airport) : ''; ?>"></p>
                        </div>
                    </div>

                    <!-- Cover Image -->
                    <div class="postbox">
                        <div class="postbox-header"><h2 class="hndle">Gambar Cover</h2></div>
                        <div class="inside">
                            <input type="text" name="package_image_url" class="widefat" placeholder="URL Gambar" value="<?php echo $isEdit ? esc_attr($package->package_image_url) : ''; ?>">
                            <p class="description">Masukkan URL gambar untuk ditampilkan di katalog.</p>
                        </div>
                    </div>

                    <!-- Publish Action -->
                    <div class="postbox">
                        <div class="inside">
                            <input type="submit" class="button button-primary button-large" style="width:100%" value="<?php echo $isEdit ? 'Simpan Perubahan' : 'Buat Paket'; ?>">
                            <br><br>
                            <a href="admin.php?page=umroh-packages" class="button" style="width:100%; text-align:center;">Batal</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
document.getElementById('add-day').addEventListener('click', function() {
    const container = document.getElementById('itinerary-container');
    const dayCount = container.children.length; // Array index logic
    const displayDay = dayCount + 1;
    
    const div = document.createElement('div');
    div.className = 'itinerary-day';
    div.style.border = '1px solid #ccc';
    div.style.padding = '10px';
    div.style.marginBottom = '10px';
    div.innerHTML = `
        <h4>Hari ke-${displayDay}</h4>
        <input type="text" name="itinerary[${dayCount}][title]" class="large-text" placeholder="Judul Kegiatan"><br><br>
        <textarea name="itinerary[${dayCount}][description]" class="large-text" placeholder="Detail Kegiatan"></textarea><br><br>
        <input type="text" name="itinerary[${dayCount}][location]" class="regular-text" placeholder="Lokasi (Mekkah/Madinah/dll)">
    `;
    container.appendChild(div);
});

function addFacility(type) {
    const container = document.getElementById('facilities-' + type);
    const div = document.createElement('div');
    div.style.marginBottom = '5px';
    
    const input = document.createElement('input');
    input.type = 'text';
    input.name = `facilities[${type}][]`;
    input.className = 'regular-text';
    input.placeholder = 'Nama Fasilitas...';
    
    div.appendChild(input);
    container.appendChild(div);
}
</script>