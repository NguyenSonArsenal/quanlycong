<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        DB::table('categories')->insert([
            [
                'name' => 'Giá vàng hôm nay',
                'slug' => 'gia-vang-hom-nay',
                'description' => 'Cập nhật giá vàng hôm nay mới nhất: SJC, DOJI, PNJ và giá vàng thế giới.',
                'meta_title' => 'Giá vàng hôm nay mới nhất | Cập nhật liên tục trong ngày',
                'meta_description' => 'Cập nhật giá vàng hôm nay SJC, DOJI, PNJ mới nhất theo thời gian thực. So sánh giá vàng trong nước và thế giới nhanh chóng và chính xác.',
                'thumbnail' => null,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Giá bạc hôm nay',
                'slug' => 'gia-bac-hom-nay',
                'description' => 'Cập nhật giá bạc hôm nay trong nước và thế giới mới nhất.',
                'meta_title' => 'Giá bạc hôm nay mới nhất | Giá bạc thế giới và Việt Nam',
                'meta_description' => 'Theo dõi giá bạc hôm nay mới nhất trong nước và quốc tế. Biến động giá bạc theo thời gian thực cập nhật liên tục.',
                'thumbnail' => null,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Phân tích giá vàng',
                'slug' => 'phan-tich-gia-vang',
                'description' => 'Nhận định và phân tích xu hướng giá vàng ngắn hạn và dài hạn.',
                'meta_title' => 'Phân tích giá vàng | Dự báo xu hướng giá vàng mới nhất',
                'meta_description' => 'Phân tích xu hướng giá vàng dựa trên FED, USD, lạm phát và tình hình kinh tế toàn cầu. Cập nhật nhận định thị trường vàng mới nhất.',
                'thumbnail' => null,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Phân tích giá bạc',
                'slug' => 'phan-tich-gia-bac',
                'description' => 'Phân tích xu hướng giá bạc trong nước và thế giới.',
                'meta_title' => 'Phân tích giá bạc | Dự báo xu hướng giá bạc mới nhất',
                'meta_description' => 'Nhận định xu hướng giá bạc dựa trên thị trường tài chính toàn cầu. Cập nhật phân tích giá bạc mới nhất cho nhà đầu tư.',
                'thumbnail' => null,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Tin tức thị trường',
                'slug' => 'tin-tuc-thi-truong',
                'description' => 'Tin tức thị trường vàng, bạc, USD và kinh tế tài chính thế giới.',
                'meta_title' => 'Tin tức thị trường vàng bạc | Cập nhật tài chính mới nhất',
                'meta_description' => 'Cập nhật tin tức mới nhất về thị trường vàng, bạc, USD, FED và kinh tế toàn cầu ảnh hưởng đến giá kim loại quý.',
                'thumbnail' => null,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Kiến thức đầu tư',
                'slug' => 'kien-thuc-dau-tu',
                'description' => 'Kiến thức đầu tư vàng bạc cho người mới và nhà đầu tư dài hạn.',
                'meta_title' => 'Kiến thức đầu tư vàng bạc | Hướng dẫn cho người mới',
                'meta_description' => 'Tổng hợp kiến thức đầu tư vàng bạc hiệu quả: khi nào nên mua, nên bán và cách quản lý rủi ro dành cho nhà đầu tư.',
                'thumbnail' => null,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ]);
    }
}
