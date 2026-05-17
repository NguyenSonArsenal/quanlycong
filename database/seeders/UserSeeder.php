<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Store;
use App\Models\Position;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run()
    {
        // Truncate to reset auto-increment and prevent duplicates
        User::truncate();

        // 1. Tạo Admin hệ thống
        User::create([
            'username'  => 'admin',
            'password'  => Hash::make('password'),
            'full_name' => 'Admin Tổng',
            'role'      => 'admin',
        ]);

        // Lấy danh sách các chức vụ
        $posQLCH  = Position::where('code', 'QLCH')->first();
        $posCHP   = Position::where('code', 'CHP')->first();
        $posFT    = Position::where('code', 'NVBH_FT')->first();
        $posPT    = Position::where('code', 'NVBH_PT')->first();
        $posTN    = Position::where('code', 'NVTN')->first();
        $posK     = Position::where('code', 'NVK')->first();
        $posBV    = Position::where('code', 'NVBV')->first();

        // Danh sách kho từ để sinh tên tiếng Việt ngẫu nhiên siêu thật
        $ho = ['Nguyễn', 'Trần', 'Lê', 'Phạm', 'Hoàng', 'Phan', 'Vũ', 'Võ', 'Đặng', 'Bùi', 'Đỗ', 'Hồ', 'Ngô', 'Dương', 'Lý'];
        $dem = ['Văn', 'Thị', 'Hữu', 'Minh', 'Hồng', 'Thanh', 'Quang', 'Duy', 'Ngọc', 'Anh', 'Đức', 'Hải', 'Thành', 'Thu', 'Kim'];
        $ten = ['Nam', 'Trang', 'Linh', 'Thảo', 'Hải', 'Đạt', 'Thư', 'Tuấn', 'Huy', 'Hoàng', 'Phương', 'Mai', 'Lan', 'Hùng', 'Phong', 'Khánh', 'Giang', 'Vy', 'Sơn', 'Lâm', 'Hà', 'Minh', 'Quân', 'Tú', 'Yến'];

        $generateVietnameseName = function() use ($ho, $dem, $ten) {
            $h = $ho[array_rand($ho)];
            $d = $dem[array_rand($dem)];
            $t = $ten[array_rand($ten)];
            return "$h $d $t";
        };

        // Lấy tất cả 14 cửa hàng KRIK đã seed ở bước trước
        $stores = Store::all();

        foreach ($stores as $store) {
            $storeCodeLower = strtolower($store->code);

            // A. TẠO CÁC VỊ TRÍ CỐ ĐỊNH CHO MỖI CỬA HÀNG (1 QL, 1 Phó QL, 1 Thu ngân, 1 Kho, 1 Bảo vệ)
            
            // 1. Quản lý cửa hàng (QLCH - role: store_manager)
            User::create([
                'username'      => "{$storeCodeLower}_qlch",
                'password'      => Hash::make('password'),
                'full_name'     => $generateVietnameseName() . ' - QL ' . $store->code,
                'role'          => 'store_manager',
                'store_id'      => $store->id,
                'position_id'   => $posQLCH->id,
                'hourly_rate'   => $posQLCH->default_hourly_rate,
                'contract_type' => $posQLCH->default_contract_type,
            ]);

            // 2. Phó quản lý (CHP - role: staff)
            User::create([
                'username'      => "{$storeCodeLower}_chp",
                'password'      => Hash::make('password'),
                'full_name'     => $generateVietnameseName() . ' - CHP ' . $store->code,
                'role'          => 'staff',
                'store_id'      => $store->id,
                'position_id'   => $posCHP->id,
                'hourly_rate'   => $posCHP->default_hourly_rate,
                'contract_type' => $posCHP->default_contract_type,
            ]);

            // 3. Nhân viên Thu ngân (NVTN - role: staff)
            User::create([
                'username'      => "{$storeCodeLower}_nvtn",
                'password'      => Hash::make('password'),
                'full_name'     => $generateVietnameseName() . ' - TN ' . $store->code,
                'role'          => 'staff',
                'store_id'      => $store->id,
                'position_id'   => $posTN->id,
                'hourly_rate'   => $posTN->default_hourly_rate,
                'contract_type' => $posTN->default_contract_type,
            ]);

            // 4. Nhân viên Kho (NVK - role: staff)
            User::create([
                'username'      => "{$storeCodeLower}_nvk",
                'password'      => Hash::make('password'),
                'full_name'     => $generateVietnameseName() . ' - Kho ' . $store->code,
                'role'          => 'staff',
                'store_id'      => $store->id,
                'position_id'   => $posK->id,
                'hourly_rate'   => $posK->default_hourly_rate,
                'contract_type' => $posK->default_contract_type,
            ]);

            // 5. Nhân viên Bảo vệ (NVBV - role: staff)
            User::create([
                'username'      => "{$storeCodeLower}_nvbv",
                'password'      => Hash::make('password'),
                'full_name'     => $generateVietnameseName() . ' - BV ' . $store->code,
                'role'          => 'staff',
                'store_id'      => $store->id,
                'position_id'   => $posBV->id,
                'hourly_rate'   => $posBV->default_hourly_rate,
                'contract_type' => $posBV->default_contract_type,
            ]);

            // B. TẠO NGẪU NHIÊN TỪ 10 ĐẾN 15 SALES (NVBH_FT HOẶC NVBH_PT)
            $totalSales = rand(10, 15);
            for ($i = 1; $i <= $totalSales; $i++) {
                // Phân bổ ngẫu nhiên tỉ lệ 60/40 giữa Full-time và Part-time
                $isFulltime = (rand(1, 100) <= 60);
                $position   = $isFulltime ? $posFT : $posPT;
                $posCodeStr = $isFulltime ? 'ft' : 'pt';

                User::create([
                    'username'      => "{$storeCodeLower}_sales_{$posCodeStr}{$i}",
                    'password'      => Hash::make('password'),
                    'full_name'     => $generateVietnameseName(),
                    'role'          => 'staff',
                    'store_id'      => $store->id,
                    'position_id'   => $position->id,
                    'hourly_rate'   => $position->default_hourly_rate,
                    'contract_type' => $position->default_contract_type,
                ]);
            }
        }
    }
}
