<div class="wrap">
    <h1 class="wp-heading-inline">Daftar Cabang</h1>
    <a href="#" class="page-title-action">Tambah Cabang</a>
    <hr class="wp-header-end">

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>Nama Cabang</th>
                <th>Alamat</th>
                <th>Telepon</th>
                <th>Tanggal Dibuat</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($branches)): ?>
                <?php foreach ($branches as $branch): ?>
                    <tr>
                        <td><?php echo esc_html($branch->name); ?></td>
                        <td><?php echo esc_html($branch->address); ?></td>
                        <td><?php echo esc_html($branch->phone); ?></td>
                        <td><?php echo esc_html($branch->created_at); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4">Belum ada data cabang.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
