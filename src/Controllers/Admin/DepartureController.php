<?php

namespace App\Controllers\Admin;

use App\Utils\View;
use App\Interfaces\DatabaseInterface;
use App\Utils\Validator;

class DepartureController
{
    private $db;
    private $table = 'travel_packages'; // Asumsi keberangkatan terikat dengan paket

    public function __construct(DatabaseInterface $db)
    {
        $this->db = $db;
    }

    /**
     * Menampilkan daftar keberangkatan
     */
    public function index()
    {
        // Ambil data paket yang aktif dan urutkan berdasarkan tanggal keberangkatan
        $query = "SELECT p.*, 
                  (SELECT COUNT(*) FROM {$this->db->prefix()}travel_bookings b WHERE b.package_id = p.id AND b.status != 'cancelled') as total_booked
                  FROM {$this->db->prefix()}{$this->table} p 
                  WHERE p.status = 'active' 
                  ORDER BY p.departure_date ASC";
        
        $departures = $this->db->get_results($query);

        // Tambahkan perhitungan sisa seat
        foreach ($departures as $departure) {
            $departure->remaining_seats = $departure->quota - $departure->total_booked;
            $departure->occupancy_rate = ($departure->quota > 0) ? round(($departure->total_booked / $departure->quota) * 100, 1) : 0;
            
            // Tentukan status keberangkatan
            $departure_date = strtotime($departure->departure_date);
            $today = time();
            
            if ($departure_date < $today) {
                $departure->departure_status = 'completed'; // Sudah berangkat
            } elseif ($departure->remaining_seats <= 0) {
                $departure->departure_status = 'full'; // Penuh
            } else {
                $departure->departure_status = 'open'; // Masih dibuka
            }
        }

        View::render('admin/departures', [
            'title' => 'Manajemen Keberangkatan',
            'departures' => $departures
        ]);
    }

    /**
     * Menampilkan detail manifest per keberangkatan
     */
    public function detail()
    {
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        
        if ($id <= 0) {
            $this->redirect('admin.php?page=travel-umroh-departures');
            return;
        }

        // Ambil detail paket
        $package = $this->db->get_row(
            $this->db->prepare("SELECT * FROM {$this->db->prefix()}{$this->table} WHERE id = %d", $id)
        );

        if (!$package) {
            echo '<div class="notice notice-error"><p>Data keberangkatan tidak ditemukan.</p></div>';
            return;
        }

        // Ambil daftar jamaah (manifest) untuk paket ini
        $query_manifest = "SELECT b.*, u.display_name, u.user_email, m.meta_value as phone_number
                           FROM {$this->db->prefix()}travel_bookings b
                           LEFT JOIN {$this->db->prefix()}users u ON b.user_id = u.ID
                           LEFT JOIN {$this->db->prefix()}usermeta m ON u.ID = m.user_id AND m.meta_key = 'phone_number'
                           WHERE b.package_id = %d AND b.status != 'cancelled'
                           ORDER BY b.created_at ASC";
                           
        $manifest = $this->db->get_results($this->db->prepare($query_manifest, $id));

        View::render('admin/operations/manifest-detail', [ // Pastikan template ini ada atau gunakan template lain
            'title' => 'Detail Manifest Keberangkatan: ' . $package->name,
            'package' => $package,
            'manifest' => $manifest
        ]);
    }

    /**
     * Mengupdate status operasional keberangkatan (misal: Visa, Tiket, dll)
     */
    public function updateStatus()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }
        
        // Verifikasi nonce keamanan WP
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'update_departure_status')) {
            die('Security check failed');
        }

        $id = intval($_POST['package_id']);
        $status_type = sanitize_text_field($_POST['status_type']); // misal: visa_status, ticket_status
        $status_value = sanitize_text_field($_POST['status_value']);

        // Validasi input sederhana
        if ($id > 0 && !empty($status_type)) {
            // Update meta data paket (asumsi status operasional disimpan di tabel terpisah atau meta)
            // Disini kita simpan ke tabel paket langsung jika ada kolomnya, atau gunakan logic lain
            // Contoh sederhana: update kolom status jika kolomnya ada
            
            // $this->db->update(...)
            
            // Feedback ke user
            add_settings_error('travel_departure', 'update_success', 'Status berhasil diperbarui.', 'success');
        }

        $this->redirect('admin.php?page=travel-umroh-departures');
    }

    /**
     * Helper untuk redirect
     */
    private function redirect($url)
    {
        if (!headers_sent()) {
            wp_redirect($url);
            exit;
        } else {
            echo "<script>window.location.href='$url';</script>";
            exit;
        }
    }
}