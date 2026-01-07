<div class="wrap">
    <h1>Booking List</h1>
    <p>View and manage all customer bookings.</p>

    <div class="tablenav top">
        <div class="alignleft actions">
            <select name="filter_status">
                <option value="">All Status</option>
                <option value="paid">Paid</option>
                <option value="partial">Partial</option>
                <option value="unpaid">Unpaid</option>
            </select>
            <button class="button">Filter</button>
        </div>
    </div>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>Booking ID</th>
                <th>Customer Name</th>
                <th>Package</th>
                <th>Booking Date</th>
                <th>Total Amount</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>#BK-001</td>
                <td>Ahmad Fauzi</td>
                <td>Umroh Reguler Januari 2026</td>
                <td>2025-12-01</td>
                <td>Rp 35.000.000</td>
                <td><span class="status-paid">Paid</span></td>
                <td>
                    <button class="button">View</button>
                    <button class="button">Invoice</button>
                </td>
            </tr>
            <tr>
                <td>#BK-002</td>
                <td>Siti Aminah</td>
                <td>Umroh Plus Turki Februari 2026</td>
                <td>2025-12-05</td>
                <td>Rp 45.000.000</td>
                <td><span class="status-partial">Partial</span></td>
                <td>
                    <button class="button">View</button>
                    <button class="button">Invoice</button>
                </td>
            </tr>
        </tbody>
    </table>
</div>
