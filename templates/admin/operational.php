<div class="wrap">
    <h1 class="wp-heading-inline">Operational & Logistics</h1>
    
    <nav class="nav-tab-wrapper">
        <a href="?page=umh-operational&tab=rooming" class="nav-tab <?php echo $active_tab === 'rooming' ? 'nav-tab-active' : ''; ?>">Rooming List</a>
        <a href="?page=umh-operational&tab=logistics" class="nav-tab <?php echo $active_tab === 'logistics' ? 'nav-tab-active' : ''; ?>">Logistics Inventory</a>
        <a href="?page=umh-operational&tab=visa" class="nav-tab <?php echo $active_tab === 'visa' ? 'nav-tab-active' : ''; ?>">Visa Processing</a>
    </nav>

    <?php if ($active_tab === 'rooming'): ?>
        <div class="tab-content" style="margin-top: 20px;">
            <h3>Daftar Keberangkatan (Rooming Management)</h3>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Paket</th>
                        <th>Sisa Seat</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($departures)): ?>
                        <?php foreach ($departures as $dep): ?>
                            <tr>
                                <td><?php echo date('d M Y', strtotime($dep->departure_date)); ?></td>
                                <td><?php echo esc_html($dep->package_name); ?></td>
                                <td><?php echo esc_html($dep->available_seats); ?></td>
                                <td><span class="umh-status-<?php echo esc_attr($dep->status); ?>"><?php echo ucfirst($dep->status); ?></span></td>
                                <td><a href="#" class="button button-small">Atur Kamar</a></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5">Belum ada keberangkatan aktif.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <?php if ($active_tab === 'logistics'): ?>
        <div class="tab-content" style="margin-top: 20px;">
            <h3>Stok Barang Gudang</h3>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Kode Barang</th>
                        <th>Nama Barang</th>
                        <th>Stok</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($inventory)): ?>
                        <?php foreach ($inventory as $item): ?>
                            <tr>
                                <td><?php echo esc_html($item->item_code); ?></td>
                                <td><?php echo esc_html($item->item_name); ?></td>
                                <td><?php echo esc_html($item->stock_qty); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="3">Data gudang kosong.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
