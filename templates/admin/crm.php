<div class="wrap">
    <h1 class="wp-heading-inline">CRM & Leads Management</h1>
    
    <div class="tab-content" style="margin-top: 20px;">
        <h3>Daftar Leads (Calon Jamaah)</h3>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Telepon</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Sumber</th>
                    <th>Tanggal Masuk</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($leads)): ?>
                    <?php foreach ($leads as $lead): ?>
                        <tr>
                            <td><?php echo esc_html($lead->name); ?></td>
                            <td><?php echo esc_html($lead->phone); ?></td>
                            <td><?php echo esc_html($lead->email); ?></td>
                            <td><span class="umh-status-<?php echo esc_attr($lead->status); ?>"><?php echo ucfirst($lead->status); ?></span></td>
                            <td><?php echo esc_html($lead->source); ?></td>
                            <td><?php echo date('d M Y', strtotime($lead->created_at)); ?></td>
                            <td><a href="#" class="button button-small">Follow Up</a></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="7">Belum ada data leads.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

