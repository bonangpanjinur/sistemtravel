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

    public function getLeads($limit = 10, $offset = 0)
    {
        return $this->db->get_results(
            $this->db->prepare("SELECT * FROM {$this->db->prefix()}travel_leads ORDER BY created_at DESC LIMIT %d OFFSET %d", $limit, $offset)
        );
    }

    public function getAllJemaah($limit = 20, $offset = 0)
    {
        // Fix: Changed 'u.registered' to 'u.user_registered'
        $query = "SELECT u.ID, u.user_email, u.display_name, m.meta_value as phone 
                FROM {$this->db->prefix()}users u 
                LEFT JOIN {$this->db->prefix()}usermeta m ON u.ID = m.user_id AND m.meta_key = 'phone_number'
                WHERE 1=1 ORDER BY u.user_registered DESC LIMIT %d OFFSET %d";
                
        return $this->db->get_results($this->db->prepare($query, $limit, $offset));
    }

    public function getJemaahStats()
    {
        return [
            'total_jemaah' => $this->db->get_var("SELECT COUNT(*) FROM {$this->db->prefix()}users"),
            'new_this_month' => 0 // Placeholder
        ];
    }
}