<div class="wrap">
    <h1 class="wp-heading-inline">Departures Management</h1>
    <button class="page-title-action" onclick="document.getElementById('departure-modal').style.display='block'">Add New Departure</button>
    <hr class="wp-header-end">
    
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>Package Name</th>
                <th>Departure Date</th>
                <th>Seats (Available/Total)</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($departures)): ?>
                <?php foreach ($departures as $departure): ?>
                    <tr>
                        <td><?php echo esc_html($departure->package_name); ?></td>
                        <td><?php echo esc_html($departure->departure_date); ?></td>
                        <td><?php echo esc_html($departure->available_seats); ?> / <?php echo esc_html($departure->total_seats); ?></td>
                        <td><span class="status-<?php echo esc_attr($departure->status); ?>"><?php echo esc_html(ucfirst($departure->status)); ?></span></td>
                        <td>
                            <button class="button">Manifest</button>
                            <button class="button">Edit</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="5">No departures found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div id="departure-modal" class="umh-modal" style="display:none; position:fixed; z-index:1000; left:0; top:0; width:100%; height:100%; background-color:rgba(0,0,0,0.5);">
        <div style="background-color:#fff; margin:10% auto; padding:20px; width:400px; border-radius:5px;">
            <h3>Add New Departure</h3>
            <form action="<?php echo admin_url('admin-post.php'); ?>" method="post">
                <input type="hidden" name="action" value="umh_save_departure">
                <?php wp_nonce_field('umh_departure_nonce'); ?>
                <div style="margin-bottom:10px;">
                    <label>Select Package</label><br>
                    <select name="package_id" required style="width:100%;">
                        <option value="">-- Select Package --</option>
                        <?php foreach ($packages as $package): ?>
                            <option value="<?php echo $package->id; ?>"><?php echo esc_html($package->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="margin-bottom:10px;">
                    <label>Departure Date</label><br>
                    <input type="date" name="departure_date" required style="width:100%;">
                </div>
                <div style="margin-bottom:10px;">
                    <label>Total Seats</label><br>
                    <input type="number" name="total_seats" required style="width:100%;">
                </div>
                <button type="submit" class="button button-primary">Save Departure</button>
                <button type="button" class="button" onclick="document.getElementById('departure-modal').style.display='none'">Cancel</button>
            </form>
        </div>
    </div>
</div>
