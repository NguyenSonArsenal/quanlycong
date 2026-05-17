<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Store;
use App\Models\User;
use App\Models\Position;
use App\Models\Permission;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // 1. Seed các chức vụ (Positions) đúng chuẩn KRIK
        $positions = [
            ['code' => 'QLCH', 'name' => 'Quản lý cửa hàng', 'is_sales' => false, 'team_bonus_base' => 5000000],
            ['code' => 'CHP', 'name' => 'Phó quản lý', 'is_sales' => true, 'team_bonus_base' => 0],
            ['code' => 'NVBH_FT', 'name' => 'Nhân viên bán hàng Full-time', 'is_sales' => true, 'team_bonus_base' => 0],
            ['code' => 'NVBH_PT', 'name' => 'Nhân viên bán hàng Part-time', 'is_sales' => true, 'team_bonus_base' => 0],
            ['code' => 'NVTN', 'name' => 'Nhân viên thu ngân', 'is_sales' => true, 'team_bonus_base' => 0],
            ['code' => 'NVK', 'name' => 'Nhân viên kho', 'is_sales' => true, 'team_bonus_base' => 0],
            ['code' => 'NVBV', 'name' => 'Bảo vệ', 'is_sales' => false, 'team_bonus_base' => 0],
        ];

        $posMap = [];
        foreach ($positions as $p) {
            $createdPos = \App\Models\Position::create($p);
            $posMap[$p['code']] = $createdPos->id;
        }

        // Seed Commission Brackets (Bảng 5.3)
        $brackets = [
            ['pos' => 'NVBH_FT', 'ct' => 'CT', 'min' =>  90, 'max' => 100,  'rate' => 2.2],
            ['pos' => 'NVBH_FT', 'ct' => 'CT', 'min' => 100, 'max' => 110,  'rate' => 2.5],
            ['pos' => 'NVBH_FT', 'ct' => 'CT', 'min' => 110, 'max' => 120,  'rate' => 2.8],
            ['pos' => 'NVBH_FT', 'ct' => 'CT', 'min' => 120, 'max' => null, 'rate' => 3.2],
            ['pos' => 'NVBH_PT', 'ct' => 'TV', 'min' =>  90, 'max' => 100,  'rate' => 0.6],
            ['pos' => 'NVBH_PT', 'ct' => 'TV', 'min' => 100, 'max' => 110,  'rate' => 0.8],
            ['pos' => 'NVBH_PT', 'ct' => 'TV', 'min' => 110, 'max' => 120,  'rate' => 1.0],
            ['pos' => 'NVBH_PT', 'ct' => 'TV', 'min' => 120, 'max' => null, 'rate' => 1.2],
        ];

        foreach ($brackets as $b) {
            \DB::table('commission_brackets')->insert([
                'position_code'   => $b['pos'],
                'contract_type'   => $b['ct'],
                'min_kpi'         => $b['min'],
                'max_kpi'         => $b['max'],
                'commission_rate' => $b['rate'],
                'effective_from'  => '2026-01-01',
            ]);
        }

        // 2. Tạo 3 cửa hàng mẫu
        $stores = [
            ['code' => 'K01', 'name' => 'KRIK Thái Hà', 'address' => '123 Thái Hà, Hà Nội'],
            ['code' => 'K02', 'name' => 'KRIK Cầu Giấy', 'address' => '456 Cầu Giấy, Hà Nội'],
            ['code' => 'K03', 'name' => 'KRIK Chùa Bộc', 'address' => '789 Chùa Bộc, Hà Nội'],
        ];

        foreach ($stores as $sData) {
            $store = Store::create($sData);

            $staffStructure = [
                ['username' => 'qlch', 'name' => 'Quản Lý',        'pos' => 'QLCH',    'role' => 'store_manager'],
                ['username' => 'chp',  'name' => 'Phó Quản Lý',    'pos' => 'CHP',     'role' => 'staff'],
                ['username' => 'ft1',  'name' => 'Bán hàng FT 1',  'pos' => 'NVBH_FT', 'role' => 'staff'],
                ['username' => 'ft2',  'name' => 'Bán hàng FT 2',  'pos' => 'NVBH_FT', 'role' => 'staff'],
                ['username' => 'pt1',  'name' => 'Bán hàng PT 1',  'pos' => 'NVBH_PT', 'role' => 'staff'],
                ['username' => 'tn1',  'name' => 'Thu Ngân 1',      'pos' => 'NVTN',    'role' => 'staff'],
                ['username' => 'kho1', 'name' => 'Kho 1',           'pos' => 'NVK',     'role' => 'staff'],
                ['username' => 'bv1',  'name' => 'Bảo Vệ 1',        'pos' => 'NVBV',    'role' => 'staff'],
            ];

            $hourlyRates   = ['QLCH' => 0, 'CHP' => 50000, 'NVBH_FT' => 35000, 'NVBH_PT' => 25000, 'NVTN' => 30000, 'NVK' => 28000, 'NVBV' => 25000];
            $contractTypes = ['NVBH_PT' => 'TV'];

            foreach ($staffStructure as $staff) {
                User::create([
                    'username'      => strtolower($store->code) . '_' . $staff['username'],
                    'password'      => Hash::make('password'),
                    'full_name'     => $staff['name'] . ' - ' . $store->code,
                    'role'          => $staff['role'],
                    'store_id'      => $store->id,
                    'position_id'   => $posMap[$staff['pos']],
                    'hourly_rate'   => $hourlyRates[$staff['pos']] ?? 0,
                    'contract_type' => $contractTypes[$staff['pos']] ?? 'CT',
                ]);
            }
        }

        // 4. Tạo Admin hệ thống
        User::create([
            'username'  => 'admin',
            'password'  => Hash::make('password'),
            'full_name' => 'Admin Tổng',
            'role'      => 'admin',
        ]);

        // ── 5. Seed Roles (Groups) ───────────────────────────
        $defaultRoles = \App\Models\Role::defaultRoles();
        foreach ($defaultRoles as $r) {
            \App\Models\Role::firstOrCreate(['name' => $r['name']], $r);
        }

        // ── 6. Seed Permissions ──────────────────────────────
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
            \App\Models\Permission::firstOrCreate(['name' => $pd['name']], $pd);
        }

        // ── 7. Map permissions to roles ──────────────────────
        $permByName = \App\Models\Permission::all()->keyBy('name');
        
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

        foreach (\App\Models\Role::all() as $role) {
            $defaultPerms = $rolePermissionMap[$role->name] ?? [];
            $permIds = collect($defaultPerms)
                ->filter(fn($name) => isset($permByName[$name]))
                ->map(fn($name) => $permByName[$name]->id)
                ->values()->toArray();
            $role->permissions()->sync($permIds);
        }
    }
}
