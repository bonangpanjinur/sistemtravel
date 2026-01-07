<div class="wrap">
    <h1 class="wp-heading-inline">Data Karyawan</h1>
    <a href="#" class="page-title-action">Tambah Karyawan</a>
    <hr class="wp-header-end">

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>Nama</th>
                <th>Jabatan</th>
                <th>Gaji Pokok</th>
                <th>Tanggal Bergabung</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($employees)): ?>
                <?php foreach ($employees as $emp): ?>
                    <tr>
                        <td><?php echo esc_html($emp->name); ?></td>
                        <td><?php echo esc_html($emp->position); ?></td>
                        <td>Rp <?php echo number_format($emp->base_salary, 0, ',', '.'); ?></td>
                        <td><?php echo esc_html($emp->joined_at); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4">Belum ada data karyawan.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
