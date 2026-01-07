<div class="wrap">
    <h1>Umroh Management Dashboard</h1>
    <p>Welcome to the Enterprise Edition of Umroh Management System.</p>

    <div class="umh-dashboard-widgets" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-top: 20px;">
        <div class="card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border-left: 4px solid #2271b1;">
            <h3 style="margin-top: 0;">Total Booking</h3>
            <p class="number" style="font-size: 2em; font-weight: bold; margin: 10px 0;"><?php echo $total_bookings; ?></p>
        </div>
        <div class="card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border-left: 4px solid #46b450;">
            <h3 style="margin-top: 0;">Pendapatan</h3>
            <p class="number" style="font-size: 2em; font-weight: bold; margin: 10px 0;">Rp <?php echo number_format($total_revenue, 0, ',', '.'); ?></p>
        </div>
    </div>

    <div class="umh-dashboard-section" style="margin-top: 40px;">
        <h2>Upcoming Departures</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Package Name</th>
                    <th>Departure Date</th>
                    <th>Available Seats</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($upcoming_departures)): ?>
                    <?php foreach ($upcoming_departures as $departure): ?>
                        <tr>
                            <td><?php echo esc_html($departure->package_name); ?></td>
                            <td><?php echo esc_html($departure->departure_date); ?></td>
                            <td><?php echo esc_html($departure->available_seats); ?></td>
                            <td><?php echo esc_html(ucfirst($departure->status)); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4">No upcoming departures found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
