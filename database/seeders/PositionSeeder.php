<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Position;

class PositionSeeder extends Seeder
{
    public function run()
    {
        $positions = [
            ['name' => 'Quản lý cửa hàng', 'code' => 'QLCH', 'is_sales' => false, 'team_bonus_base' => 1000000],
            ['name' => 'Phó quản lý', 'code' => 'CHP', 'is_sales' => false, 'team_bonus_base' => 500000],
            ['name' => 'NV Bán hàng Full-time', 'code' => 'NVBH_FT', 'is_sales' => true, 'team_bonus_base' => 0],
            ['name' => 'NV Bán hàng Part-time', 'code' => 'NVBH_PT', 'is_sales' => true, 'team_bonus_base' => 0],
            ['name' => 'Nhân viên thu ngân', 'code' => 'NVTN', 'is_sales' => false, 'team_bonus_base' => 0],
            ['name' => 'Nhân viên kho', 'code' => 'NVK', 'is_sales' => false, 'team_bonus_base' => 0],
            ['name' => 'Bảo vệ', 'code' => 'NVBV', 'is_sales' => false, 'team_bonus_base' => 0],
        ];

        foreach ($positions as $pos) {
            Position::create($pos);
        }
    }
}
