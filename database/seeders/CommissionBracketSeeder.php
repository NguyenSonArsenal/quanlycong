<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CommissionBracketSeeder extends Seeder
{
    public function run()
    {
        // Xóa sạch brackets cũ của NVBH_FT và NVBH_PT để ghi đè mới không bị trùng
        DB::table('commission_brackets')->whereIn('position_code', ['NVBH_FT', 'NVBH_PT'])->delete();

        $baseRates = [
            // NVBH_FT (Chính thức - CT)
            ['position_code' => 'NVBH_FT', 'contract_type' => 'CT', 'min_kpi' => 0,   'max_kpi' => 90,   'commission_rate' => 0.0],
            ['position_code' => 'NVBH_FT', 'contract_type' => 'CT', 'min_kpi' => 90,  'max_kpi' => 100,  'commission_rate' => 2.2],
            ['position_code' => 'NVBH_FT', 'contract_type' => 'CT', 'min_kpi' => 100, 'max_kpi' => 110,  'commission_rate' => 2.5],
            ['position_code' => 'NVBH_FT', 'contract_type' => 'CT', 'min_kpi' => 110, 'max_kpi' => 120,  'commission_rate' => 2.8],
            ['position_code' => 'NVBH_FT', 'contract_type' => 'CT', 'min_kpi' => 120, 'max_kpi' => null, 'commission_rate' => 3.2],

            // NVBH_PT (Thời vụ - TV)
            ['position_code' => 'NVBH_PT', 'contract_type' => 'TV', 'min_kpi' => 0,   'max_kpi' => 90,   'commission_rate' => 0.0],
            ['position_code' => 'NVBH_PT', 'contract_type' => 'TV', 'min_kpi' => 90,  'max_kpi' => 100,  'commission_rate' => 0.6],
            ['position_code' => 'NVBH_PT', 'contract_type' => 'TV', 'min_kpi' => 100, 'max_kpi' => 110,  'commission_rate' => 0.8],
            ['position_code' => 'NVBH_PT', 'contract_type' => 'TV', 'min_kpi' => 110, 'max_kpi' => 120,  'commission_rate' => 1.0],
            ['position_code' => 'NVBH_PT', 'contract_type' => 'TV', 'min_kpi' => 120, 'max_kpi' => null, 'commission_rate' => 1.2],

            // Các chức danh khác (như CHP, NVTN)
            ['position_code' => 'CHP',     'contract_type' => 'CT', 'min_kpi' => 0,   'max_kpi' => 90,   'commission_rate' => 0.3],
            ['position_code' => 'CHP',     'contract_type' => 'CT', 'min_kpi' => 90,  'max_kpi' => 100,  'commission_rate' => 0.5],
            ['position_code' => 'CHP',     'contract_type' => 'CT', 'min_kpi' => 100, 'max_kpi' => null, 'commission_rate' => 0.7],
            ['position_code' => 'NVTN',    'contract_type' => 'CT', 'min_kpi' => 0,   'max_kpi' => 90,   'commission_rate' => 0.2],
            ['position_code' => 'NVTN',    'contract_type' => 'CT', 'min_kpi' => 90,  'max_kpi' => null, 'commission_rate' => 0.4],
        ];

        foreach ($baseRates as $row) {
            $exists = DB::table('commission_brackets')
                ->where('position_code', $row['position_code'])
                ->where('contract_type', $row['contract_type'])
                ->where('min_kpi', $row['min_kpi'])
                ->exists();

            if (!$exists) {
                DB::table('commission_brackets')->insert([
                    'position_code'   => $row['position_code'],
                    'contract_type'   => $row['contract_type'],
                    'min_kpi'         => $row['min_kpi'],
                    'max_kpi'         => $row['max_kpi'],
                    'commission_rate' => $row['commission_rate'],
                    'effective_from'  => '2026-01-01',
                    'effective_to'    => null,
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);
                echo "Added: {$row['position_code']} ({$row['contract_type']}) min={$row['min_kpi']}%\n";
            } else {
                echo "Skip (exists): {$row['position_code']} ({$row['contract_type']}) min={$row['min_kpi']}%\n";
            }
        }
    }
}
