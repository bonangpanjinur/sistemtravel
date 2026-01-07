<div class="wrap">
    <h1 class="wp-heading-inline">Package Management</h1>
    <button class="page-title-action" onclick="openPackageModal()">Add New Package</button>
    <hr class="wp-header-end">

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>Package Name</th>
                <th>Description</th>
                <th>Base Price</th>
                <th>Duration</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($packages)): ?>
                <?php foreach ($packages as $package): ?>
                    <tr>
                        <td><?php echo esc_html($package->name); ?></td>
                        <td><?php echo esc_html($package->description); ?></td>
                        <td>Rp <?php echo number_format($package->base_price, 0, ',', '.'); ?></td>
                        <td><?php echo esc_html($package->duration_days); ?> Days</td>
                        <td>
                            <button class="button" onclick='openPackageModal(<?php echo json_encode($package); ?>)'>Edit</button>
                            <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=umh_delete_package&id=' . $package->id), 'umh_package_nonce'); ?>" class="button button-link-delete" onclick="return confirm('Are you sure?')">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="5">No packages found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div id="package-modal" class="umh-modal" style="display:none; position:fixed; z-index:1000; left:0; top:0; width:100%; height:100%; background-color:rgba(0,0,0,0.5);">
        <div style="background-color:#fff; margin:5% auto; padding:20px; width:500px; border-radius:5px;">
            <h3 id="package-modal-title">Add New Package</h3>
            <form action="<?php echo admin_url('admin-post.php'); ?>" method="post">
                <input type="hidden" name="action" value="umh_save_package">
                <input type="hidden" name="id" id="package-id" value="">
                <?php wp_nonce_field('umh_package_nonce'); ?>
                <div style="margin-bottom:10px;">
                    <label>Package Name</label><br>
                    <input type="text" name="name" id="package-name" required style="width:100%;">
                </div>
                <div style="margin-bottom:10px;">
                    <label>Description</label><br>
                    <textarea name="description" id="package-description" style="width:100%; height:100px;"></textarea>
                </div>
                <div style="margin-bottom:10px;">
                    <label>Base Price</label><br>
                    <input type="number" name="base_price" id="package-price" required style="width:100%;">
                </div>
                <div style="margin-bottom:10px;">
                    <label>Duration (Days)</label><br>
                    <input type="number" name="duration_days" id="package-duration" required style="width:100%;">
                </div>
                <button type="submit" class="button button-primary">Save Package</button>
                <button type="button" class="button" onclick="document.getElementById('package-modal').style.display='none'">Cancel</button>
            </form>
        </div>
    </div>
</div>

<script>
function openPackageModal(data = null) {
    const modal = document.getElementById('package-modal');
    const title = document.getElementById('package-modal-title');
    const idInput = document.getElementById('package-id');
    const nameInput = document.getElementById('package-name');
    const descInput = document.getElementById('package-description');
    const priceInput = document.getElementById('package-price');
    const durationInput = document.getElementById('package-duration');

    if (data) {
        title.innerText = 'Edit Package';
        idInput.value = data.id;
        nameInput.value = data.name;
        descInput.value = data.description;
        priceInput.value = data.base_price;
        durationInput.value = data.duration_days;
    } else {
        title.innerText = 'Add New Package';
        idInput.value = '';
        nameInput.value = '';
        descInput.value = '';
        priceInput.value = '';
        durationInput.value = '';
    }
    modal.style.display = 'block';
}
</script>
