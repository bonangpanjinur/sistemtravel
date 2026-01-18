<?php
// File: templates/operations/inventory-scanner.php
// Tampilan untuk Petugas Logistik / Gudang
?>
<div class="wrap">
    <h1 class="wp-heading-inline">Scanner Distribusi Barang</h1>
    <hr class="wp-header-end">

    <div class="umh-scanner-container" style="display: flex; gap: 20px; margin-top: 20px;">
        
        <!-- Kolom Kiri: Form Scanner -->
        <div class="scanner-box" style="flex: 1; background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px;">
            <h2>Input Distribusi</h2>
            
            <form id="scannerForm">
                <div class="form-group" style="margin-bottom: 15px;">
                    <label style="display:block; font-weight:bold; margin-bottom: 5px;">ID Penumpang / No. Paspor</label>
                    <input type="number" id="scanPassengerId" class="regular-text" placeholder="Contoh: 101" autofocus required>
                    <p class="description">Scan QR Code pada ID Card Jemaah</p>
                </div>

                <div class="form-group" style="margin-bottom: 15px;">
                    <label style="display:block; font-weight:bold; margin-bottom: 5px;">Kode Barang (Barcode)</label>
                    <input type="text" id="scanItemCode" class="regular-text" placeholder="Scan Label Barang..." required>
                </div>

                <div style="margin-top: 20px;">
                    <button type="submit" class="button button-primary button-large" id="btnScan">
                        <span class="dashicons dashicons-products" style="margin-top: 3px;"></span> Proses Distribusi
                    </button>
                </div>
            </form>

            <div id="scanResult" style="margin-top: 20px; display: none;"></div>
        </div>

        <!-- Kolom Kanan: Log Aktivitas Sesi Ini -->
        <div class="history-box" style="flex: 1; background: #f9f9f9; padding: 20px; border: 1px solid #ccd0d4;">
            <h3>Aktivitas Terakhir</h3>
            <ul id="scanHistory" style="list-style: none; padding: 0; margin: 0;">
                <li style="color: #666; font-style: italic;">Belum ada aktivitas.</li>
            </ul>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    $('#scannerForm').on('submit', function(e) {
        e.preventDefault();
        
        var passengerId = $('#scanPassengerId').val();
        var itemCode = $('#scanItemCode').val();
        var btn = $('#btnScan');
        var resultBox = $('#scanResult');

        // Loading State
        btn.prop('disabled', true).text('Memproses...');
        resultBox.hide().removeClass('notice-success notice-error');

        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'umh_scan_distribution',
                nonce: '<?php echo wp_create_nonce("umh_scanner_nonce"); ?>',
                passenger_id: passengerId,
                item_code: itemCode
            },
            success: function(response) {
                if (response.success) {
                    // Tampilkan Sukses
                    resultBox.html('<p><strong>✅ SUKSES:</strong> ' + response.data.message + '</p>')
                             .addClass('notice notice-success inline')
                             .show();
                    
                    // Tambah ke History
                    $('#scanHistory').prepend(
                        '<li style="border-bottom:1px solid #ddd; padding:8px 0;">' +
                        '<strong>' + response.data.item_name + '</strong> ke PAX-' + passengerId + 
                        ' <span style="float:right; color:green;">OK</span></li>'
                    );

                    // Reset Input Barang (ID Penumpang biasanya tetap untuk barang berikutnya)
                    $('#scanItemCode').val('').focus();
                } else {
                    // Tampilkan Error
                    resultBox.html('<p><strong>❌ GAGAL:</strong> ' + response.data.message + '</p>')
                             .addClass('notice notice-error inline')
                             .show();
                    
                    // Play Error Sound (Optional)
                    // new Audio('error.mp3').play();
                }
            },
            error: function() {
                resultBox.html('<p>Terjadi kesalahan koneksi.</p>').addClass('notice notice-error inline').show();
            },
            complete: function() {
                btn.prop('disabled', false).html('<span class="dashicons dashicons-products"></span> Proses Distribusi');
            }
        });
    });
});
</script>