<?php

namespace App\Repositories;

use App\Interfaces\DatabaseInterface;

class CRMRepository
{
    protected $db;

    public function __construct(DatabaseInterface $db)
    {
        $this->db = $db;
    }

    /**
     * Mengambil daftar leads dengan pagination dan filter status
     */
    public function getLeads($limit = 10, $offset = 0, $status = null)
    {
        $query = "SELECT * FROM {$this->db->prefix()}travel_leads";
        $params = [];

        if ($status) {
            $query .= " WHERE status = %s";
            $params[] = $status;
        }

        $query .= " ORDER BY created_at DESC LIMIT %d OFFSET %d";
        $params[] = $limit;
        $params[] = $offset;

        // Menggunakan variadic argument unpacking (...) untuk prepare
        return $this->db->get_results($this->db->prepare($query, ...$params));
    }

    /**
     * Menghitung total leads (opsional filter by status)
     */
    public function countLeads($status = null)
    {
        $query = "SELECT COUNT(*) FROM {$this->db->prefix()}travel_leads";
        $params = [];

        if ($status) {
            $query .= " WHERE status = %s";
            $params[] = $status;
            return $this->db->get_var($this->db->prepare($query, ...$params));
        }

        return $this->db->get_var($query);
    }

    /**
     * Mengambil detail satu lead berdasarkan ID
     */
    public function getLead($id)
    {
        return $this->db->get_row(
            $this->db->prepare("SELECT * FROM {$this->db->prefix()}travel_leads WHERE id = %d", $id)
        );
    }

    /**
     * Membuat lead baru
     */
    public function createLead($data)
    {
        $table = $this->db->prefix() . 'travel_leads';
        
        $insert_data = [
            'name' => $data['name'],
            'email' => $data['email'] ?? '',
            'phone' => $data['phone'] ?? '',
            'source' => $data['source'] ?? 'manual', // misal: website, agent, referal
            'status' => 'new',
            'notes' => $data['notes'] ?? '',
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ];

        $this->db->insert($table, $insert_data);
        return $this->db->last_insert_id();
    }

    /**
     * Mengupdate data lead
     */
    public function updateLead($id, $data)
    {
        $table = $this->db->prefix() . 'travel_leads';
        $data['updated_at'] = current_time('mysql');
        
        return $this->db->update($table, $data, ['id' => $id]);
    }

    /**
     * Menghapus lead
     */
    public function deleteLead($id)
    {
        $table = $this->db->prefix() . 'travel_leads';
        return $this->db->delete($table, ['id' => $id]);
    }

    /**
     * Mengambil daftar semua Jemaah (User) dengan pagination dan pencarian
     */
    public function getAllJemaah($limit = 20, $offset = 0, $search = '')
    {
        // Fix: Changed 'u.registered' to 'u.user_registered'
        // Join ke usermeta untuk mengambil nomor telepon dan alamat
        $query = "SELECT u.ID, u.user_email, u.display_name, u.user_registered,
                  m_phone.meta_value as phone, m_address.meta_value as address
                  FROM {$this->db->prefix()}users u 
                  LEFT JOIN {$this->db->prefix()}usermeta m_phone ON u.ID = m_phone.user_id AND m_phone.meta_key = 'phone_number'
                  LEFT JOIN {$this->db->prefix()}usermeta m_address ON u.ID = m_address.user_id AND m_address.meta_key = 'address'
                  WHERE 1=1";

        $params = [];

        // Tambahkan logika pencarian jika ada keyword
        if (!empty($search)) {
            $query .= " AND (u.display_name LIKE %s OR u.user_email LIKE %s)";
            $search_term = '%' . $search . '%';
            $params[] = $search_term;
            $params[] = $search_term;
        }

        $query .= " ORDER BY u.user_registered DESC LIMIT %d OFFSET %d";
        $params[] = $limit;
        $params[] = $offset;
                
        return $this->db->get_results($this->db->prepare($query, ...$params));
    }

    /**
     * Mengambil detail lengkap Jemaah beserta riwayat booking
     */
    public function getJemaahDetail($user_id)
    {
        // Menggunakan fungsi WP native untuk data user dasar
        $user = get_userdata($user_id);
        if (!$user) return null;

        // Mengambil meta data tambahan
        $phone = get_user_meta($user_id, 'phone_number', true);
        $address = get_user_meta($user_id, 'address', true);
        $passport = get_user_meta($user_id, 'passport_number', true);
        $nik = get_user_meta($user_id, 'nik', true);

        // Mengambil riwayat booking dari tabel custom travel_bookings
        $bookings_query = "SELECT b.*, p.name as package_name 
                           FROM {$this->db->prefix()}travel_bookings b 
                           LEFT JOIN {$this->db->prefix()}travel_packages p ON b.package_id = p.id
                           WHERE b.user_id = %d 
                           ORDER BY b.created_at DESC";
                           
        $bookings = $this->db->get_results($this->db->prepare($bookings_query, $user_id));

        return [
            'id' => $user->ID,
            'name' => $user->display_name,
            'email' => $user->user_email,
            'phone' => $phone,
            'address' => $address,
            'passport' => $passport,
            'nik' => $nik,
            'registered' => $user->user_registered,
            'bookings' => $bookings
        ];
    }

    /**
     * Mengambil statistik ringkas untuk Dashboard CRM
     */
    public function getJemaahStats()
    {
        // Hitung User yang mendaftar bulan ini
        $new_users_query = "SELECT COUNT(*) FROM {$this->db->prefix()}users 
                            WHERE MONTH(user_registered) = MONTH(CURRENT_DATE()) 
                            AND YEAR(user_registered) = YEAR(CURRENT_DATE())";

        return [
            'total_jemaah' => $this->db->get_var("SELECT COUNT(*) FROM {$this->db->prefix()}users"),
            'new_this_month' => $this->db->get_var($new_users_query),
            'total_leads' => $this->countLeads(),
            'converted_leads' => $this->countLeads('converted') // Lead yang berhasil jadi booking
        ];
    }

    /**
     * Mencatat interaksi dengan jemaah/lead (Log Activity)
     */
    public function logInteraction($entity_type, $entity_id, $type, $notes, $staff_id)
    {
        // Pastikan tabel log ada (biasanya travel_crm_logs)
        $table = $this->db->prefix() . 'travel_crm_logs';
        
        $data = [
            'entity_type' => $entity_type, // 'lead' atau 'user'
            'entity_id' => $entity_id,
            'interaction_type' => $type, // 'call', 'meeting', 'whatsapp', 'email'
            'notes' => $notes,
            'created_by' => $staff_id,
            'created_at' => current_time('mysql')
        ];

        return $this->db->insert($table, $data);
    }

    /**
     * Mengambil riwayat interaksi user/lead tertentu
     */
    public function getInteractions($entity_type, $entity_id)
    {
        $table = $this->db->prefix() . 'travel_crm_logs';
        
        // Kita join dengan tabel users untuk tahu siapa staff yang melakukan log
        $query = "SELECT l.*, u.display_name as staff_name 
                  FROM $table l 
                  LEFT JOIN {$this->db->prefix()}users u ON l.created_by = u.ID
                  WHERE l.entity_type = %s AND l.entity_id = %d 
                  ORDER BY l.created_at DESC";

        return $this->db->get_results($this->db->prepare($query, $entity_type, $entity_id));
    }
}