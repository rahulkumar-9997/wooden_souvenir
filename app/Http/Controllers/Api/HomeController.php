<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Label;
use App\Models\Category;
use App\Models\Product;
use App\Models\Attribute_values;
use App\Models\Attribute;
use App\Models\Banner;
use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{
    public function newArrivals()
    {
        /*
        When product / label updated run this:
        Cache::forget('home_new_arrivals_products');
        Cache::forget('home_label_new_arrivals');
        Get label from cache (24 hours)
        */
        $label = Cache::remember('home_label_new_arrivals', now()->addHours(24), function () {
            return Label::where('slug', 'new-arrivals')
            ->where('status', 1)
            ->first();
        });
        if (!$label) {
            return response()->json([
                'status' => false,
                'message' => 'New Arrivals label not found',
                'data' => []
            ]);
        }
        $products = Cache::remember('home_new_arrivals_products', now()->addHours(24), function () use ($label) {
            return Product::where('product_status', 1)
                ->where('label_id', $label->id)
                ->with([
                    'category:id,title,slug',
                    'firstSortedImage:id,product_id,image_path',
                    'inventories' => function ($query) {
                        $query->select('id','product_id','mrp','offer_rate','purchase_rate','sku')
                            ->orderBy('mrp','asc');
                    },

                    'productAttributesValues.attributeValue:id,slug'
                ])
                ->select('id','title','slug','category_id')
                ->get()
                ->map(function ($product) {
                    $inventory = $product->inventories->first();
                    $attribute_slug = optional(
                        $product->productAttributesValues->first()
                    )->attributeValue->slug ?? null;
                    return [
                        'id' => $product->id,
                        'title' => $product->title,
                        'slug' => $product->slug,
                        'image' => $product->firstSortedImage
                            ? $product->firstSortedImage->getThumbImages()
                            : null,
                        'mrp' => $inventory->mrp ?? null,
                        'offer_rate' => $inventory->offer_rate ?? null,
                        'sku' => $inventory->sku ?? null,
                        'attribute_value' => $attribute_slug,
                        'category' => [
                            'title' => $product->category->title ?? null,
                            'slug'  => $product->category->slug ?? null,
                        ],
                    ];
                })
                ->shuffle()
                ->values();
        });

        return response()->json([
            'status' => true,
            'message' => 'New arrival products',
            'data' => $products
        ]);
    }

    public function trendingProducts()
    {
        /*
        After updating product run this code
        Cache::forget('home_trending_products');
        Cache::forget('home_label_trending_products');
        */
        $label = Cache::remember('home_label_trending_products', now()->addHours(24), function () {
            return Label::where('slug', 'trending-products')
            ->where('status', 1)
            ->first();
        });
        if (!$label) {
            return response()->json([
                'status' => false,
                'message' => 'Trending label not found',
                'data' => []
            ]);
        }
        $products = Cache::remember('home_trending_products', now()->addHours(24), function () use ($label) {
            return Product::where('product_status', 1)
                ->where('label_id', $label->id)
                ->with([
                    'category:id,title,slug',
                    'firstSortedImage:id,product_id,image_path',
                    'inventories' => function ($query) {
                        $query->select('id','product_id','mrp','offer_rate','purchase_rate','sku')
                            ->orderBy('mrp','asc');
                    },
                    'productAttributesValues.attributeValue:id,slug'
                ])
                ->select('id','title','slug','category_id')
                ->get()
                ->map(function ($product) {
                    $inventory = $product->inventories->first();
                    $attribute_slug = optional(
                        $product->productAttributesValues->first()
                    )->attributeValue->slug ?? null;
                    return [
                        'id' => $product->id,
                        'title' => $product->title,
                        'slug' => $product->slug,
                        'image' => $product->firstSortedImage
                            ? $product->firstSortedImage->getThumbImages()
                            : null,
                        'mrp' => $inventory->mrp ?? null,
                        'offer_rate' => $inventory->offer_rate ?? null,
                        'sku' => $inventory->sku ?? null,
                        'attribute_value' => $attribute_slug,
                        'category' => [
                            'title' => $product->category->title ?? null,
                            'slug'  => $product->category->slug ?? null,
                        ],
                    ];
                })
                ->shuffle()
                ->values();
        });
        return response()->json([
            'status' => true,
            'message' => 'Trending products',
            'data' => $products
        ]);
    }
    public function banner()
    {
        $banners = Cache::remember('home_banners', 3600, function () {
            return Banner::with(['products:id,title,slug'])
            ->where('status', 1)
            ->orderBy('id', 'desc')
            ->get();
        });
        $banners = $banners->map(function ($banner) {
            return [
                'id' => $banner->id,
                'title' => $banner->title,
                'content' => $banner->content,
                'image_path_desktop' => $banner->image_path_desktop 
                ? asset('storage/images/banner-desktop/'.$banner->image_path_desktop)
                : null,
                'image_path_mobile' => $banner->image_path_mobile 
                ? asset('storage/images/banner-mobile/'.$banner->image_path_mobile)
                : null,
                'banner_link' => $banner->products->isNotEmpty(),
                'products' => $banner->products
            ];
        });

        return response()->json([
            'status' => true,
            'message' => 'Banner list fetched successfully',
            'data' => $banners
        ]);
    }
}
