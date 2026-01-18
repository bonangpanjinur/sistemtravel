<?php
// File: templates/frontend/invoice-print.php
// Tampilan ini didesain khusus untuk window.print() atau Save as PDF

if (!defined('ABSPATH')) exit;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #<?php echo esc_html($booking->id); ?></title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #333; line-height: 1.6; font-size: 14px; }
        .container { max-width: 800px; margin: 0 auto; padding: 20px; border: 1px solid #eee; }
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #eee; padding-bottom: 20px; margin-bottom: 20px; }
        .company-info h1 { margin: 0; font-size: 24px; color: #2c3e50; }
        .invoice-details { text-align: right; }
        .invoice-details h2 { margin: 0; color: #e74c3c; }
        .section-title { font-size: 16px; font-weight: bold; border-bottom: 1px solid #ddd; margin: 20px 0 10px; padding-bottom: 5px; color: #2c3e50; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #eee; }
        th { background-color: #f8f9fa; font-weight: bold; }
        .text-right { text-align: right; }
        .total-row td { font-weight: bold; font-size: 16px; background-color: #f8f9fa; border-top: 2px solid #ddd; }
        .status-badge { display: inline-block; padding: 5px 10px; border-radius: 4px; font-size: 12px; font-weight: bold; text-transform: uppercase; }
        .status-pending { background: #f39c12; color: #fff; }
        .status-confirmed { background: #27ae60; color: #fff; }
        .footer { margin-top: 40px; text-align: center; font-size: 12px; color: #7f8c8d; border-top: 1px solid #eee; padding-top: 20px; }
        @media print {
            .no-print { display: none; }
            .container { border: none; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="text-align: center; padding: 10px; background: #f1f1f1; margin-bottom: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; cursor: pointer; background: #3498db; color: white; border: none; border-radius: 4px;">🖨️ Cetak Invoice / Simpan PDF</button>
    </div>

    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="company-info">
                <h1><?php echo esc_html($company['name']); ?></h1>
                <div><?php echo esc_html($company['address']); ?></div>
                <div><?php echo esc_html($company['phone']); ?> | <?php echo esc_html($company['email']); ?></div>
            </div>
            <div class="invoice-details">
                <h2>INVOICE</h2>
                <div>No: <strong>INV-<?php echo str_pad($booking->id, 6, '0', STR_PAD_LEFT); ?></strong></div>
                <div>Tanggal: <?php echo date('d M Y', strtotime($booking->created_at)); ?></div>
                <div style="margin-top: 5px;">
                    <span class="status-badge status-<?php echo sanitize_html_class($booking->status); ?>">
                        <?php echo esc_html(strtoupper($booking->status)); ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Detail Pemesan & Paket -->
        <div style="display: flex; justify-content: space-between; margin-bottom: 20px;">
            <div style="width: 48%;">
                <div class="section-title">Informasi Pemesan</div>
                <strong><?php echo esc_html(get_user_by('id', $booking->customer_user_id)->display_name ?? 'Guest'); ?></strong><br>
                Cabang: <?php echo esc_html($booking->branch_name ?? 'Pusat'); ?>
            </div>
            <div style="width: 48%;">
                <div class="section-title">Detail Keberangkatan</div>
                <strong>Paket: <?php echo esc_html($booking->package_name); ?></strong><br>
                Keberangkatan: <?php echo date('d F Y', strtotime($booking->departure_date)); ?>
            </div>
        </div>

        <!-- Tabel Item (Penumpang + Addons) -->
        <div class="section-title">Rincian Biaya</div>
        <table>
            <thead>
                <tr>
                    <th>Deskripsi</th>
                    <th class="text-right">Harga Satuan</th>
                    <th class="text-right">Qty</th>
                    <th class="text-right">Total (IDR)</th>
                </tr>
            </thead>
            <tbody>
                <!-- Paket Penumpang -->
                <?php foreach ($passengers as $pax): ?>
                <tr>
                    <td>
                        Paket Umroh - <?php echo esc_html($pax->name); ?> 
                        <small style="color: #7f8c8d;">(<?php echo ucfirst($pax->pax_type); ?> - Room <?php echo ucfirst($pax->assigned_room_type); ?>)</small>
                    </td>
                    <td class="text-right">-</td>
                    <td class="text-right">1</td>
                    <td class="text-right">-</td>
                </tr>
                <?php endforeach; ?>
                
                <!-- Spacer Row untuk Total Paket (Simplified Display) -->
                 <tr>
                    <td colspan="3" style="font-style: italic;">Total Biaya Paket Dasar (<?php echo count($passengers); ?> Jamaah)</td>
                    <td class="text-right"><?php echo number_format($booking->total_price - array_sum(array_column($addons, 'total_price')) + $booking->discount_total, 0, ',', '.'); ?></td>
                </tr>

                <!-- Add-ons -->
                <?php if (!empty($addons)): ?>
                    <?php foreach ($addons as $addon): ?>
                    <tr>
                        <td>Layanan Tambahan: <?php echo esc_html($addon->service_name); ?></td>
                        <td class="text-right"><?php echo number_format($addon->total_price / $addon->quantity, 0, ',', '.'); ?></td>
                        <td class="text-right"><?php echo esc_html($addon->quantity); ?></td>
                        <td class="text-right"><?php echo number_format($addon->total_price, 0, ',', '.'); ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>

                <!-- Diskon -->
                <?php if ($booking->discount_total > 0): ?>
                <tr style="color: #27ae60;">
                    <td colspan="3">Diskon / Kupon (<?php echo esc_html($booking->coupon_code); ?>)</td>
                    <td class="text-right">- <?php echo number_format($booking->discount_total, 0, ',', '.'); ?></td>
                </tr>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td colspan="3" class="text-right">GRAND TOTAL</td>
                    <td class="text-right">Rp <?php echo number_format($booking->total_price, 0, ',', '.'); ?></td>
                </tr>
            </tfoot>
        </table>

        <!-- Instruksi Pembayaran -->
        <div class="section-title">Instruksi Pembayaran</div>
        <p>Silakan lakukan pembayaran ke rekening berikut:</p>
        <div style="background: #f8f9fa; padding: 15px; border-radius: 4px;">
            <strong>Bank Syariah Indonesia (BSI)</strong><br>
            No. Rekening: <strong>7788-9900-11</strong><br>
            Atas Nama: <strong>PT. Travel Umroh Berkah</strong><br>
            Berita Transfer: <strong>BOOK-<?php echo $booking->id; ?></strong>
        </div>

        <div class="footer">
            <p>Terima kasih telah mempercayakan perjalanan ibadah Anda kepada kami.</p>
            <small>Dicetak pada: <?php echo date('d-m-Y H:i:s'); ?></small>
        </div>
    </div>
</body>
</html>