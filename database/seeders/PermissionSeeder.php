<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class PermissionSeeder extends Seeder
{
    public function run()
    {
        // 1. Seed Roles (Groups)
        $defaultRoles = Role::defaultRoles();
        foreach ($defaultRoles as $r) {
            Role::firstOrCreate(['name' => $r['name']], $r);
        }

        // 2. Seed Permissions
        $permissionDefs = [
            ['name' => 'manage_all_stores',  'title' => 'Quản lý toàn chuỗi',            'group' => 'stores',     'order' => 1],
            ['name' => 'manage_own_store',   'title' => 'Quản lý cửa hàng của mình',     'group' => 'stores',     'order' => 2],
            ['name' => 'lock_day',           'title' => 'Khoá ngày',                     'group' => 'attendance', 'order' => 1],
            ['name' => 'unlock_day',         'title' => 'Mở khoá ngày',                  'group' => 'attendance', 'order' => 2],
            ['name' => 'lock_month',         'title' => 'Khoá tháng',                    'group' => 'attendance', 'order' => 3],
            ['name' => 'unlock_month',       'title' => 'Mở khoá tháng',                 'group' => 'attendance', 'order' => 4],
            ['name' => 'bypass_locked_day',  'title' => 'Bypass khi ngày đã khoá',       'group' => 'attendance', 'order' => 5],
            ['name' => 'manage_staff',       'title' => 'Quản lý nhân sự',               'group' => 'staff',      'order' => 1],
            ['name' => 'view_payroll_all',   'title' => 'Xem lương toàn hệ thống',       'group' => 'payroll',    'order' => 1],
            ['name' => 'view_payroll_store', 'title' => 'Xem lương cửa hàng mình',       'group' => 'payroll',    'order' => 2],
            ['name' => 'config_kpi',         'title' => 'Cấu hình KPI',                  'group' => 'kpi',        'order' => 1],
            ['name' => 'manage_permissions', 'title' => 'Phân quyền người dùng',         'group' => 'admin',      'order' => 1],
        ];

        foreach ($permissionDefs as $pd) {
            Permission::firstOrCreate(['name' => $pd['name']], $pd);
        }

        // 3. Map default permissions to roles/groups
        $permByName = Permission::all()->keyBy('name');
        
        $rolePermissionMap = [
            'admin' => [
                'manage_all_stores', 'manage_own_store', 'lock_day', 'unlock_day', 
                'lock_month', 'unlock_month', 'bypass_locked_day', 'manage_staff', 
                'view_payroll_all', 'view_payroll_store', 'config_kpi', 'manage_permissions',
            ],
            'area_manager' => [
                'manage_all_stores', 'view_payroll_all', 'manage_staff',
            ],
            'QLCH' => [
                'manage_own_store', 'bypass_locked_day', 'view_payroll_store', 'manage_staff',
            ],
            'CHP' => [
                'manage_own_store', 'view_payroll_store',
            ],
            'NVBH_FT' => [],
            'NVBH_PT' => [],
            'NVTN' => [],
            'NVK' => [],
            'NVBV' => [],
        ];

        foreach (Role::all() as $role) {
            $defaultPerms = $rolePermissionMap[$role->name] ?? [];
            $permIds = collect($defaultPerms)
                ->filter(fn($name) => isset($permByName[$name]))
                ->map(fn($name) => $permByName[$name]->id)
                ->values()->toArray();
            $role->permissions()->sync($permIds);
        }
    }
}
