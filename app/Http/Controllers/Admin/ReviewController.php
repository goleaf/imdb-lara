<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\BuildAdminReviewsIndexQueryAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexReviewsRequest;
use Illuminate\Contracts\View\View;

class ReviewController extends Controller
{
    public function index(
        IndexReviewsRequest $request,
        BuildAdminReviewsIndexQueryAction $buildAdminReviewsIndexQuery,
    ): View {
        return view('admin.reviews.index', [
            'reviews' => $buildAdminReviewsIndexQuery
                ->handle()
                ->simplePaginate(20)
                ->withQueryString(),
        ]);
    }
}
