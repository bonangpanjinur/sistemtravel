<?php
// File: InvoiceController.php
// Location: src/Controllers/Frontend/InvoiceController.php

namespace UmhMgmt\Controllers\Frontend;

use UmhMgmt\Utils\View;

class InvoiceController {
    
    public function __construct() {
        // URL akses: /wp-admin/admin-post.php?action=umh_print_invoice&booking_id=123
        add_action('admin_post_umh_print_invoice', [$this, 'print_invoice']);
    }

    public function print_invoice() {
        // Cek Login
        if (!is_user_logged_in()) {
            wp_die('Anda harus login untuk melihat invoice ini.');
        }

        $booking_id = isset($_GET['booking_id']) ? absint($_GET['booking_id']) : 0;
        if (!$booking_id) wp_die('Booking ID tidak valid.');

        global $wpdb;

        // Ambil Data Booking
        $booking = $wpdb->get_row($wpdb->prepare("
            SELECT b.*, d.departure_date, p.name as package_name, br.name as branch_name
            FROM {$wpdb->prefix}umh_bookings b
            JOIN {$wpdb->prefix}umh_departures d ON b.departure_id = d.id
            JOIN {$wpdb->prefix}umh_packages p ON d.package_id = p.id
            LEFT JOIN {$wpdb->prefix}umh_branches br ON b.branch_id = br.id
            WHERE b.id = %d
        ", $booking_id));

        // Security: Pastikan user melihat invoice miliknya sendiri (atau admin)
        $current_user_id = get_current_user_id();
        if ($booking->customer_user_id != $current_user_id && !current_user_can('manage_options')) {
            wp_die('Akses ditolak.');
        }

        // Ambil Data Penumpang
        $passengers = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}umh_booking_passengers WHERE booking_id = %d", 
            $booking_id
        ));

        // Ambil Riwayat Pembayaran
        $payments = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}umh_payments WHERE booking_id = %d AND status = 'verified'",
            $booking_id
        ));

        // Render Template Invoice Sederhana (Tanpa View class agar mandiri)
        $this->render_html($booking, $passengers, $payments);
    }

    private function render_html($booking, $passengers, $payments) {
        $total_paid = 0;
        foreach ($payments as $pay) $total_paid += $pay->amount;
        $due_amount = $booking->total_price - $total_paid;
        
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Invoice #INV-<?php echo $booking->id; ?></title>
            <style>
                body { font-family: Helvetica, Arial, sans-serif; font-size: 14px; line-height: 1.6; color: #333; }
                .invoice-box { max-width: 800px; margin: auto; padding: 30px; border: 1px solid #eee; box-shadow: 0 0 10px rgba(0, 0, 0, .15); }
                .header { display: flex; justify-content: space-between; margin-bottom: 20px; }
                .header h1 { margin: 0; color: #555; }
                .info-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                .info-table td { padding: 5px; vertical-align: top; }
                .items-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                .items-table th, .items-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                .items-table th { background-color: #f2f2f2; }
                .total-section { text-align: right; margin-top: 20px; }
                .total-row { font-size: 1.1em; font-weight: bold; }
                .status-paid { color: green; border: 2px solid green; padding: 5px 10px; display: inline-block; transform: rotate(-10deg); }
                .status-unpaid { color: red; border: 2px solid red; padding: 5px 10px; display: inline-block; }
                @media print { .no-print { display: none; } .invoice-box { border: none; box-shadow: none; } }
            </style>
        </head>
        <body onload="window.print()">
            <div class="invoice-box">
                <div class="header">
                    <div>
                        <h1>INVOICE</h1>
                        <p><strong><?php echo esc_html($booking->branch_name); ?> Travel</strong><br>
                        Izin PPIU No: 123/2024</p>
                    </div>
                    <div style="text-align:right;">
                        <h3>#INV-<?php echo str_pad($booking->id, 6, '0', STR_PAD_LEFT); ?></h3>
                        <p>Tanggal: <?php echo date('d M Y', strtotime($booking->created_at)); ?><br>
                        Status: <?php echo ($due_amount <= 0) ? '<span class="status-paid">LUNAS</span>' : '<span class="status-unpaid">BELUM LUNAS</span>'; ?>
                        </p>
                    </div>
                </div>

                <table class="info-table">
                    <tr>
                        <td width="50%">
                            <strong>Ditagihkan Kepada:</strong><br>
                            ID User: <?php echo $booking->customer_user_id; ?><br>
                            <!-- Idealnya ambil nama user dari WP User -->
                        </td>
                        <td width="50%">
                            <strong>Detail Perjalanan:</strong><br>
                            Paket: <?php echo esc_html($booking->package_name); ?><br>
                            Keberangkatan: <?php echo date('d F Y', strtotime($booking->departure_date)); ?>
                        </td>
                    </tr>
                </table>

                <h3>Rincian Biaya</h3>
                <table class="items-table">
                    <thead>
                        <tr>
                            <th>Keterangan</th>
                            <th>Tipe</th>
                            <th style="text-align:right;">Harga</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($passengers as $pax): ?>
                        <tr>
                            <td><?php echo esc_html($pax->name); ?></td>
                            <td><?php echo ucfirst($pax->pax_type); ?> (<?php echo ucfirst($pax->assigned_room_type); ?>)</td>
                            <!-- Harga satuan perlu dilogic lagi jika tidak disimpan per pax di DB, asumsi rata-rata -->
                            <td style="text-align:right;">-</td> 
                        </tr>
                        <?php endforeach; ?>
                        
                        <!-- Total -->
                        <tr>
                            <td colspan="2" style="text-align:right;"><strong>Subtotal</strong></td>
                            <td style="text-align:right;"><?php echo number_format($booking->total_price + $booking->discount_total, 0, ',', '.'); ?></td>
                        </tr>
                        <?php if($booking->discount_total > 0): ?>
                        <tr>
                            <td colspan="2" style="text-align:right; color:red;">Diskon (<?php echo $booking->coupon_code; ?>)</td>
                            <td style="text-align:right; color:red;">-<?php echo number_format($booking->discount_total, 0, ',', '.'); ?></td>
                        </tr>
                        <?php endif; ?>
                        <tr class="total-row">
                            <td colspan="2" style="text-align:right;">TOTAL TAGIHAN</td>
                            <td style="text-align:right;">Rp <?php echo number_format($booking->total_price, 0, ',', '.'); ?></td>
                        </tr>
                    </tbody>
                </table>

                <h3>Riwayat Pembayaran</h3>
                <table class="items-table">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Metode</th>
                            <th style="text-align:right;">Jumlah</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($payments as $pay): ?>
                        <tr>
                            <td><?php echo date('d/m/Y H:i', strtotime($pay->created_at)); ?></td>
                            <td><?php echo esc_html($pay->payment_method); ?> (<?php echo esc_html($pay->bank_target); ?>)</td>
                            <td style="text-align:right;">Rp <?php echo number_format($pay->amount, 0, ',', '.'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <tr>
                            <td colspan="2" style="text-align:right;"><strong>Total Terbayar</strong></td>
                            <td style="text-align:right;"><strong>Rp <?php echo number_format($total_paid, 0, ',', '.'); ?></strong></td>
                        </tr>
                        <tr>
                            <td colspan="2" style="text-align:right;"><strong>Sisa Tagihan</strong></td>
                            <td style="text-align:right; color:red;"><strong>Rp <?php echo number_format($due_amount, 0, ',', '.'); ?></strong></td>
                        </tr>
                    </tbody>
                </table>

                <div style="margin-top: 50px; font-size: 0.9em; color: #777;">
                    <p>Terima kasih telah mempercayakan perjalanan ibadah Anda bersama kami.</p>
                </div>
            </div>
        </body>
        </html>
        <?php
    }
}