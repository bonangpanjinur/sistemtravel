document.addEventListener('alpine:init', () => {
    Alpine.data('bookingForm', (initialData) => ({
        // State Awal
        departureId: initialData.departureId || 0,
        packageId: initialData.packageId || 0,
        roomType: 'quad',
        passengers: [{ name: '', type: 'adult', passport: '' }], // Minimal 1 jemaah
        addons: [],
        isLoading: false,
        priceDetails: {
            grand_total: 0,
            formatted_total: 'Rp 0'
        },

        init() {
            // Hitung harga awal saat load
            this.calculatePrice();
            
            // Watch perubahan state untuk hitung ulang otomatis
            this.$watch('roomType', () => this.calculatePrice());
            this.$watch('passengers', () => this.calculatePrice());
            this.$watch('addons', () => this.calculatePrice());
        },

        addPassenger() {
            this.passengers.push({ name: '', type: 'adult', passport: '' });
        },

        removePassenger(index) {
            if (this.passengers.length > 1) {
                this.passengers.splice(index, 1);
            } else {
                alert("Minimal harus ada 1 jemaah.");
            }
        },

        async calculatePrice() {
            this.isLoading = true;
            
            try {
                const response = await fetch(umhData.apiUrl + 'calculate-price', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': umhData.nonce
                    },
                    body: JSON.stringify({
                        package_id: this.packageId,
                        room_type: this.roomType,
                        pax_count: this.passengers.length,
                        addons: this.addons
                    })
                });

                const result = await response.json();

                if (result.success) {
                    this.priceDetails = result.data;
                }
            } catch (error) {
                console.error('Gagal menghitung harga:', error);
            } finally {
                this.isLoading = false;
            }
        }
    }));
});