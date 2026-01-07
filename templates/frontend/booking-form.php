<form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
    <input type="hidden" name="action" value="umh_submit_booking">
    <?php wp_nonce_field('umh_booking_nonce'); ?>
    
    <div class="umh-form-group">
        <label for="departure_id">Select Departure</label>
        <select name="departure_id" id="departure_id" required>
            <!-- Options will be populated here -->
        </select>
    </div>

    <div class="umh-form-group">
        <label for="passport_expiry">Passport Expiry Date</label>
        <input type="date" name="passport_expiry" id="passport_expiry" required>
    </div>

    <button type="submit" class="button button-primary">Book Now</button>
</form>
