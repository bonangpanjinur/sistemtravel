<?php
// File: catalog-grid.php
// Location: templates/frontend/catalog-grid.php
?>
<div class="umh-catalog-wrapper">
    <style>
        /* CSS Inline Sederhana untuk Grid (Sebaiknya dipindah ke file CSS terpisah) */
        .umh-catalog-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 30px;
            padding: 20px 0;
        }
        .umh-card {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            overflow: hidden;
            background: #fff;
            transition: transform 0.2s, box-shadow 0.2s;
            display: flex;
            flex-direction: column;
        }
        .umh-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px rgba(0,0,0,0.1);
        }
        .umh-card-image {
            height: 200px;
            background-color: #cbd5e0;
            position: relative;
        }
        .umh-card-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .umh-card-body {
            padding: 20px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }
        .umh-card-title {
            font-size: 1.25rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
            color: #2d3748;
            line-height: 1.4;
        }
        .umh-badge {
            display: inline-block;
            padding: 4px 8px;
            background: #ebf8ff;
            color: #2b6cb0;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-bottom: 10px;
        }
        .umh-meta {
            font-size: 0.875rem;
            color: #718096;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .umh-price-tag {
            margin-top: auto; /* Push to bottom */
            padding-top: 15px;
            border-top: 1px solid #edf2f7;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .umh-price {
            font-size: 1.1rem;
            font-weight: bold;
            color: #2f855a;
        }
        .umh-btn {
            background-color: #2271b1;
            color: white;
            padding: 8px 16px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
        }
        .umh-btn:hover {
            background-color: #135e96;
        }
    </style>

    <?php if (!empty($packages)): ?>
        <div class="umh-catalog-grid">
            <?php foreach ($packages as $pkg): ?>
                <div class="umh-card">
                    <div class="umh-card-image">
                        <?php if (!empty($pkg->package_image_url)): ?>
                            <!-- Security: esc_url untuk attribute src -->
                            <img src="<?php echo esc_url($pkg->package_image_url); ?>" alt="<?php echo esc_attr($pkg->name); ?>">
                        <?php else: ?>
                            <div style="display:flex; align-items:center; justify-content:center; height:100%; color:#718096;">
                                <span>No Image</span>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="umh-card-body">
                        <span class="umh-badge"><?php echo esc_html($pkg->duration_days); ?> Hari</span>
                        
                        <!-- Security: esc_html untuk output teks -->
                        <h3 class="umh-card-title"><?php echo esc_html($pkg->name); ?></h3>
                        
                        <div class="umh-meta">
                            <span>🏨 <?php echo esc_html($pkg->hotel_mekkah_name ?? 'Hotel TBD'); ?></span>
                            <span>✈️ <?php echo esc_html($pkg->airline_name ?? 'Airline TBD'); ?></span>
                        </div>

                        <div class="umh-price-tag">
                            <div>
                                <small style="display:block; color:#a0aec0; font-size:0.75rem;">Mulai dari</small>
                                <span class="umh-price">Rp <?php echo number_format($pkg->display_price, 0, ',', '.'); ?></span>
                            </div>
                            <!-- Security: esc_url untuk link -->
                            <a href="<?php echo esc_url($pkg->booking_url); ?>" class="umh-btn">Lihat Detail</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div style="text-align:center; padding:40px; background:#f7fafc; border-radius:8px;">
            <p>Belum ada paket umroh yang tersedia saat ini.</p>
        </div>
    <?php endif; ?>
</div>