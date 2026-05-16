<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CommissionBracketSeeder extends Seeder
{
    public function run()
    {
        // Thêm bracket 0-90% (base rate) cho các vị trí sales
        // Không xóa data cũ, chỉ insert nếu chưa có
        $baseRates = [
            ['position_code' => 'NVBH_FT', 'min_kpi' => 0, 'max_kpi' => 90, 'commission_rate' => 1.5],
            ['position_code' => 'NVBH_PT', 'min_kpi' => 0, 'max_kpi' => 90, 'commission_rate' => 0.4],
            ['position_code' => 'CHP',     'min_kpi' => 0, 'max_kpi' => 90, 'commission_rate' => 0.3],
            ['position_code' => 'CHP',     'min_kpi' => 90,'max_kpi' => 100,'commission_rate' => 0.5],
            ['position_code' => 'CHP',     'min_kpi' => 100,'max_kpi'=> null,'commission_rate'=> 0.7],
            ['position_code' => 'NVTN',    'min_kpi' => 0, 'max_kpi' => 90, 'commission_rate' => 0.2],
            ['position_code' => 'NVTN',    'min_kpi' => 90,'max_kpi' => null,'commission_rate'=> 0.4],
        ];

        foreach ($baseRates as $row) {
            // Chỉ insert nếu chưa tồn tại bracket này
            $exists = DB::table('commission_brackets')
                ->where('position_code', $row['position_code'])
                ->where('contract_type', 'CT')
                ->where('min_kpi', $row['min_kpi'])
                ->exists();

            if (!$exists) {
                DB::table('commission_brackets')->insert([
                    'position_code'   => $row['position_code'],
                    'contract_type'   => 'CT',
                    'min_kpi'         => $row['min_kpi'],
                    'max_kpi'         => $row['max_kpi'],
                    'commission_rate' => $row['commission_rate'],
                    'effective_from'  => '2026-01-01',
                    'effective_to'    => null,
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);
                echo "Added: {$row['position_code']} min={$row['min_kpi']}%\n";
            } else {
                echo "Skip (exists): {$row['position_code']} min={$row['min_kpi']}%\n";
            }
        }
    }
}
