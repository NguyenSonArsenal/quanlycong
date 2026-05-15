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
        // Admin
        User::create([
            'username' => 'admin',
            'password' => Hash::make('123456'),
            'full_name' => 'Nguyễn Văn Admin',
            'role' => 'admin',
        ]);

        $store1 = Store::where('code', 'K01')->first();
        $posManager = Position::where('code', 'QLCH')->first();
        $posStaff = Position::where('code', 'NVBH_FT')->first();

        // QLCH Store 1
        User::create([
            'username' => 'manager',
            'password' => Hash::make('123456'),
            'full_name' => 'Trần Thị Quản Lý',
            'role' => 'store_manager',
            'store_id' => $store1->id,
            'position_id' => $posManager->id,
            'contract_type' => 'CT',
            'hourly_rate' => 50000,
        ]);

        // Sales Staff Store 1
        User::create([
            'username' => 'staff1',
            'password' => Hash::make('123456'),
            'full_name' => 'Lê Văn Bán Hàng',
            'role' => 'staff',
            'store_id' => $store1->id,
            'position_id' => $posStaff->id,
            'contract_type' => 'CT',
            'hourly_rate' => 25000,
        ]);
    }
}
