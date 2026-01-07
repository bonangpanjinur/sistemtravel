<?php
// File: certificate-print.php
// Location: templates/frontend/certificate-print.php

/** @var object $data (jemaah_name, package_name, departure_date) */
/** @var string $director_name */
/** @var string $company_name */
/** @var string $qr_image */
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Sertifikat Umroh - <?php echo esc_html($data->jemaah_name); ?></title>
    <style>
        @page {
            size: A4 landscape;
            margin: 0;
        }
        body {
            margin: 0;
            padding: 0;
            font-family: 'Times New Roman', serif; /* Font Klasik Sertifikat */
            background-color: #fff;
            -webkit-print-color-adjust: exact; /* Pastikan background tercetak */
        }
        .cert-container {
            width: 297mm; /* Lebar A4 Landscape */
            height: 210mm; /* Tinggi A4 Landscape */
            position: relative;
            background-image: url('https://img.freepik.com/free-vector/white-gold-geometric-pattern-background-vector_53876-140726.jpg'); /* Contoh Background Batik/Islami */
            background-size: cover;
            border: 15px solid #d4af37; /* Bingkai Emas */
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #333;
        }
        .cert-inner-border {
            position: absolute;
            top: 10px; left: 10px; right: 10px; bottom: 10px;
            border: 2px solid #d4af37;
            pointer-events: none;
        }
        .cert-header {
            text-align: center;
            margin-bottom: 20px;
        }
        .cert-title {
            font-size: 48px;
            font-weight: bold;
            color: #d4af37; /* Warna Emas */
            text-transform: uppercase;
            letter-spacing: 5px;
            margin: 0;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
        }
        .cert-subtitle {
            font-size: 18px;
            margin-top: 10px;
            font-style: italic;
            color: #555;
        }
        .cert-body {
            text-align: center;
            width: 80%;
        }
        .cert-text {
            font-size: 16px;
            margin: 10px 0;
        }
        .cert-name {
            font-family: 'Pinyon Script', cursive; /* Font Sambung Elegan */
            font-size: 56px;
            color: #1a1a1a;
            margin: 20px 0;
            border-bottom: 1px solid #ccc;
            display: inline-block;
            padding-bottom: 10px;
            min-width: 500px;
        }
        .cert-details {
            font-size: 18px;
            line-height: 1.6;
            margin-bottom: 40px;
        }
        .cert-footer {
            width: 80%;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-top: 30px;
        }
        .cert-sign {
            text-align: center;
        }
        .sign-line {
            width: 200px;
            border-bottom: 1px solid #333;
            margin-bottom: 10px;
            height: 50px; /* Ruang Tanda Tangan */
        }
        .sign-name {
            font-weight: bold;
            font-size: 16px;
        }
        .sign-title {
            font-size: 12px;
            color: #666;
        }
        .cert-qr {
            text-align: center;
        }
        
        /* Font Google untuk Nama */
        @import url('https://fonts.googleapis.com/css2?family=Pinyon+Script&display=swap');

        /* Hide tombol print saat dicetak */
        @media print {
            .no-print { display: none !important; }
        }
        .no-print {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #333;
            color: #fff;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            z-index: 9999;
            box-shadow: 0 2px 10px rgba(0,0,0,0.3);
        }
    </style>
</head>
<body>

    <div class="no-print" onclick="window.print()">🖨️ Cetak / Simpan PDF</div>

    <div class="cert-container">
        <div class="cert-inner-border"></div>

        <div class="cert-header">
            <img src="<?php echo site_url('/wp-content/uploads/logo-travel.png'); ?>" alt="Logo" style="height: 60px; margin-bottom: 15px; display:none;"> <!-- Ganti URL Logo Anda -->
            <h1 class="cert-title">Sertifikat Umroh</h1>
            <div class="cert-subtitle">Diberikan sebagai tanda penghargaan kepada:</div>
        </div>

        <div class="cert-body">
            <div class="cert-name"><?php echo esc_html($data->jemaah_name); ?></div>
            
            <div class="cert-details">
                Telah menunaikan Ibadah Umroh bersama <strong><?php echo esc_html($company_name); ?></strong><br>
                Program: <?php echo esc_html($data->package_name); ?><br>
                Keberangkatan Tanggal: <strong><?php echo date('d F Y', strtotime($data->departure_date)); ?></strong>
            </div>
            
            <div class="cert-text">
                <em>"Semoga menjadi Umroh yang Mabrur dan segala amal ibadahnya diterima oleh Allah SWT."</em>
            </div>
        </div>

        <div class="cert-footer">
            <div class="cert-qr">
                <img src="<?php echo esc_url($qr_image); ?>" alt="Validasi" width="80">
                <div style="font-size: 10px; color:#777; margin-top:5px;">Scan untuk Validasi</div>
            </div>

            <div class="cert-sign">
                <!-- <img src="ttd-direktur.png" style="height:50px; position:absolute; bottom:30px;"> -->
                <div class="sign-line"></div>
                <div class="sign-name"><?php echo esc_html($director_name); ?></div>
                <div class="sign-title">Direktur Utama</div>
            </div>
        </div>
    </div>

</body>
</html>