<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CommissionBracketSeeder extends Seeder
{
    public function run()
    {
        // Note: Truncation is handled centrally in DatabaseSeeder to avoid foreign key violations.

        $brackets = [];

        // --- QUÝ 2/2026 (Tháng 4, 5, 6: 2026-04-01 đến 2026-06-30) ---
        // Áp dụng đúng định mức theo đề bài:
        // NVBH_FT: <90% = 0%, 90-100% = 2.2%, 100-110% = 2.5%, 110-120% = 2.8%, >=120% = 3.2%
        // NVBH_PT: <90% = 0%, 90-100% = 0.6%, 100-110% = 0.8%, 110-120% = 1.0%, >=120% = 1.2%
        $q2Rates = [
            // NVBH_FT (Bán hàng Full-time - CT)
            ['position_code' => 'NVBH_FT', 'contract_type' => 'CT', 'min_kpi' => 0,   'max_kpi' => 90,   'commission_rate' => 0.0],
            ['position_code' => 'NVBH_FT', 'contract_type' => 'CT', 'min_kpi' => 90,  'max_kpi' => 100,  'commission_rate' => 2.2],
            ['position_code' => 'NVBH_FT', 'contract_type' => 'CT', 'min_kpi' => 100, 'max_kpi' => 110,  'commission_rate' => 2.5],
            ['position_code' => 'NVBH_FT', 'contract_type' => 'CT', 'min_kpi' => 110, 'max_kpi' => 120,  'commission_rate' => 2.8],
            ['position_code' => 'NVBH_FT', 'contract_type' => 'CT', 'min_kpi' => 120, 'max_kpi' => null, 'commission_rate' => 3.2],
            
            // NVBH_PT (Bán hàng Part-time - TV)
            ['position_code' => 'NVBH_PT', 'contract_type' => 'TV', 'min_kpi' => 0,   'max_kpi' => 90,   'commission_rate' => 0.0],
            ['position_code' => 'NVBH_PT', 'contract_type' => 'TV', 'min_kpi' => 90,  'max_kpi' => 100,  'commission_rate' => 0.6],
            ['position_code' => 'NVBH_PT', 'contract_type' => 'TV', 'min_kpi' => 100, 'max_kpi' => 110,  'commission_rate' => 0.8],
            ['position_code' => 'NVBH_PT', 'contract_type' => 'TV', 'min_kpi' => 110, 'max_kpi' => 120,  'commission_rate' => 1.0],
            ['position_code' => 'NVBH_PT', 'contract_type' => 'TV', 'min_kpi' => 120, 'max_kpi' => null, 'commission_rate' => 1.2],
        ];
        foreach ($q2Rates as $row) {
            $brackets[] = array_merge($row, ['effective_from' => '2026-04-01', 'effective_to' => '2026-06-30']);
        }

        // --- QUÝ 3/2026 TRỞ ĐI (Tháng 7, 8, 9...: 2026-07-01 trở đi) ---
        // Giữ nguyên đúng định mức hoa hồng chuẩn để kiểm thử ổn định:
        $q3Rates = [
            // NVBH_FT (Bán hàng Full-time - CT)
            ['position_code' => 'NVBH_FT', 'contract_type' => 'CT', 'min_kpi' => 0,   'max_kpi' => 90,   'commission_rate' => 0.0],
            ['position_code' => 'NVBH_FT', 'contract_type' => 'CT', 'min_kpi' => 90,  'max_kpi' => 100,  'commission_rate' => 2.2],
            ['position_code' => 'NVBH_FT', 'contract_type' => 'CT', 'min_kpi' => 100, 'max_kpi' => 110,  'commission_rate' => 2.5],
            ['position_code' => 'NVBH_FT', 'contract_type' => 'CT', 'min_kpi' => 110, 'max_kpi' => 120,  'commission_rate' => 2.8],
            ['position_code' => 'NVBH_FT', 'contract_type' => 'CT', 'min_kpi' => 120, 'max_kpi' => null, 'commission_rate' => 3.2],
            
            // NVBH_PT (Bán hàng Part-time - TV)
            ['position_code' => 'NVBH_PT', 'contract_type' => 'TV', 'min_kpi' => 0,   'max_kpi' => 90,   'commission_rate' => 0.0],
            ['position_code' => 'NVBH_PT', 'contract_type' => 'TV', 'min_kpi' => 90,  'max_kpi' => 100,  'commission_rate' => 0.6],
            ['position_code' => 'NVBH_PT', 'contract_type' => 'TV', 'min_kpi' => 100, 'max_kpi' => 110,  'commission_rate' => 0.8],
            ['position_code' => 'NVBH_PT', 'contract_type' => 'TV', 'min_kpi' => 110, 'max_kpi' => 120,  'commission_rate' => 1.0],
            ['position_code' => 'NVBH_PT', 'contract_type' => 'TV', 'min_kpi' => 120, 'max_kpi' => null, 'commission_rate' => 1.2],
        ];
        foreach ($q3Rates as $row) {
            $brackets[] = array_merge($row, ['effective_from' => '2026-07-01', 'effective_to' => null]);
        }

        // Insert all rows
        foreach ($brackets as $b) {
            DB::table('commission_brackets')->insert(array_merge($b, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}
