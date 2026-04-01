<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Contracts\View\View;

class ReviewController extends Controller
{
    public function index(): View
    {
        return view('admin.reviews.index', [
            'reviews' => Review::query()
                ->select(['id', 'user_id', 'title_id', 'headline', 'status', 'published_at'])
                ->with([
                    'author:id,name,username',
                    'title:id,name,slug',
                ])
                ->latest()
                ->simplePaginate(20)
                ->withQueryString(),
        ]);
    }
}
