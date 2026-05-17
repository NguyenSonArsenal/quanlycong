<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Store;

class StoreSeeder extends Seeder
{
    public function run()
    {
        $stores = [
            [
                'name' => 'KRIK 344 Cầu Giấy',
                'code' => 'K01',
                'area_id' => 'HN01',
            ],
            [
                'name' => 'KRIK 280 Nguyễn Trãi',
                'code' => 'K02',
                'area_id' => 'HN01',
            ],
            [
                'name' => 'KRIK 92 Chùa Bộc',
                'code' => 'K03',
                'area_id' => 'HN02',
            ],
            [
                'name' => 'KRIK 307H Bạch Mai',
                'code' => 'K04',
                'area_id' => 'HN02',
            ],
            [
                'name' => 'KRIK 189 Phố Nhổn',
                'code' => 'K05',
                'area_id' => 'HN02',
            ],
            [
                'name' => 'KRIK Vincom Ocean Park',
                'code' => 'K06',
                'area_id' => 'HN03',
            ],
            [
                'name' => 'KRIK Vincom Smart Tây Mỗ',
                'code' => 'K07',
                'area_id' => 'HN01',
            ],
            [
                'name' => 'KRIK 100 Quang Trung',
                'code' => 'K08',
                'area_id' => 'HN01',
            ],
            [
                'name' => 'KRIK 192 Trần Duy Hưng',
                'code' => 'K09',
                'area_id' => 'HN01',
            ],
            [
                'name' => 'KRIK 232 Nguyễn Văn Cừ',
                'code' => 'K10',
                'area_id' => 'HN03',
            ],
            [
                'name' => 'KRIK 132 Cầu Giấy',
                'code' => 'K11',
                'area_id' => 'HN01',
            ],
            [
                'name' => 'KRIK 167 Chùa Bộc',
                'code' => 'K12',
                'area_id' => 'HN02',
            ],
            [
                'name' => 'KRIK 1239 Giải Phóng',
                'code' => 'K13',
                'area_id' => 'HN02',
            ],
            [
                'name' => 'KRIK 61 Hồ Tùng Mậu',
                'code' => 'K14',
                'area_id' => 'HN01',
            ],
        ];

        foreach ($stores as $store) {
            Store::create($store);
        }
    }
}
