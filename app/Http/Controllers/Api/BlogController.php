<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Blog;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
class BlogController extends Controller
{
    public function blogList()
    {
        $blogs = Cache::remember('api_blog_list', now()->addHours(24), function () {
            return Blog::where('status', 'published')
                ->select(
                    'id',
                    'title',
                    'slug',
                    'short_desc',
                    'main_image',
                    'page_image',
                    'published_at'
                )
                ->orderBy('published_at','desc')
                ->get()
                ->map(function ($blog) {
                    return [
                        'id' => $blog->id,
                        'title' => $blog->title,
                        'slug' => $blog->slug,
                        'short_desc' => $blog->short_desc ?: null,
                        'main_image' => $blog->main_image
                            ? asset('storage/images/blogs/main/'.$blog->main_image)
                            : null,
                        'published_at' => $blog->published_at
                        ? Carbon::parse($blog->published_at)->format('d M Y')
                        : null,
                    ];
                });
        });
        return response()->json([
            'status' => true,
            'message' => 'Blog list',
            'data' => $blogs
        ]);
    }
}
