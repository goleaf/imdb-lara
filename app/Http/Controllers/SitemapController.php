<?php

namespace App\Http\Controllers;

use App\Actions\Seo\GetSitemapDataAction;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(GetSitemapDataAction $getSitemapData): Response
    {
        return response()
            ->view('seo.sitemap', $getSitemapData->handle())
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
