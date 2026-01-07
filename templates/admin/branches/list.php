<?php
// File: list.php
// Location: templates/admin/branches/list.php
?>
<div class="wrap">
    <h1 class="wp-heading-inline">Data Kantor Cabang</h1>
    <button class="page-title-action" onclick="openBranchModal()">Tambah Cabang</button>
    <hr class="wp-header-end">

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>Nama Cabang</th>
                <th>Alamat</th>
                <th>Telepon</th>
                <th>Total Booking</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($branches)): ?>
                <?php foreach ($branches as $row): ?>
                    <tr>
                        <td><strong><?php echo esc_html($row->name); ?></strong></td>
                        <td><?php echo esc_html($row->address); ?></td>
                        <td><?php echo esc_html($row->phone); ?></td>
                        <td>
                            <span class="update-plugins count-<?php echo $row->total_bookings; ?>">
                                <?php echo $row->total_bookings; ?> Pax
                            </span>
                        </td>
                        <td>
                            <button class="button" onclick='openBranchModal(<?php echo json_encode($row); ?>)'>Edit</button>
                            <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=umh_delete_branch&id=' . $row->id), 'umh_branch_nonce'); ?>" 
                               class="button button-link-delete" 
                               onclick="return confirm('Hapus cabang ini?')">Hapus</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="5">Belum ada data cabang.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Modal Form -->
<div id="branchModal" class="umh-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5);">
    <div style="background:#fff; width:400px; margin:10% auto; padding:20px; border-radius:5px;">
        <h3 id="modal-title">Tambah Cabang</h3>
        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
            <input type="hidden" name="action" value="umh_save_branch">
            <input type="hidden" name="id" id="branch-id">
            <?php wp_nonce_field('umh_branch_nonce'); ?>

            <div style="margin-bottom:10px;">
                <label style="display:block; font-weight:bold;">Nama Cabang</label>
                <input type="text" name="name" id="branch-name" class="widefat" required placeholder="Contoh: Cabang Surabaya">
            </div>
            
            <div style="margin-bottom:10px;">
                <label style="display:block; font-weight:bold;">Telepon</label>
                <input type="text" name="phone" id="branch-phone" class="widefat" placeholder="031-xxxxx">
            </div>

            <div style="margin-bottom:15px;">
                <label style="display:block; font-weight:bold;">Alamat Lengkap</label>
                <textarea name="address" id="branch-addr" class="widefat" rows="3"></textarea>
            </div>

            <div style="text-align:right;">
                <button type="button" class="button" onclick="document.getElementById('branchModal').style.display='none'">Batal</button>
                <button type="submit" class="button button-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
function openBranchModal(data = null) {
    document.getElementById('branchModal').style.display = 'block';
    if (data) {
        document.getElementById('modal-title').innerText = 'Edit Cabang';
        document.getElementById('branch-id').value = data.id;
        document.getElementById('branch-name').value = data.name;
        document.getElementById('branch-phone').value = data.phone;
        document.getElementById('branch-addr').value = data.address;
    } else {
        document.getElementById('modal-title').innerText = 'Tambah Cabang';
        document.getElementById('branch-id').value = '';
        document.getElementById('branch-name').value = '';
        document.getElementById('branch-phone').value = '';
        document.getElementById('branch-addr').value = '';
    }
}
</script>