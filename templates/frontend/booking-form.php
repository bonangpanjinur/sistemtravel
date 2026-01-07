<form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
    <input type="hidden" name="action" value="umh_submit_booking">
    <?php wp_nonce_field('umh_booking_nonce'); ?>
    
    <div class="umh-form-group">
        <label for="departure_id">Select Departure</label>
        <select name="departure_id" id="departure_id" required>
            <!-- Options will be populated here -->
        </select>
    </div>

    <div id="passenger-repeater">
        <h3>Passenger Details</h3>
        <div class="passenger-item" data-index="0">
            <div class="umh-form-group">
                <label>Name</label>
                <input type="text" name="passengers[0][name]" required>
            </div>
            <div class="umh-form-group">
                <label>Passport Number</label>
                <input type="text" name="passengers[0][passport_number]" required>
            </div>
            <div class="umh-form-group">
                <label>Passport Expiry</label>
                <input type="date" name="passengers[0][passport_expiry]" required>
            </div>
        </div>
    </div>

    <button type="button" id="add-passenger" class="button">Add Passenger</button>
    <button type="submit" class="button button-primary">Book Now</button>
</form>

<script>
document.getElementById('add-passenger').addEventListener('click', function() {
    const container = document.getElementById('passenger-repeater');
    const index = container.querySelectorAll('.passenger-item').length;
    const newItem = container.querySelector('.passenger-item').cloneNode(true);
    
    newItem.setAttribute('data-index', index);
    newItem.querySelectorAll('input').forEach(input => {
        input.name = input.name.replace(/\[\d+\]/, `[${index}]`);
        input.value = '';
    });
    
    container.appendChild(newItem);
});
</script>
