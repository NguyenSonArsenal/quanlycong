<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Position;

class PositionSeeder extends Seeder
{
    public function run()
    {
        $positions = [
            ['code' => 'QLCH', 'name' => 'Quản lý cửa hàng', 'is_sales' => false, 'team_bonus_base' => 5000000, 'default_hourly_rate' => 0, 'default_contract_type' => 'CT'],
            ['code' => 'CHP', 'name' => 'Phó quản lý', 'is_sales' => false, 'team_bonus_base' => 0, 'default_hourly_rate' => 50000, 'default_contract_type' => 'CT'],
            ['code' => 'NVBH_FT', 'name' => 'Nhân viên bán hàng Full-time', 'is_sales' => true, 'team_bonus_base' => 0, 'default_hourly_rate' => 35000, 'default_contract_type' => 'CT'],
            ['code' => 'NVBH_PT', 'name' => 'Nhân viên bán hàng Part-time', 'is_sales' => true, 'team_bonus_base' => 0, 'default_hourly_rate' => 25000, 'default_contract_type' => 'TV'],
            ['code' => 'NVTN', 'name' => 'Nhân viên thu ngân', 'is_sales' => false, 'team_bonus_base' => 0, 'default_hourly_rate' => 30000, 'default_contract_type' => 'CT'],
            ['code' => 'NVK', 'name' => 'Nhân viên kho', 'is_sales' => false, 'team_bonus_base' => 0, 'default_hourly_rate' => 28000, 'default_contract_type' => 'CT'],
            ['code' => 'NVBV', 'name' => 'Bảo vệ', 'is_sales' => false, 'team_bonus_base' => 0, 'default_hourly_rate' => 25000, 'default_contract_type' => 'CT'],
        ];

        foreach ($positions as $p) {
            Position::create($p);
        }
    }
}
