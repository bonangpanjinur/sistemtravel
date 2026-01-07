// Folder: assets/js/
// File: booking-form.js

jQuery(document).ready(function($) {
    const form = $('#umh-booking-form');
    const msgContainer = $('#umh-form-message');
    const submitBtn = form.find('button[type="submit"]');

    // Handle Add Passenger
    $('#add-passenger').on('click', function() {
        const container = $('#passenger-repeater');
        const index = container.find('.passenger-item').length;
        
        // Clone item pertama
        const template = container.find('.passenger-item').first().clone();
        
        // Reset nilai input
        template.find('input').val('');
        template.attr('data-index', index);
        
        // Update atribut name array: passengers[0][name] -> passengers[1][name]
        template.find('input').each(function() {
            const name = $(this).attr('name');
            const newName = name.replace(/\[\d+\]/, '[' + index + ']');
            $(this).attr('name', newName);
        });

        // Tambahkan tombol hapus jika bukan item pertama
        if (template.find('.remove-passenger').length === 0) {
            template.append('<button type="button" class="button remove-passenger" style="margin-top:5px; color:red;">Hapus Penumpang</button>');
        }
        
        container.append(template);
    });

    // Handle Remove Passenger (Delegated event)
    $(document).on('click', '.remove-passenger', function() {
        $(this).closest('.passenger-item').remove();
    });

    // Handle Form Submit (AJAX)
    form.on('submit', function(e) {
        e.preventDefault();

        // UX: Loading State
        submitBtn.prop('disabled', true).text('Memproses...');
        msgContainer.hide().removeClass('umh-success umh-error').html('');

        const formData = new FormData(this);
        formData.append('action', 'umh_submit_booking_ajax'); // Action hook WP

        $.ajax({
            url: umh_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    msgContainer.addClass('umh-success').html(response.data.message).fadeIn();
                    form[0].reset(); // Reset form jika sukses
                    // Opsional: Redirect ke halaman terima kasih
                    // window.location.href = response.data.redirect_url;
                } else {
                    msgContainer.addClass('umh-error').html(response.data.message).fadeIn();
                }
            },
            error: function(xhr, status, error) {
                msgContainer.addClass('umh-error').html('Terjadi kesalahan server. Silakan coba lagi.').fadeIn();
                console.error(error);
            },
            complete: function() {
                // UX: Restore Button
                submitBtn.prop('disabled', false).text('Book Now');
                // Scroll ke pesan
                $('html, body').animate({
                    scrollTop: msgContainer.offset().top - 100
                }, 500);
            }
        });
    });
});