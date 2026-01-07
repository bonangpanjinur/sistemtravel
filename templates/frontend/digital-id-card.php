<?php
// File: digital-id-card.php
// Location: templates/frontend/digital-id-card.php

/** @var object $user */
/** @var string $role_label */
/** @var string $qr_url */
/** @var string $avatar_url */
?>
<div class="umh-id-wrapper">
    <style>
        .umh-id-wrapper { font-family: sans-serif; display: flex; justify-content: center; padding: 20px; }
        .umh-id-card {
            width: 320px;
            background: #fff;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
            position: relative;
            text-align: center;
            border: 1px solid #eee;
        }
        .umh-id-header {
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
            height: 100px;
            position: relative;
        }
        .umh-id-logo {
            color: #fff;
            font-weight: bold;
            font-size: 1.2rem;
            padding-top: 15px;
            letter-spacing: 1px;
        }
        .umh-id-photo {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            border: 4px solid #fff;
            object-fit: cover;
            position: absolute;
            bottom: -50px;
            left: 50%;
            transform: translateX(-50%);
            background: #fff;
        }
        .umh-id-body {
            padding: 60px 20px 30px;
        }
        .umh-id-name {
            font-size: 1.4rem;
            font-weight: bold;
            color: #1f2937;
            margin: 0;
        }
        .umh-id-role {
            display: inline-block;
            background: #dbeafe;
            color: #1e40af;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-top: 5px;
            text-transform: uppercase;
        }
        .umh-id-qr {
            margin-top: 20px;
            border: 1px dashed #cbd5e0;
            padding: 10px;
            display: inline-block;
            border-radius: 8px;
        }
        .umh-id-qr img {
            display: block;
            width: 120px;
            height: 120px;
        }
        .umh-id-footer {
            background: #f9fafb;
            padding: 15px;
            font-size: 0.75rem;
            color: #6b7280;
            border-top: 1px solid #eee;
        }
        .umh-id-number {
            font-family: monospace;
            font-size: 0.9rem;
            color: #4b5563;
            margin-top: 10px;
            display: block;
        }
    </style>

    <div class="umh-id-card">
        <div class="umh-id-header">
            <div class="umh-id-logo">UMROH TRAVEL</div>
            <img src="<?php echo esc_url($avatar_url); ?>" class="umh-id-photo" alt="Profile">
        </div>
        
        <div class="umh-id-body">
            <h2 class="umh-id-name"><?php echo esc_html($user->display_name); ?></h2>
            <span class="umh-id-role"><?php echo esc_html($role_label); ?></span>
            
            <span class="umh-id-number">ID: <?php echo str_pad($user->ID, 6, '0', STR_PAD_LEFT); ?></span>

            <div class="umh-id-qr">
                <img src="<?php echo esc_url($qr_url); ?>" alt="QR Code">
            </div>
            
            <p style="font-size:0.8rem; color:#666; margin-top:10px;">
                Tunjukkan QR Code ini kepada petugas untuk verifikasi data.
            </p>
        </div>

        <div class="umh-id-footer">
            Berlaku sebagai identitas resmi member.
        </div>
    </div>
</div>