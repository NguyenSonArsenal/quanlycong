<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // Tắt khóa ngoại để dọn dẹp dữ liệu cũ an toàn
        \DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Reset dữ liệu cũ để tránh trùng lặp
        \App\Models\Position::truncate();
        \DB::table('commission_brackets')->truncate();
        \App\Models\Store::truncate();
        \App\Models\User::truncate();
        \App\Models\Permission::truncate();
        \App\Models\Role::truncate();
        \DB::table('role_permissions')->truncate();

        // Gọi các seeder riêng biệt theo đúng thứ tự logic
        $this->call([
            PositionSeeder::class,
            CommissionBracketSeeder::class,
            StoreSeeder::class,
            PermissionSeeder::class,
            UserSeeder::class,
        ]);

        // Kích hoạt lại ràng buộc khóa ngoại
        \DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
