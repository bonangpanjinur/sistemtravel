// File: assets/css/js/booking-form.js

jQuery(document).ready(function($) {
    const container = $('#paxContainer');
    const totalDisplay = $('#grandTotalDisplay');
    const form = $('#umhBookingForm');
    const submitBtn = form.find('button[type="submit"]');
    
    // Format Rupiah Helper
    function formatRupiah(amount) {
        return 'Rp ' + new Intl.NumberFormat('id-ID').format(amount);
    }

    // 1. Logika Tambah Jamaah
    $('#addPaxBtn').on('click', function() {
        let index = container.children('.pax-row').length + 1;
        
        let template = `
        <div class="pax-row mt-3 pt-3 border-top" data-index="${index}">
            <div class="d-flex justify-content-between">
                <div class="pax-header font-weight-bold">Jamaah ${index}</div>
                <button type="button" class="btn btn-sm btn-danger remove-pax">Hapus</button>
            </div>
            <div class="row mt-2">
                <div class="col-md-4">
                    <input type="text" name="pax_name[]" class="form-control" placeholder="Nama Lengkap" required>
                </div>
                <div class="col-md-3">
                    <select name="pax_type[]" class="form-control pax-type-select">
                        <option value="adult">Dewasa</option>
                        <option value="child">Anak (dengan Bed)</option>
                        <option value="child_no_bed">Anak (Tanpa Bed)</option>
                        <option value="infant">Bayi (< 2 Thn)</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <input type="text" name="pax_passport[]" class="form-control" placeholder="No. Paspor">
                </div>
                <div class="col-md-2">
                    <input type="date" name="pax_expiry[]" class="form-control">
                </div>
            </div>
        </div>`;

        container.append(template);
        calculateTotal();
    });

    // Hapus Jamaah
    container.on('click', '.remove-pax', function() {
        $(this).closest('.pax-row').remove();
        // Opsional: Renumbering logic jika diperlukan agar urutan Jamaah 1, 2, 3 tetap rapi
        calculateTotal();
    });

    // 2. Kalkulasi Harga Real-time (Integrasi Logic Lama + Baru)
    let discountAmount = 0;
    let discountType = ''; // fixed / percent

    function calculateTotal() {
        let total = 0;
        
        // Ambil harga dasar dari Room Type yang dipilih
        let roomPrice = parseFloat($('input[name="room_type"]:checked').data('price')) || 0;

        // Loop setiap jamaah untuk hitung harga berdasarkan Tipe
        $('.pax-type-select').each(function() {
            let type = $(this).val();
            let paxPrice = 0;

            if (type === 'adult' || type === 'child') {
                paxPrice = roomPrice;
            } else if (type === 'infant') {
                // Gunakan harga infant dari variable global JS (umhPricing)
                // Fallback ke logic statis jika variable global belum ada
                paxPrice = (typeof umhPricing !== 'undefined' && umhPricing.infant) 
                           ? parseFloat(umhPricing.infant) 
                           : (roomPrice * 0.2); 
            } else if (type === 'child_no_bed') {
                paxPrice = (typeof umhPricing !== 'undefined' && umhPricing.child_no_bed) 
                           ? parseFloat(umhPricing.child_no_bed) 
                           : (roomPrice * 0.85);
            }

            total += paxPrice;
        });

        // Tambah Add-ons
        $('.addon-checkbox:checked').each(function() {
            let addonPrice = parseFloat($(this).data('price')) || 0;
            total += addonPrice;
        });

        // Apply Discount (Fitur dari kode lama)
        let finalDiscount = 0;
        if (discountType === 'fixed') {
            finalDiscount = discountAmount;
        } else if (discountType === 'percent') {
            finalDiscount = total * (discountAmount / 100);
        }

        total = Math.max(0, total - finalDiscount);

        // Update Tampilan
        totalDisplay.text(formatRupiah(total));
        totalDisplay.data('raw-total', total);
    }

    // Trigger kalkulasi saat ada perubahan input
    $(document).on('change', 'input[name="room_type"], .pax-type-select, .addon-checkbox', function() {
        calculateTotal();
    });

    // 3. Cek Kupon (Integrasi Fitur Lama)
    // Pastikan input kupon memiliki ID #couponCode dan ada tombol/event untuk cek
    $('#couponCode').on('change', function() {
        const code = $(this).val();
        if(!code) {
             discountAmount = 0;
             discountType = '';
             calculateTotal();
             return;
        }

        // Asumsi endpoint AJAX sudah disiapkan di wp_localize_script sebagai 'umh_ajax'
        if (typeof umh_ajax !== 'undefined') {
            $.post(umh_ajax.ajax_url, {
                action: 'umh_check_coupon',
                code: code
            }, function(res) {
                // Feedback visual bisa ditambahkan di HTML form
                if (res.success) {
                    discountType = res.data.type;
                    discountAmount = parseFloat(res.data.amount);
                    alert('Kupon berhasil digunakan: ' + res.data.message); // Simple feedback
                } else {
                    discountType = '';
                    discountAmount = 0;
                    alert('Kupon tidak valid: ' + res.data.message);
                }
                calculateTotal(); 
            });
        }
    });

    // 4. Submit Form via AJAX (Integrasi Fitur Lama)
    // Menggantikan submit form standar agar UX lebih mulus
    form.on('submit', function(e) {
        // Jika Anda ingin menggunakan AJAX submit sepenuhnya seperti kode lama:
        // Uncomment baris di bawah ini. Jika ingin tetap POST biasa (PHP handle redirect), biarkan dicomment.
        /*
        e.preventDefault();
        submitBtn.prop('disabled', true).text('Memproses...');
        
        // Tambahkan FormData logic jika diperlukan upload file
        // const formData = new FormData(this);
        
        // Namun, karena Controller PHP saat ini (BookingFormController) menggunakan admin-post.php 
        // yang mengharapkan POST request standar dan melakukan redirect, 
        // maka submit standar HTML form sebenarnya lebih kompatibel dengan backend saat ini.
        // Kecuali Anda mengubah Controller untuk menerima AJAX response JSON.
        */
       
       // Fallback sederhana untuk UX saat submit standar:
       submitBtn.prop('disabled', true).text('Sedang Memproses...');
    });

    // Init awal
    calculateTotal();
});