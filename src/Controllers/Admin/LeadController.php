<?php
// Path: src/Controllers/Admin/LeadController.php

namespace App\Controllers\Admin;

use App\Repositories\LeadRepository;
use App\Repositories\BookingRepository;
use App\Utils\View;
use App\Utils\Validator;

class LeadController {
    private $leadRepo;
    private $bookingRepo;

    public function __construct(LeadRepository $leadRepo, BookingRepository $bookingRepo) {
        $this->leadRepo = $leadRepo;
        $this->bookingRepo = $bookingRepo;
    }

    public function index() {
        $status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
        $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
        
        $leads = $this->leadRepo->getAll(20, 0, ['status' => $status, 'search' => $search]);
        $stats = $this->leadRepo->getStats();

        echo View::render('admin/leads/index', [
            'leads' => $leads,
            'stats' => $stats,
            'current_status' => $status
        ]);
    }

    public function create() {
        echo View::render('admin/leads/form', ['lead' => null]);
    }

    public function edit() {
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $lead = $this->leadRepo->getById($id);
        
        if (!$lead) {
            echo '<div class="notice notice-error"><p>Data tidak ditemukan.</p></div>';
            return;
        }

        echo View::render('admin/leads/form', ['lead' => $lead]);
    }

    public function save() {
        if (!isset($_POST['umh_lead_nonce']) || !wp_verify_nonce($_POST['umh_lead_nonce'], 'save_lead')) {
            wp_die('Security check failed');
        }

        $validator = Validator::make($_POST)->rules([
            'name' => 'required',
            'phone' => 'required|numeric'
        ]);

        if ($validator->fails()) {
            wp_die('Validasi gagal: ' . $validator->getFirstError());
        }

        $data = [
            'name' => sanitize_text_field($_POST['name']),
            'phone' => sanitize_text_field($_POST['phone']),
            'email' => sanitize_email($_POST['email']),
            'source' => sanitize_text_field($_POST['source']),
            'status' => sanitize_text_field($_POST['status']),
            'interested_in' => sanitize_text_field($_POST['interested_in']),
            'notes' => sanitize_textarea_field($_POST['notes']),
            'follow_up_date' => sanitize_text_field($_POST['follow_up_date']) ?: null
        ];

        if (!empty($_POST['id'])) {
            $this->leadRepo->update(intval($_POST['id']), $data);
        } else {
            $this->leadRepo->create($data);
        }

        echo '<script>window.location.href="admin.php?page=umroh-leads&message=saved";</script>';
        exit;
    }

    public function delete() {
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'delete_lead')) {
            wp_die('Security check failed');
        }

        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $this->leadRepo->delete($id);
        
        echo '<script>window.location.href="admin.php?page=umroh-leads&message=deleted";</script>';
        exit;
    }

    /**
     * FITUR SPESIAL: Konversi Lead jadi Booking Real
     */
    public function convertToBooking() {
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'convert_lead')) {
            wp_die('Security check failed');
        }

        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $lead = $this->leadRepo->getById($id);

        if (!$lead) wp_die('Lead not found');

        // 1. Cek atau Buat User WordPress
        $user = get_user_by('email', $lead->email);
        
        if (!$user && !empty($lead->email)) {
            // Buat User Baru
            $password = wp_generate_password(10, false);
            $userId = wp_insert_user([
                'user_login' => $lead->email,
                'user_email' => $lead->email,
                'display_name' => $lead->name,
                'user_pass' => $password,
                'role' => 'umh_jemaah'
            ]);
            update_user_meta($userId, 'phone_number', $lead->phone);
        } elseif ($user) {
            $userId = $user->ID;
        } else {
            // Kasus langka: lead tanpa email
            wp_die('Lead harus memiliki email untuk dikonversi menjadi User.');
        }

        // 2. Update Status Lead jadi 'Closing'
        $this->leadRepo->update($id, ['status' => 'closing']);

        // 3. Redirect ke Halaman Tambah Booking (Admin) dengan data pre-filled
        // Kita asumsikan ada route 'umroh-bookings-add' yang menerima parameter user_id
        $url = admin_url('admin.php?page=umroh-bookings&action=add_new&user_id=' . $userId . '&ref_lead=' . $id);
        
        echo '<script>window.location.href="' . $url . '";</script>';
        exit;
    }
}