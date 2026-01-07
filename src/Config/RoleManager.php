<?php
namespace UmhMgmt\Config;

class RoleManager {
    public static function init() {
        self::register_roles();
    }

    public static function register_roles() {
        $roles = [
            'umh_owner' => [
                'display_name' => 'Owner',
                'capabilities' => [
                    'read' => true,
                    'manage_options' => true,
                    'umh_view_audit_log' => true,
                    'umh_manage_all_branches' => true,
                ]
            ],
            'umh_manager' => [
                'display_name' => 'General Manager',
                'capabilities' => [
                    'read' => true,
                    'umh_manage_packages' => true,
                    'umh_view_finance_summary' => true,
                    'umh_manage_staff' => true,
                ]
            ],
            'umh_finance' => [
                'display_name' => 'Finance',
                'capabilities' => [
                    'read' => true,
                    'umh_manage_invoices' => true,
                    'umh_view_detailed_finance' => true,
                    'umh_approve_payments' => true,
                ]
            ],
            'umh_operational' => [
                'display_name' => 'Operational',
                'capabilities' => [
                    'read' => true,
                    'umh_manage_manifest' => true,
                    'umh_manage_departures' => true,
                    'umh_handle_handling' => true,
                ]
            ],
            'umh_agent' => [
                'display_name' => 'Sales/Agent',
                'capabilities' => [
                    'read' => true,
                    'umh_create_booking' => true,
                    'umh_view_own_commission' => true,
                ]
            ],
            'umh_it' => [
                'display_name' => 'IT Support',
                'capabilities' => [
                    'read' => true,
                    'umh_view_system_status' => true,
                    'umh_manage_settings' => true,
                    'umh_debug_logs' => true,
                ]
            ],
        ];

        foreach ($roles as $role_slug => $role_data) {
            add_role($role_slug, $role_data['display_name'], $role_data['capabilities']);
        }
    }

    public static function remove_roles() {
        $roles = ['umh_owner', 'umh_manager', 'umh_finance', 'umh_operational', 'umh_agent', 'umh_it'];
        foreach ($roles as $role) {
            remove_role($role);
        }
    }
}
