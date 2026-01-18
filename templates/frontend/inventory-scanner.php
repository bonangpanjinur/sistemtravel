<?php
// File: templates/operations/inventory-scanner.php
// Tampilan ini sekarang mendukung 2 Mode: Stock Opname (Gudang) & Distribusi (Jemaah)
?>

<div class="wrap">
    <h1>Inventory & Distribution Scanner</h1>
    
    <div style="margin-bottom: 20px; background: white; padding: 15px; border-left: 4px solid #2271b1;">
        <label style="font-weight:bold; font-size:1.2em; margin-right:15px;">Mode Scanner:</label>
        <label style="margin-right:20px;">
            <input type="radio" name="scan_mode" value="stock" checked> 📦 Stock Opname (Gudang)
        </label>
        <label>
            <input type="radio" name="scan_mode" value="distribution"> 🎁 Distribusi ke Jemaah
        </label>
    </div>

    <div class="umh-scanner-container" style="display: flex; gap: 20px;">
        
        <!-- KOLOM KIRI: INPUT & STATUS -->
        <div style="flex: 1; background: #fff; padding: 20px; border: 1px solid #ccd0d4;">
            
            <!-- PANEL DISTRIBUSI (Hidden by default) -->
            <div id="panel-distribution" style="display:none; margin-bottom:20px; padding:15px; background:#f0f6fc; border:1px solid #cce5ff;">
                <h3>👤 Data Penerima (Jemaah)</h3>
                <div style="margin-bottom:10px;">
                    <label>Scan QR Paspor/ID Jemaah:</label>
                    <input type="text" id="pax-qr-input" class="regular-text" placeholder="Scan QR Jemaah..." autofocus>
                    <input type="hidden" id="active-pax-id">
                </div>
                <div id="pax-info" style="display:none;">
                    <p><strong>Nama:</strong> <span id="pax-name">-</span></p>
                    <p><strong>Paket:</strong> <span id="pax-package">-</span></p>
                </div>
            </div>

            <!-- INPUT BARANG -->
            <div class="form-group">
                <label style="font-size: 1.5em; display:block;">Scan Barcode Barang:</label>
                <input type="text" id="barcode-input" class="large-text" style="width: 100%; height: 50px; font-size: 20px;" placeholder="Klik disini & Scan Barang..." autocomplete="off">
            </div>

            <div id="scan-feedback" style="margin-top: 20px; padding: 15px; display: none;"></div>
        </div>

        <!-- KOLOM KANAN: RIWAYAT SCAN -->
        <div style="flex: 1; background: #fff; padding: 20px; border: 1px solid #ccd0d4;">
            <h3>Riwayat Scan Sesi Ini</h3>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>Barang</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="scan-history-body">
                    <tr><td colspan="3">Belum ada scan.</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    let mode = 'stock';

    // Switch Mode
    $('input[name="scan_mode"]').change(function() {
        mode = $(this).val();
        if(mode === 'distribution') {
            $('#panel-distribution').slideDown();
            $('#pax-qr-input').focus();
        } else {
            $('#panel-distribution').slideUp();
            $('#barcode-input').focus();
        }
    });

    // 1. Scan Jemaah (Simulasi: Anggap input ID Jemaah langsung, realnya butuh AJAX lookup booking)
    $('#pax-qr-input').on('change', function() {
        // Disini harusnya ada AJAX call ke API untuk get data jemaah by QR
        // Kita simulasi saja ID jemaah masuk
        const paxId = $(this).val();
        if(paxId) {
            $('#active-pax-id').val(paxId);
            $('#pax-info').show();
            $('#pax-name').text('Jemaah ID #' + paxId); // Placeholder
            $('#barcode-input').focus();
            $(this).prop('disabled', true); // Lock jemaah ini
        }
    });

    // 2. Scan Barang
    $('#barcode-input').on('change', function() {
        const barcode = $(this).val();
        if (!barcode) return;

        if (mode === 'distribution') {
            const paxId = $('#active-pax-id').val();
            if (!paxId) {
                alert('Silakan scan QR Jemaah terlebih dahulu!');
                $(this).val('');
                $('#pax-qr-input').focus();
                return;
            }

            // AJAX Call Sprint 2
            $.post(ajaxurl, {
                action: 'umh_scan_distribution',
                item_code: barcode,
                passenger_id: paxId,
                // nonce: '...'
            }, function(res) {
                handleScanResponse(res);
            });

        } else {
            // Logic Stock Opname Lama
            // ... (Existing logic)
            console.log("Stock Opname: " + barcode);
        }

        $(this).val('').focus(); // Reset & Refocus
    });

    function handleScanResponse(res) {
        const feedback = $('#scan-feedback');
        if (res.success) {
            feedback.removeClass('notice-error').addClass('notice-success updated')
                .html('<p>✅ ' + res.data.message + '</p>').show();
            
            // Update Table Log
            let rows = '';
            if(res.data.logs) {
                res.data.logs.forEach(function(log) {
                    rows += `<tr>
                        <td>${log.taken_at}</td>
                        <td>${log.item_name}</td>
                        <td><span style="color:green">Diterima</span></td>
                    </tr>`;
                });
                $('#scan-history-body').html(rows);
            }
        } else {
            feedback.removeClass('notice-success updated').addClass('notice-error error')
                .html('<p>❌ ' + res.data.message + '</p>').show();
            
            // Suara Error (Beep)
            // new Audio('/assets/error.mp3').play();
        }
    }
});
</script>