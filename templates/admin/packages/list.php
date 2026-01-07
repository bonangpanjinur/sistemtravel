<div class="wrap">
    <h1>Umroh Packages</h1>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Price</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($packages)): ?>
                <?php foreach ($packages as $package): ?>
                    <tr>
                        <td><?php echo esc_html($package->id); ?></td>
                        <td><?php echo esc_html($package->name); ?></td>
                        <td><?php echo esc_html($package->price); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="3">No packages found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
