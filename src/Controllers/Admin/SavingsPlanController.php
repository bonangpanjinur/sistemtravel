<?php
namespace UmhMgmt\Controllers\Admin;

use UmhMgmt\Utils\View;

class SavingsPlanController {
    public function __construct() {
        add_action('admin_menu', [$this, 'add_menu_page']);
    }

    public function add_menu_page() {
        add_submenu_page(
            'umh-dashboard',
            'Tabungan Umroh',
            'Tabungan',
            'umh_manage_invoices',
            'umh-savings',
            [$this, 'render_list']
        );
    }

    public function render_list() {
        global $wpdb;
        $plans = $wpdb->get_results("SELECT s.*, u.display_name as customer_name 
            FROM {$wpdb->prefix}umh_savings_plans s
            LEFT JOIN {$wpdb->users} u ON s.customer_user_id = u.ID
            ORDER BY s.created_at DESC");

        View::render('admin/savings/plan-list', ['plans' => $plans]);
    }

    public function create_plan($data) {
        global $wpdb;
        
        $target = floatval($data['target_amount']);
        $tenor = intval($data['tenor_months']);
        $monthly = $target / $tenor;

        $wpdb->insert("{$wpdb->prefix}umh_savings_plans", [
            'customer_user_id' => $data['customer_id'],
            'target_amount' => $target,
            'tenor_months' => $tenor,
            'monthly_amount' => $monthly,
            'status' => 'active'
        ]);

        return $wpdb->insert_id;
    }
}
