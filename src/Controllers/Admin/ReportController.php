<?php

namespace App\Controllers\Admin;

use App\Utils\View;
use App\Interfaces\DatabaseInterface;
use App\Repositories\BookingRepository;
use App\Repositories\FinanceRepository;

class ReportController
{
    private $db;
    private $bookingRepo;
    private $financeRepo;

    public function __construct(DatabaseInterface $db, BookingRepository $bookingRepo, FinanceRepository $financeRepo)
    {
        $this->db = $db;
        $this->bookingRepo = $bookingRepo;
        $this->financeRepo = $financeRepo;
    }

    /**
     * Main Report Handler
     * Mengarahkan ke jenis laporan berdasarkan parameter $_GET['type']
     */
    public function index()
    {
        $type = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : 'financial';
        $start_date = isset($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : date('Y-m-01');
        $end_date = isset($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : date('Y-m-t');

        switch ($type) {
            case 'sales':
                $this->salesReport($start_date, $end_date);
                break;
            case 'manifest':
                $this->manifestReport($start_date, $end_date);
                break;
            case 'operational':
                $this->operationalReport($start_date, $end_date);
                break;
            case 'financial':
            default:
                $this->financialReport($start_date, $end_date);
                break;
        }
    }

    /**
     * Laporan Keuangan (Pemasukan vs Pengeluaran)
     */
    private function financialReport($start_date, $end_date)
    {
        // 1. Ambil data transaksi dari database
        $query = "SELECT 
                    DATE(transaction_date) as date,
                    SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as income,
                    SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as expense
                  FROM {$this->db->prefix()}travel_finance
                  WHERE transaction_date BETWEEN %s AND %s
                  GROUP BY DATE(transaction_date)
                  ORDER BY date ASC";
        
        $daily_stats = $this->db->get_results($this->db->prepare($query, $start_date, $end_date));

        // 2. Hitung total ringkasan
        $total_income = 0;
        $total_expense = 0;
        foreach ($daily_stats as $stat) {
            $total_income += $stat->income;
            $total_expense += $stat->expense;
        }
        $net_profit = $total_income - $total_expense;

        // 3. Ambil rincian transaksi terbesar
        $top_expenses = $this->db->get_results($this->db->prepare(
            "SELECT category, SUM(amount) as total 
             FROM {$this->db->prefix()}travel_finance 
             WHERE type = 'expense' AND transaction_date BETWEEN %s AND %s 
             GROUP BY category ORDER BY total DESC LIMIT 5",
            $start_date, $end_date
        ));

        View::render('admin/reports/financial-report', [
            'title' => 'Laporan Keuangan',
            'active_tab' => 'financial',
            'start_date' => $start_date,
            'end_date' => $end_date,
            'daily_stats' => $daily_stats,
            'total_income' => $total_income,
            'total_expense' => $total_expense,
            'net_profit' => $net_profit,
            'top_expenses' => $top_expenses
        ]);
    }

    /**
     * Laporan Penjualan (Booking & Jamaah)
     */
    private function salesReport($start_date, $end_date)
    {
        // 1. Statistik Booking per Paket
        $query_packages = "SELECT p.name, COUNT(b.id) as total_bookings, SUM(b.total_price) as total_revenue
                           FROM {$this->db->prefix()}travel_bookings b
                           JOIN {$this->db->prefix()}travel_packages p ON b.package_id = p.id
                           WHERE b.created_at BETWEEN %s AND %s AND b.status != 'cancelled'
                           GROUP BY p.id
                           ORDER BY total_revenue DESC";
        
        $package_stats = $this->db->get_results($this->db->prepare($query_packages, $start_date, $end_date));

        // 2. Tren Penjualan Harian
        $query_trend = "SELECT DATE(created_at) as date, COUNT(*) as count, SUM(total_price) as revenue
                        FROM {$this->db->prefix()}travel_bookings
                        WHERE created_at BETWEEN %s AND %s AND status != 'cancelled'
                        GROUP BY DATE(created_at)";
        
        $sales_trend = $this->db->get_results($this->db->prepare($query_trend, $start_date, $end_date));

        // 3. Status Pembayaran
        $payment_status = $this->db->get_results($this->db->prepare(
            "SELECT payment_status, COUNT(*) as count 
             FROM {$this->db->prefix()}travel_bookings 
             WHERE created_at BETWEEN %s AND %s 
             GROUP BY payment_status",
            $start_date, $end_date
        ));

        // Render view (menggunakan template generic report jika sales report spesifik belum ada)
        View::render('admin/reports/sales-report', [
            'title' => 'Laporan Penjualan',
            'active_tab' => 'sales',
            'start_date' => $start_date,
            'end_date' => $end_date,
            'package_stats' => $package_stats,
            'sales_trend' => $sales_trend,
            'payment_status' => $payment_status
        ]);
    }

    /**
     * Laporan Manifest (Keberangkatan)
     */
    private function manifestReport($start_date, $end_date)
    {
        // Menampilkan keberangkatan dalam rentang waktu yang dipilih
        $query = "SELECT p.*, 
                  (SELECT COUNT(*) FROM {$this->db->prefix()}travel_bookings b WHERE b.package_id = p.id AND b.status != 'cancelled') as total_pax
                  FROM {$this->db->prefix()}travel_packages p
                  WHERE p.departure_date BETWEEN %s AND %s
                  ORDER BY p.departure_date ASC";

        $departures = $this->db->get_results($this->db->prepare($query, $start_date, $end_date));

        View::render('admin/reports/manifest-report', [
            'title' => 'Laporan Keberangkatan (Manifest)',
            'active_tab' => 'manifest',
            'start_date' => $start_date,
            'end_date' => $end_date,
            'departures' => $departures
        ]);
    }

    /**
     * Laporan Operasional (Visa & Handling)
     */
    private function operationalReport($start_date, $end_date)
    {
        // Contoh: Memantau status visa jamaah yang akan berangkat
        // Asumsi ada tabel atau meta data untuk status visa
        
        // Query placeholder: Mengambil booking yang berangkat dalam rentang waktu
        $query = "SELECT b.id, u.display_name, p.name as package_name, p.departure_date,
                  b.status as booking_status
                  FROM {$this->db->prefix()}travel_bookings b
                  JOIN {$this->db->prefix()}users u ON b.user_id = u.ID
                  JOIN {$this->db->prefix()}travel_packages p ON b.package_id = p.id
                  WHERE p.departure_date BETWEEN %s AND %s AND b.status = 'confirmed'
                  ORDER BY p.departure_date ASC";

        $operational_data = $this->db->get_results($this->db->prepare($query, $start_date, $end_date));

        View::render('admin/reports/operational-report', [
            'title' => 'Laporan Operasional',
            'active_tab' => 'operational',
            'start_date' => $start_date,
            'end_date' => $end_date,
            'data' => $operational_data
        ]);
    }
    
    /**
     * Export ke PDF/Excel (Placeholder logic)
     */
    public function export()
    {
        $type = $_GET['type'] ?? 'financial';
        // Logika untuk generate CSV atau PDF menggunakan library PDFGenerator
        // ...
        
        header("Content-type: text/csv");
        header("Content-Disposition: attachment; filename=report_{$type}.csv");
        echo "Date,Type,Amount\n";
        // Loop data dan echo row
        exit;
    }
}