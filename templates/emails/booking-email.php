<?php
// File: templates/emails/booking-email.php
// Template Email HTML Responsif
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?php echo esc_html($title); ?></title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 0; }
        .email-container { max-width: 600px; margin: 20px auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .header { background: #2271b1; color: #ffffff; padding: 20px; text-align: center; }
        .content { padding: 30px; color: #333333; line-height: 1.6; }
        .info-box { background: #f9f9f9; border: 1px solid #eeeeee; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .btn { display: inline-block; background: #d63638; color: #ffffff; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold; margin-top: 20px; }
        .footer { background: #333333; color: #aaaaaa; text-align: center; padding: 15px; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 8px 0; border-bottom: 1px solid #eee; }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h2 style="margin:0;">Konfirmasi Booking Umroh</h2>
        </div>
        
        <div class="content">
            <p>Assalamu'alaikum <strong><?php echo esc_html($booking->display_name); ?></strong>,</p>
            <p>Terima kasih telah melakukan pemesanan paket umroh bersama kami. Berikut adalah detail pesanan Anda:</p>
            
            <div class="info-box">
                <table>
                    <tr>
                        <td><strong>No. Booking</strong></td>
                        <td>#<?php echo esc_html($booking->id); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Nama Paket</strong></td>
                        <td><?php echo esc_html($booking->package_name); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Keberangkatan</strong></td>
                        <td><?php echo date('d F Y', strtotime($booking->departure_date)); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Total Tagihan</strong></td>
                        <td style="color:#d63638; font-weight:bold;">Rp <?php echo number_format($booking->total_price, 0, ',', '.'); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Status</strong></td>
                        <td><?php echo strtoupper($booking->status); ?></td>
                    </tr>
                </table>
            </div>

            <?php if ($booking->status == 'pending'): ?>
                <p>Silakan segera lakukan pembayaran agar kursi Anda aman. Klik tombol di bawah untuk melihat Invoice dan cara pembayaran:</p>
                <center>
                    <a href="<?php echo admin_url('admin-post.php?action=umh_print_invoice&booking_id=' . $booking->id); ?>" class="btn">Lihat Invoice & Bayar</a>
                </center>
            <?php else: ?>
                <p>Pembayaran Anda telah kami terima. Tim kami akan segera menghubungi Anda untuk proses selanjutnya.</p>
            <?php endif; ?>
        </div>

        <div class="footer">
            &copy; <?php echo date('Y'); ?> <?php echo get_bloginfo('name'); ?>. All rights reserved.<br>
            Alamat Kantor: Jl. Contoh Travel No. 123
        </div>
    </div>
</body>
</html>