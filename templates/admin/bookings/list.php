<div class="wrap">
    <h1>Booking List</h1>
    <p>View and manage all customer bookings.</p>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>Booking ID</th>
                <th>Customer Name</th>
                <th>Package</th>
                <th>Departure Date</th>
                <th>Total Amount</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($bookings)): ?>
                <?php foreach ($bookings as $booking): ?>
                    <tr>
                        <td>#BK-<?php echo str_pad($booking->id, 3, '0', STR_PAD_LEFT); ?></td>
                        <td><?php echo esc_html($booking->customer_name); ?></td>
                        <td><?php echo esc_html($booking->package_name); ?></td>
                        <td><?php echo esc_html($booking->departure_date); ?></td>
                        <td>Rp <?php echo number_format($booking->total_price, 0, ',', '.'); ?></td>
                        <td><span class="status-<?php echo esc_attr($booking->status); ?>"><?php echo esc_html(ucfirst($booking->status)); ?></span></td>
                        <td>
                            <?php if ($booking->status !== 'paid'): ?>
                                <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=umh_update_booking_status&id=' . $booking->id . '&status=paid'), 'umh_booking_nonce'); ?>" class="button button-small">Mark as Paid</a>
                            <?php endif; ?>
                            <button class="button button-small">View</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="7">No bookings found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
