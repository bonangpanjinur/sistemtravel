<div class="wrap">
    <h1 class="wp-heading-inline">Master Data Management</h1>
    
    <nav class="nav-tab-wrapper">
        <a href="?page=umh-master&tab=hotels" class="nav-tab <?php echo $active_tab === 'hotels' ? 'nav-tab-active' : ''; ?>">Hotels</a>
        <a href="?page=umh-master&tab=airlines" class="nav-tab <?php echo $active_tab === 'airlines' ? 'nav-tab-active' : ''; ?>">Airlines</a>
    </nav>

    <?php if ($active_tab === 'hotels'): ?>
        <div class="tab-content" style="margin-top: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h3>Daftar Hotel</h3>
                <button class="button button-primary" onclick="openHotelModal()">Tambah Hotel</button>
            </div>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Nama Hotel</th>
                        <th>Lokasi</th>
                        <th>Rating</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($hotels)): ?>
                        <?php foreach ($hotels as $hotel): ?>
                            <tr>
                                <td><?php echo esc_html($hotel->name); ?></td>
                                <td><?php echo esc_html($hotel->location); ?></td>
                                <td><?php echo esc_html($hotel->rating); ?> Stars</td>
                                <td>
                                    <button class="button" onclick='openHotelModal(<?php echo json_encode($hotel); ?>)'>Edit</button>
                                    <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=umh_delete_hotel&id=' . $hotel->id), 'umh_master_nonce'); ?>" class="button button-link-delete" onclick="return confirm('Yakin ingin menghapus?')">Hapus</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4">Belum ada data hotel.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div id="hotel-modal" class="umh-modal" style="display:none; position:fixed; z-index:1000; left:0; top:0; width:100%; height:100%; background-color:rgba(0,0,0,0.5);">
            <div style="background-color:#fff; margin:10% auto; padding:20px; width:400px; border-radius:5px;">
                <h3 id="hotel-modal-title">Tambah Hotel</h3>
                <form action="<?php echo admin_url('admin-post.php'); ?>" method="post">
                    <input type="hidden" name="action" value="umh_save_hotel">
                    <input type="hidden" name="id" id="hotel-id" value="">
                    <?php wp_nonce_field('umh_master_nonce'); ?>
                    <div style="margin-bottom:10px;">
                        <label>Nama Hotel</label><br>
                        <input type="text" name="name" id="hotel-name" required style="width:100%;">
                    </div>
                    <div style="margin-bottom:10px;">
                        <label>Lokasi</label><br>
                        <input type="text" name="location" id="hotel-location" required style="width:100%;">
                    </div>
                    <div style="margin-bottom:10px;">
                        <label>Rating</label><br>
                        <input type="number" name="rating" id="hotel-rating" min="1" max="5" required style="width:100%;">
                    </div>
                    <button type="submit" class="button button-primary">Simpan</button>
                    <button type="button" class="button" onclick="document.getElementById('hotel-modal').style.display='none'">Batal</button>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($active_tab === 'airlines'): ?>
        <div class="tab-content" style="margin-top: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
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
function openHotelModal(data = null) {
    const modal = document.getElementById('hotel-modal');
    const title = document.getElementById('hotel-modal-title');
    const idInput = document.getElementById('hotel-id');
    const nameInput = document.getElementById('hotel-name');
    const locationInput = document.getElementById('hotel-location');
    const ratingInput = document.getElementById('hotel-rating');

    if (data) {
        title.innerText = 'Edit Hotel';
        idInput.value = data.id;
        nameInput.value = data.name;
        locationInput.value = data.location;
        ratingInput.value = data.rating;
    } else {
        title.innerText = 'Tambah Hotel';
        idInput.value = '';
        nameInput.value = '';
        locationInput.value = '';
        ratingInput.value = '';
    }
    modal.style.display = 'block';
}

function openAirlineModal(data = null) {
    const modal = document.getElementById('airline-modal');
    const title = document.getElementById('airline-modal-title');
    const idInput = document.getElementById('airline-id');
    const nameInput = document.getElementById('airline-name');
    const codeInput = document.getElementById('airline-code');

    if (data) {
        title.innerText = 'Edit Maskapai';
        idInput.value = data.id;
        nameInput.value = data.name;
        codeInput.value = data.code;
    } else {
        title.innerText = 'Tambah Maskapai';
        idInput.value = '';
        nameInput.value = '';
        codeInput.value = '';
    }
    modal.style.display = 'block';
}
</script>
