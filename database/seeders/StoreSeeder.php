<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Store;

class StoreSeeder extends Seeder
{
    public function run()
    {
        $stores = [
            ['name' => 'Cửa hàng K01', 'code' => 'K01', 'area_id' => 'HN01'],
            ['name' => 'Cửa hàng K02', 'code' => 'K02', 'area_id' => 'HN01'],
            ['name' => 'Cửa hàng K03', 'code' => 'K03', 'area_id' => 'HN02'],
            ['name' => 'Cửa hàng K04', 'code' => 'K04', 'area_id' => 'HN02'],
            ['name' => 'Cửa hàng K05', 'code' => 'K05', 'area_id' => 'HN02'],
        ];

        foreach ($stores as $store) {
            Store::create($store);
        }
    }
}
