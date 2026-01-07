<?php
// File: manifest-print.php
// Location: templates/admin/operations/manifest-print.php

/** @var object $departure Data flight & hotel */
/** @var array $passengers List penumpang */
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Manifest - <?php echo esc_html($departure->package_name); ?></title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; margin: 20px; }
        .header { margin-bottom: 20px; border-bottom: 2px solid #000; padding-bottom: 10px; }
        .header h1 { margin: 0; font-size: 18px; text-transform: uppercase; }
        .meta-info { display: flex; gap: 40px; margin-top: 10px; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #000; padding: 5px 8px; text-align: left; vertical-align: middle; }
        th { background-color: #f0f0f0; font-weight: bold; text-transform: uppercase; font-size: 11px; }
        
        .no-print { margin-bottom: 20px; padding: 10px; background: #eee; border: 1px solid #ccc; }
        
        @media print {
            .no-print { display: none; }
            @page { margin: 1cm; size: landscape; }
        }
    </style>
</head>
<body>

    <div class="no-print">
        <button onclick="window.print()" style="font-weight:bold; padding:5px 10px; cursor:pointer;">🖨️ Cetak / Save PDF</button>
        <button onclick="exportToExcel()" style="font-weight:bold; padding:5px 10px; cursor:pointer;">📊 Download Excel (xls)</button>
        <span style="margin-left:15px; color:#555;">Tips: Tabel di bawah bisa di-copy paste langsung ke Excel.</span>
    </div>

    <div class="header">
        <h1>Manifest Keberangkatan Umroh</h1>
        <div class="meta-info">
            <div><strong>Paket:</strong> <?php echo esc_html($departure->package_name); ?></div>
            <div><strong>Tanggal:</strong> <?php echo date('d F Y', strtotime($departure->departure_date)); ?></div>
            <div><strong>Pesawat:</strong> <?php echo esc_html($departure->airline_name . ' (' . $departure->airline_code . ')'); ?></div>
            <div><strong>Hotel Mekkah:</strong> <?php echo esc_html($departure->hotel_mekkah); ?></div>
        </div>
    </div>

    <table id="manifestTable">
        <thead>
            <tr>
                <th width="30">No</th>
                <th>Nama Lengkap (Sesuai Paspor)</th>
                <th>Jenis Kelamin</th>
                <th>No. Paspor</th>
                <th>Tgl Expired</th>
                <th>Tempat Lahir</th>
                <th>Tgl Lahir</th>
                <th>NIK / KTP</th>
                <th>Status Dokumen</th>
                <th>Agen / Ref</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($passengers)): $no = 1; ?>
                <?php foreach ($passengers as $pax): ?>
                    <tr>
                        <td style="text-align:center;"><?php echo $no++; ?></td>
                        <td>
                            <strong><?php echo strtoupper(esc_html($pax->name)); ?></strong>
                            <?php if ($pax->is_tour_leader): ?><span style="color:red; font-size:10px;">(TL)</span><?php endif; ?>
                        </td>
                        <td>
                            <!-- Data gender belum ada di tabel, placeholder logic -->
                            <?php echo (strpos(strtolower($pax->name), 'binti') !== false) ? 'F' : 'M'; ?>
                        </td>
                        <td><?php echo esc_html($pax->passport_number); ?></td>
                        <td><?php echo $pax->passport_expiry ? date('d M Y', strtotime($pax->passport_expiry)) : '-'; ?></td>
                        <td>-</td> <!-- Placeholder Tempat Lahir -->
                        <td>-</td> <!-- Placeholder Tgl Lahir -->
                        <td>-</td> <!-- Placeholder NIK -->
                        <td style="text-align:center;">
                            <?php echo ($pax->doc_verification_status == 'verified') ? '✅ OK' : '⚠️ Pending'; ?>
                        </td>
                        <td><?php echo $pax->agent_name ? esc_html($pax->agent_name) : 'Langsung'; ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="10" style="text-align:center;">Belum ada penumpang terdaftar untuk keberangkatan ini.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <script>
    function exportToExcel() {
        var html = document.querySelector("table").outerHTML;
        var url = 'data:application/vnd.ms-excel,' + escape(html); // Simple data URI hack
        var link = document.createElement("a");
        link.href = url;
        link.download = "Manifest_<?php echo date('Ymd', strtotime($departure->departure_date)); ?>.xls";
        link.click();
    }
    </script>
</body>
</html>