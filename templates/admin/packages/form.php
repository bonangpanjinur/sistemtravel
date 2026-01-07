<div class="wrap">
    <h1><?php echo isset($package) ? 'Edit Paket' : 'Tambah Paket Baru'; ?></h1>
    
    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
        <input type="hidden" name="action" value="umh_save_package">
        <?php wp_nonce_field('umh_package_nonce'); ?>
        <?php if (isset($package)): ?>
            <input type="hidden" name="id" value="<?php echo $package->id; ?>">
        <?php endif; ?>

        <div id="poststuff">
            <div id="post-body" class="metabox-holder columns-2">
                <div id="post-body-content">
                    <div class="postbox">
                        <h2 class="hndle">Informasi Umum</h2>
                        <div class="inside">
                            <table class="form-table">
                                <tr>
                                    <th>Nama Paket</th>
                                    <td><input type="text" name="name" class="large-text" required></td>
                                </tr>
                                <tr>
                                    <th>Deskripsi</th>
                                    <td><textarea name="description" class="large-text" rows="5"></textarea></td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <div class="postbox">
                        <h2 class="hndle">Harga Berdasarkan Tipe Kamar</h2>
                        <div class="inside">
                            <table class="form-table">
                                <tr>
                                    <th>Quad (Sekamar Berempat)</th>
                                    <td><input type="number" name="price_quad" class="regular-text" placeholder="Rp"></td>
                                </tr>
                                <tr>
                                    <th>Triple (Sekamar Bertiga)</th>
                                    <td><input type="number" name="price_triple" class="regular-text" placeholder="Rp"></td>
                                </tr>
                                <tr>
                                    <th>Double (Sekamar Berdua)</th>
                                    <td><input type="number" name="price_double" class="regular-text" placeholder="Rp"></td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <div class="postbox">
                        <h2 class="hndle">Itinerary (Rencana Perjalanan)</h2>
                        <div class="inside">
                            <div id="itinerary-container">
                                <!-- Dynamic Itinerary Rows -->
                            </div>
                            <button type="button" class="button" id="add-day">Tambah Hari</button>
                        </div>
                    </div>
                </div>

                <div id="postbox-container-1" class="postbox-container">
                    <div class="postbox">
                        <h2 class="hndle">Komponen Paket</h2>
                        <div class="inside">
                            <p><strong>Hotel Mekkah</strong><br>
                            <select name="hotel_mekkah_id" class="widefat">
                                <option value="">Pilih Hotel...</option>
                                <?php foreach ($hotels as $hotel): ?>
                                    <option value="<?php echo $hotel->id; ?>"><?php echo esc_html($hotel->name); ?></option>
                                <?php endforeach; ?>
                            </select></p>

                            <p><strong>Hotel Madinah</strong><br>
                            <select name="hotel_madinah_id" class="widefat">
                                <option value="">Pilih Hotel...</option>
                                <?php foreach ($hotels as $hotel): ?>
                                    <option value="<?php echo $hotel->id; ?>"><?php echo esc_html($hotel->name); ?></option>
                                <?php endforeach; ?>
                            </select></p>

                            <p><strong>Maskapai</strong><br>
                            <select name="airline_id" class="widefat">
                                <option value="">Pilih Maskapai...</option>
                                <?php foreach ($airlines as $airline): ?>
                                    <option value="<?php echo $airline->id; ?>"><?php echo esc_html($airline->name); ?></option>
                                <?php endforeach; ?>
                            </select></p>

                            <p><strong>Bandara Keberangkatan</strong><br>
                            <input type="text" name="departure_airport" class="widefat" placeholder="Misal: CGK"></p>
                        </div>
                    </div>

                    <div class="postbox">
                        <h2 class="hndle">Gambar Cover</h2>
                        <div class="inside">
                            <input type="text" name="package_image_url" class="widefat" placeholder="URL Gambar">
                        </div>
                    </div>

                    <div class="side-info-column">
                        <input type="submit" class="button-primary" value="Simpan Paket">
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
document.getElementById('add-day').addEventListener('click', function() {
    const container = document.getElementById('itinerary-container');
    const dayCount = container.children.length + 1;
    const div = document.createElement('div');
    div.className = 'itinerary-day';
    div.style.border = '1px solid #ccc';
    div.style.padding = '10px';
    div.style.marginBottom = '10px';
    div.innerHTML = `
        <h4>Hari ke-${dayCount}</h4>
        <input type="text" name="itinerary[${dayCount-1}][title]" class="large-text" placeholder="Judul Kegiatan"><br><br>
        <textarea name="itinerary[${dayCount-1}][description]" class="large-text" placeholder="Detail Kegiatan"></textarea><br><br>
        <input type="text" name="itinerary[${dayCount-1}][location]" class="regular-text" placeholder="Lokasi (Mekkah/Madinah/dll)">
    `;
    container.appendChild(div);
});
</script>
