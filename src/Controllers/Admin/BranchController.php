<?php
// File: BranchController.php
// Location: src/Controllers/Admin/BranchController.php

namespace UmhMgmt\Controllers\Admin;

use UmhMgmt\Utils\View;

class BranchController {

    public function __construct() {
        add_action('admin_menu', [$this, 'add_menu']);
        add_action('admin_post_umh_save_branch', [$this, 'handle_save']);
        add_action('admin_post_umh_delete_branch', [$this, 'handle_delete']);
    }

    public function add_menu() {
        add_submenu_page(
            'umh-master-data', // Induk menu: Master Data
            'Manajemen Cabang',
            'Kantor Cabang',
            'manage_options',
            'umh-branches',
            [$this, 'render_page']
        );
    }

    public function handle_save() {
        check_admin_referer('umh_branch_nonce');
        if (!current_user_can('manage_options')) wp_die('Unauthorized');

        global $wpdb;
        $table = $wpdb->prefix . 'umh_branches';

        $data = [
            'name' => sanitize_text_field($_POST['name']),
            'address' => sanitize_textarea_field($_POST['address']),
            'phone' => sanitize_text_field($_POST['phone']),
            // Future: Bisa tambah kolom 'head_user_id' untuk link ke akun Kepala Cabang
        ];

        if (empty($_POST['id'])) {
            $wpdb->insert($table, $data);
        } else {
            $wpdb->update($table, $data, ['id' => absint($_POST['id'])]);
        }

        wp_redirect(admin_url('admin.php?page=umh-branches&msg=saved'));
        exit;
    }

    public function handle_delete() {
        check_admin_referer('umh_branch_nonce');
        if (!current_user_can('manage_options')) wp_die('Unauthorized');

        global $wpdb;
        $id = absint($_GET['id']);
        
        // Cek Dependensi: Jangan hapus jika ada booking
        $count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}umh_bookings WHERE branch_id = %d", $id));
        
        if ($count > 0) {
            wp_die('Gagal menghapus: Cabang ini memiliki data booking aktif. Arsipkan saja.');
        }

        $wpdb->delete($wpdb->prefix . 'umh_branches', ['id' => $id]);
        wp_redirect(admin_url('admin.php?page=umh-branches&msg=deleted'));
        exit;
    }

    public function render_page() {
        global $wpdb;
        $branches = $wpdb->get_results("
            SELECT b.*, 
            (SELECT COUNT(*) FROM {$wpdb->prefix}umh_bookings WHERE branch_id = b.id) as total_bookings 
            FROM {$wpdb->prefix}umh_branches b 
            ORDER BY b.name ASC
        ");

        View::render('admin/branches/list', ['branches' => $branches]);
    }
}