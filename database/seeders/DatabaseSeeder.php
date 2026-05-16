<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Store;
use App\Models\User;
use App\Models\Position;
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
            // NVBH_FT — Full-time, HĐ=CT. Spec 5.3: <90%=0% (no row), 90%+ mới có rate
            ['pos' => 'NVBH_FT', 'ct' => 'CT', 'min' =>  90, 'max' => 100,  'rate' => 2.2],
            ['pos' => 'NVBH_FT', 'ct' => 'CT', 'min' => 100, 'max' => 110,  'rate' => 2.5],
            ['pos' => 'NVBH_FT', 'ct' => 'CT', 'min' => 110, 'max' => 120,  'rate' => 2.8],
            ['pos' => 'NVBH_FT', 'ct' => 'CT', 'min' => 120, 'max' => null, 'rate' => 3.2],
            // NVBH_PT — Part-time, HĐ=TV. Spec 5.3: <90%=0%
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

            // 3. Tạo đội ngũ nhân sự chuẩn cho mỗi store (8 người)
            $staffStructure = [
                ['username' => 'qlch', 'name' => 'Quản Lý', 'pos' => 'QLCH', 'role' => 'store_manager'],
                ['username' => 'chp', 'name' => 'Phó Quản Lý', 'pos' => 'CHP', 'role' => 'staff'],
                ['username' => 'ft1', 'name' => 'Bán hàng FT 1', 'pos' => 'NVBH_FT', 'role' => 'staff'],
                ['username' => 'ft2', 'name' => 'Bán hàng FT 2', 'pos' => 'NVBH_FT', 'role' => 'staff'],
                ['username' => 'pt1', 'name' => 'Bán hàng PT 1', 'pos' => 'NVBH_PT', 'role' => 'staff'],
                ['username' => 'tn1', 'name' => 'Thu Ngân 1', 'pos' => 'NVTN', 'role' => 'staff'],
                ['username' => 'kho1', 'name' => 'Kho 1', 'pos' => 'NVK', 'role' => 'staff'],
                ['username' => 'bv1', 'name' => 'Bảo Vệ 1', 'pos' => 'NVBV', 'role' => 'staff'],
            ];

            // hourly_rate theo chức vụ
            $hourlyRates = [
                'QLCH' => 0, 'CHP' => 50000, 'NVBH_FT' => 35000,
                'NVBH_PT' => 25000, 'NVTN' => 30000, 'NVK' => 28000, 'NVBV' => 25000,
            ];
            // Hợp đồng: NVBH_PT = TV, còn lại = CT
            $contractTypes = [
                'NVBH_PT' => 'TV',
            ];

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
            'username' => 'admin',
            'password' => Hash::make('password'),
            'full_name' => 'Admin Tổng',
            'role' => 'admin',
        ]);
    }
}
