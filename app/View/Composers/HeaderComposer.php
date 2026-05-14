<?php

namespace App\View\Composers;

use App\Models\Category;
use Illuminate\View\View;

class HeaderComposer
{
    public function compose(View $view): void
    {
        // Categories table chưa có parent_id / sort → lấy flat list
        $menuCategories = Category::query()
            ->where('status', 1)
            ->orderBy('name')
            ->get()
            ->each(function ($cat) {
                // Giả lập children rỗng để header template không bị lỗi
                $cat->setRelation('children', collect());
            });

        $view->with('menuCategories', $menuCategories);
    }
}
