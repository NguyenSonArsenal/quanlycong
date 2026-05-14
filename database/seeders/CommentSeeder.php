<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\Post;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class CommentSeeder extends Seeder
{
    public function run()
    {
        $posts = Post::active()->get();

        if ($posts->isEmpty()) {
            $this->command->info('Không có post nào để seed comments.');
            return;
        }

        $names = [
            'Minh Tuấn', 'Ngọc Anh', 'Hoàng Nam', 'Thu Hà', 'Đức Trung',
            'Lan Phương', 'Quốc Bảo', 'Thanh Hương', 'Văn Đạt', 'Bích Ngọc',
            'Peter Nguyen', 'Anna Tran', 'David Le', 'Mai Chi', 'Hải Đăng',
            'Phương Linh', 'Trọng Nhân', 'Yến Nhi', 'Anh Khoa', 'Thùy Dung',
        ];

        $domains = ['gmail.com', 'yahoo.com', 'outlook.com', 'hotmail.com'];

        $templates = [
            'Bài viết rất hữu ích, cảm ơn admin!',
            'Mình đang tìm hiểu về chủ đề này, bài viết giúp mình rất nhiều.',
            'Có thể giải thích thêm phần này được không ạ?',
            'Thông tin rất chi tiết và dễ hiểu. Cảm ơn đã chia sẻ!',
            'Mình có câu hỏi: liệu xu hướng này có tiếp tục trong thời gian tới không?',
            'Admin cập nhật bài viết thường xuyên quá, rất thích!',
            'Mình đã theo dõi giá vàng bạc trên web lâu rồi, rất chất lượng.',
            'Bài phân tích rất sâu, mong admin viết thêm nhiều bài như này.',
            'Cho mình hỏi nguồn dữ liệu lấy từ đâu vậy ạ?',
            'Nội dung hay quá, mình sẽ chia sẻ cho bạn bè!',
            'Cảm ơn admin, bài viết rất bổ ích cho người mới tìm hiểu.',
            'Mình muốn biết thêm về cách đầu tư bạc, có bài nào khác không?',
            'Web cập nhật giá rất nhanh, rất tiện để theo dõi.',
            'Phần biểu đồ rất trực quan, dễ nhìn.',
            'Mình đã tham khảo và quyết định đầu tư, cảm ơn nhé!',
            'Bài viết rất chuyên sâu, keep up the good work!',
            'Có thể so sánh thêm với giá quốc tế được không admin?',
            'Mình mới bắt đầu tìm hiểu, bài viết này rất dễ hiểu cho newbie.',
            'Trang web thiết kế đẹp, nội dung chất lượng!',
            'Admin có thể viết bài về cách nhận biết bạc thật giả không?',
        ];

        $adminReplies = [
            'Cảm ơn bạn đã theo dõi! Mình sẽ cập nhật thêm nhiều bài viết hữu ích nhé.',
            'Dạ bạn có thể tham khảo thêm các bài viết liên quan trên web nhé!',
            'Cảm ơn bạn! Mình sẽ bổ sung thêm thông tin trong thời gian tới.',
            'Rất vui vì bài viết hữu ích cho bạn! Hãy theo dõi thêm nhé.',
            'Dạ dữ liệu được cập nhật real-time từ các nguồn uy tín nhé bạn.',
            'Cảm ơn góp ý! Mình sẽ có thêm bài phân tích sâu hơn.',
            'Bạn có thể xem phần So Sánh Giá trên menu để so sánh các thương hiệu nhé!',
            'Mình đã note lại, sẽ viết bài về chủ đề đó sớm nhất!',
            'Cảm ơn bạn đã ủng hộ! Hãy chia sẻ cho bạn bè cùng biết nhé.',
            'Dạ bạn có thể dùng công cụ Quy Đổi trên web để tính giá nhanh nhé!',
        ];

        $avatarGradients = [
            'linear-gradient(135deg,#6366f1,#4f46e5)',
            'linear-gradient(135deg,#22c97a,#059669)',
            'linear-gradient(135deg,#f59e0b,#d97706)',
            'linear-gradient(135deg,#ec4899,#be185d)',
            'linear-gradient(135deg,#14b8a6,#0d9488)',
            'linear-gradient(135deg,#ef4444,#dc2626)',
            'linear-gradient(135deg,#8b5cf6,#7c3aed)',
            'linear-gradient(135deg,#06b6d4,#0284c7)',
        ];

        foreach ($posts as $post) {
            $commentCount = rand(1, 10);
            $baseDate = $post->created_at->copy()->addDays(rand(1, 10));

            for ($i = 0; $i < $commentCount; $i++) {
                $name = $names[array_rand($names)];
                $emailName = strtolower(str_replace(' ', '.', $this->removeVietnamese($name)));
                $email = $emailName . rand(10, 999) . '@' . $domains[array_rand($domains)];

                $commentDate = $baseDate->copy()->addHours(rand($i * 12, ($i + 1) * 48));
                if ($commentDate->isFuture()) {
                    $commentDate = Carbon::now()->subHours(rand(1, 168));
                }

                $comment = Comment::create([
                    'post_id'    => $post->id,
                    'parent_id'  => null,
                    'name'       => $name,
                    'email'      => $email,
                    'body'       => $templates[array_rand($templates)],
                    'is_admin'   => false,
                    'status'     => Comment::STATUS_APPROVED,
                    'created_at' => $commentDate,
                    'updated_at' => $commentDate,
                ]);

                // 40% chance of admin reply
                if (rand(1, 100) <= 40) {
                    $replyDate = $commentDate->copy()->addMinutes(rand(10, 360));
                    if ($replyDate->isFuture()) {
                        $replyDate = Carbon::now()->subMinutes(rand(5, 60));
                    }

                    Comment::create([
                        'post_id'    => $post->id,
                        'parent_id'  => $comment->id,
                        'name'       => 'GIAVANGBAC ADMIN',
                        'email'      => 'admin@giavang.vn',
                        'body'       => $adminReplies[array_rand($adminReplies)],
                        'is_admin'   => true,
                        'status'     => Comment::STATUS_APPROVED,
                        'created_at' => $replyDate,
                        'updated_at' => $replyDate,
                    ]);
                }
            }
        }

        $total = Comment::count();
        $this->command->info("Đã seed {$total} comments cho " . $posts->count() . " posts.");
    }

    private function removeVietnamese($str)
    {
        $map = [
            'à'=>'a','á'=>'a','ả'=>'a','ã'=>'a','ạ'=>'a','ă'=>'a','ằ'=>'a','ắ'=>'a','ẳ'=>'a','ẵ'=>'a','ặ'=>'a',
            'â'=>'a','ầ'=>'a','ấ'=>'a','ẩ'=>'a','ẫ'=>'a','ậ'=>'a','đ'=>'d','è'=>'e','é'=>'e','ẻ'=>'e','ẽ'=>'e',
            'ẹ'=>'e','ê'=>'e','ề'=>'e','ế'=>'e','ể'=>'e','ễ'=>'e','ệ'=>'e','ì'=>'i','í'=>'i','ỉ'=>'i','ĩ'=>'i',
            'ị'=>'i','ò'=>'o','ó'=>'o','ỏ'=>'o','õ'=>'o','ọ'=>'o','ô'=>'o','ồ'=>'o','ố'=>'o','ổ'=>'o','ỗ'=>'o',
            'ộ'=>'o','ơ'=>'o','ờ'=>'o','ớ'=>'o','ở'=>'o','ỡ'=>'o','ợ'=>'o','ù'=>'u','ú'=>'u','ủ'=>'u','ũ'=>'u',
            'ụ'=>'u','ư'=>'u','ừ'=>'u','ứ'=>'u','ử'=>'u','ữ'=>'u','ự'=>'u','ỳ'=>'y','ý'=>'y','ỷ'=>'y','ỹ'=>'y','ỵ'=>'y',
            'À'=>'A','Á'=>'A','Ả'=>'A','Ã'=>'A','Ạ'=>'A','Ă'=>'A','Ằ'=>'A','Ắ'=>'A','Ẳ'=>'A','Ẵ'=>'A','Ặ'=>'A',
            'Â'=>'A','Ầ'=>'A','Ấ'=>'A','Ẩ'=>'A','Ẫ'=>'A','Ậ'=>'A','Đ'=>'D','È'=>'E','É'=>'E','Ẻ'=>'E','Ẽ'=>'E',
            'Ẹ'=>'E','Ê'=>'E','Ề'=>'E','Ế'=>'E','Ể'=>'E','Ễ'=>'E','Ệ'=>'E',
        ];
        return strtr($str, $map);
    }
}
