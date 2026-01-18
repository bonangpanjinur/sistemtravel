// Folder: assets/js/
// File: booking-form.js

jQuery(document).ready(function($) {
    const form = $('#umh-booking-form');
    const msgContainer = $('#umh-form-message');
    const submitBtn = form.find('button[type="submit"]');
    
    // State Harga
    let currentTotal = 0;
    let discountAmount = 0;
    let discountType = ''; // fixed / percent

    // --- 1. Kalkulasi Harga Real-time ---
    function calculateTotal() {
        if (typeof umhPricing === 'undefined' || !umhPricing) return;

        let subtotal = 0;
        const roomType = $('#room_type').val();
        const basePrice = parseFloat(umhPricing[roomType] || 0);

        $('.passenger-item').each(function() {
            const paxType = $(this).find('.pax-type-select').val();
            
            if (paxType === 'adult') {
                subtotal += basePrice;
            } else if (paxType === 'child_no_bed') {
                // Logika: Jika ada harga khusus child di DB, pakai itu. Jika tidak, diskon manual (misal 85%)
                // Di sini kita pakai asumsi simple 85% dari Quad
                subtotal += (umhPricing['child_no_bed']) ? parseFloat(umhPricing['child_no_bed']) : (basePrice * 0.85);
            } else if (paxType === 'infant') {
                subtotal += (umhPricing['infant']) ? parseFloat(umhPricing['infant']) : (basePrice * 0.20);
            }
        });

        // Apply Discount
        let finalDiscount = 0;
        if (discountType === 'fixed') {
            finalDiscount = discountAmount;
        } else if (discountType === 'percent') {
            finalDiscount = subtotal * (discountAmount / 100);
        }

        currentTotal = Math.max(0, subtotal - finalDiscount);
        
        // Format Currency
        $('#total-display').text('Rp ' + new Intl.NumberFormat('id-ID').format(currentTotal));
    }

    // Trigger kalkulasi saat ada perubahan input
    $(document).on('change', '#room_type, .pax-type-select', calculateTotal);
    
    // Init pertama kali
    calculateTotal();

    // --- 2. Cek Kupon ---
    $('#btn-check-coupon').on('click', function() {
        const code = $('#coupon_code').val();
        if(!code) return;

        $.post(umh_ajax.ajax_url, {
            action: 'umh_check_coupon',
            code: code
        }, function(res) {
            const feedback = $('#coupon-feedback');
            if (res.success) {
                feedback.html('<span style="color:green">✅ ' + res.data.message + '</span>');
                discountType = res.data.type;
                discountAmount = parseFloat(res.data.amount);
            } else {
                feedback.html('<span style="color:red">❌ ' + res.data.message + '</span>');
                discountType = '';
                discountAmount = 0;
            }
            calculateTotal(); // Recalculate total with discount
        });
    });

    // --- 3. Handle Add Passenger ---
    $('#add-passenger').on('click', function() {
        const container = $('#passenger-repeater');
        const index = container.find('.passenger-item').length;
        
        const template = container.find('.passenger-item').first().clone();
        
        template.find('input').val('');
        template.find('strong').text('Jamaah #' + (index + 1));
        template.attr('data-index', index);
        
        // Update name attributes
        template.find('input, select').each(function() {
            const name = $(this).attr('name');
            if(name) {
                const newName = name.replace(/\[\d+\]/, '[' + index + ']');
                $(this).attr('name', newName);
            }
        });

        // Tambah tombol hapus
        if (template.find('.remove-passenger').length === 0) {
            template.append('<button type="button" class="button remove-passenger" style="margin-top:10px; color:red; border:none; background:none; cursor:pointer;">Hapus</button>');
        }
        
        container.append(template);
        calculateTotal();
    });

    $(document).on('click', '.remove-passenger', function() {
        $(this).closest('.passenger-item').remove();
        calculateTotal();
    });

    // --- 4. Submit Form ---
    form.on('submit', function(e) {
        e.preventDefault();
        submitBtn.prop('disabled', true).text('Memproses...');
        
        const formData = new FormData(this);
        
        $.ajax({
            url: umh_ajax.ajax_url, // Pastikan wp_localize_script sudah dipasang di enqueue assets
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    msgContainer.show().html('<div style="background:#e6fffa; color:#2c7a7b; padding:10px;">' + response.data.message + '</div>');
                    setTimeout(function() {
                        window.location.href = response.data.redirect_url;
                    }, 1500);
                } else {
                    msgContainer.show().html('<div style="background:#fff5f5; color:#c53030; padding:10px;">Error: ' + response.data.message + '</div>');
                    submitBtn.prop('disabled', false).text('Coba Lagi');
                }
            },
            error: function() {
                alert('Server Error');
                submitBtn.prop('disabled', false).text('Konfirmasi & Pesan Sekarang');
            }
        });
    });
});