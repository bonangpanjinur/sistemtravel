<?php
// File: inventory-scanner.php
// Location: templates/admin/operations/inventory-scanner.php
?>
<div class="wrap">
    <h1 class="wp-heading-inline">Inventory Scanner</h1>
    <hr class="wp-header-end">

    <div class="umh-scanner-container" style="display:grid; grid-template-columns: 1fr 1fr; gap:20px; margin-top:20px;">
        
        <!-- Panel Kiri: Input Area -->
        <div style="background:#fff; padding:30px; border:1px solid #ccc; border-radius:8px; text-align:center;">
            
            <!-- Toggle Mode -->
            <div style="margin-bottom:20px;">
                <label style="font-weight:bold; margin-right:10px;">Mode Scanner:</label>
                <select id="scan-mode" style="font-size:16px; padding:5px;">
                    <option value="out">📤 Barang Keluar (Ke Jemaah)</option>
                    <option value="in">📥 Barang Masuk (Stok Baru)</option>
                </select>
            </div>

            <!-- Input Referensi (Opsional) -->
            <div style="margin-bottom:20px;">
                <input type="text" id="ref-id" class="large-text" placeholder="ID Jemaah / No. Booking (Opsional)" style="text-align:center;">
            </div>

            <!-- Input Barcode (Fokus Utama) -->
            <div style="margin-bottom:10px;">
                <label style="display:block; font-weight:bold; color:#2271b1;">SCAN BARCODE DI SINI 👇</label>
                <input type="text" id="barcode-input" class="large-text" style="font-size:24px; text-align:center; height:60px; border:2px solid #2271b1;" autofocus autocomplete="off">
                <p class="description">Arahkan kursor ke kotak di atas, lalu scan barang.</p>
            </div>

            <div id="loading-spinner" style="display:none; color:#666;">⏳ Memproses...</div>
        </div>

        <!-- Panel Kanan: Log Aktivitas Sesi Ini -->
        <div style="background:#fff; padding:20px; border:1px solid #ccc; border-radius:8px;">
            <h3>Log Aktivitas (Sesi Ini)</h3>
            <ul id="scan-log" style="list-style:none; margin:0; padding:0; max-height:400px; overflow-y:auto;">
                <li style="color:#888; font-style:italic; padding:10px; border-bottom:1px solid #eee;">Belum ada barang di-scan.</li>
            </ul>
        </div>
    </div>

    <!-- Audio Feedback (Opsional) -->
    <!-- <audio id="beep-ok" src="..."></audio> -->
    <!-- <audio id="beep-error" src="..."></audio> -->

    <script>
        jQuery(document).ready(function($) {
            var barcodeInput = $('#barcode-input');
            
            // Auto-focus agar staff tidak perlu klik terus
            barcodeInput.focus();
            $(document).click(function() { barcodeInput.focus(); });

            // Deteksi Enter (Scanner biasanya mengirim karakter Enter di akhir scan)
            barcodeInput.on('keypress', function(e) {
                if (e.which == 13) {
                    var code = $(this).val();
                    if(code.trim() !== "") {
                        processScan(code);
                    }
                    $(this).val(''); // Clear input siap scan berikutnya
                }
            });

            function processScan(code) {
                $('#loading-spinner').show();
                
                $.post(ajaxurl, {
                    action: 'umh_process_scan',
                    barcode: code,
                    mode: $('#scan-mode').val(),
                    ref_id: $('#ref-id').val()
                }, function(response) {
                    $('#loading-spinner').hide();
                    
                    if (response.success) {
                        addLog('success', response.data.message + ' (Sisa: ' + response.data.new_stock + ')');
                        // Play beep sound here if needed
                    } else {
                        addLog('error', 'GAGAL: ' + response.data.message);
                        alert('ERROR: ' + response.data.message); // Alert agar staff sadar ada error
                    }
                });
            }

            function addLog(type, msg) {
                var color = (type === 'success') ? 'green' : 'red';
                var icon = (type === 'success') ? '✅' : '❌';
                var time = new Date().toLocaleTimeString();
                
                var html = `<li style="padding:10px; border-bottom:1px solid #eee; color:${color};">
                    <strong>${time}</strong> ${icon} ${msg}
                </li>`;
                
                $('#scan-log').prepend(html);
            }
        });
    </script>
</div>