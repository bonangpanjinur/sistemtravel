<?php
// src/Utils/PdfGenerator.php

namespace UmhMgmt\Utils;

use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * Class PdfGenerator
 * Utility untuk membuat file PDF dari HTML menggunakan DomPDF.
 * * Requires: composer require dompdf/dompdf
 */
class PdfGenerator {
    
    /**
     * Generate PDF dan kirim ke browser (Download/Stream).
     *
     * @param string $html Konten HTML yang akan dirender
     * @param string $filename Nama file output (default: document.pdf)
     * @param string $paper Ukuran kertas (default: A4)
     * @param string $orientation Orientasi (portrait/landscape)
     * @param bool $stream Jika true, langsung download. Jika false, return string output.
     */
    public static function generate($html, $filename = 'document.pdf', $paper = 'A4', $orientation = 'portrait', $stream = true) {
        // Konfigurasi DomPDF
        $options = new Options();
        $options->set('isRemoteEnabled', true); // Penting: Agar bisa load gambar dari URL (logo, foto, dll)
        $options->set('defaultFont', 'Helvetica');
        
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper($paper, $orientation);
        
        // Render HTML ke PDF
        $dompdf->render();
        
        if ($stream) {
            // Output ke browser
            $dompdf->stream($filename, ["Attachment" => false]); // false = Preview di browser, true = Force Download
            exit;
        } else {
            return $dompdf->output();
        }
    }
}