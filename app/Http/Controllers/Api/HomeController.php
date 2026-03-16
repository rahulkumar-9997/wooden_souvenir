<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Label;
use App\Models\Category;
use App\Models\Product;
use App\Models\Attribute_values;
use App\Models\Attribute;
use App\Models\Banner;
use App\Models\Client;
use App\Models\Testimonial;
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
        $banners = Cache::remember('home_banners', now()->addHours(24), function () {
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

    public function client()
    {
        $clients = Cache::remember('home_clients', now()->addHours(24), function () {
            return Client::select('id', 'title', 'image', 'sort_order', 'status')
                ->where('status', 1)
                ->orderBy('sort_order', 'asc')
                ->orderBy('id', 'desc')
                ->limit(15)
                ->get()
                ->map(function ($client) {
                    return [
                        'id' => $client->id,
                        'title' => $client->title,
                        'image' => $client->image 
                            ? asset('storage/images/clients/'.$client->image)
                            : null,
                    ];
                });
        });

        return response()->json([
            'success' => true,
            'message' => 'Clients retrieved successfully',
            'data' => $clients,
            'total' => $clients->count()
        ]);
    }

    public function testimonials()
    {
        $testimonials = Cache::remember('home_testimonials', now()->addHours(24), function () {
            return Testimonial::select('id','name','content','designation','profile_img','status')
                ->where('status',1)
                ->orderBy('id','desc')
                ->limit(10)
                ->get()
                ->map(function ($testimonial) {
                    return [
                        'id' => $testimonial->id,
                        'name' => $testimonial->name ?? null,
                        'designation' => $testimonial->designation ?? null,
                        'content' => $testimonial->content ?? null,
                        'image' => $testimonial->profile_img
                            ? asset('storage/images/testimonials/'.$testimonial->profile_img)
                            : null,
                    ];

                });
        });
        return response()->json([
            'success' => true,
            'message' => 'Testimonials retrieved successfully',
            'data' => $testimonials,
            'total' => $testimonials->count()
        ]);
    }

    public function productCatalog(Request $request, $categorySlug, $attributeSlug, $valueSlug){
        try {   
           
            $category = Category::select('id','title','slug')
            ->where('slug', $categorySlug)
            ->first();
            if (!$category) {
                return response()->json([
                    'status' => false,
                    'message' => 'Category not found'
                ], 404);
            }
            $attribute_top = Attribute::select('id','title','slug')
            ->where('slug', $attributeSlug)
            ->first();
            if (!$attribute_top) {
                return response()->json([
                    'status' => false,
                    'message' => 'Attribute not found'
                ], 404);
            }

            $attributeValue = Attribute_values::select('id','name','slug')
            ->where('slug', $valueSlug)
            ->first();
            if (!$attributeValue) {
                return response()->json([
                    'status' => false,
                    'message' => 'Attribute value not found'
                ], 404);
            }
            $productsQuery = Product::where('category_id', $category->id)
            ->where('product_status', 1);
            $productsQuery->whereHas('attributes', function ($query) use ($attribute_top, $attributeValue) {
                $query->where('attributes_id', $attribute_top->id)
                    ->whereHas('values', function ($q) use ($attributeValue) {
                        $q->where('attributes_value_id', $attributeValue->id);
                    });
            });
            /* Apply additional filters from the request */
            if ($request->has('filter')) {
                Log::info('Filters attributes value catalog: ' . json_encode($request->query()));
                $filters = $request->except(['filter', 'sort', 'page']);
                foreach ($filters as $filterAttributeSlug => $filterValueSlugs) {
                    if ($filterAttributeSlug !== $attribute_top->slug) {
                        if (is_string($filterValueSlugs)) {
                            $filterValueSlugs = explode(',', $filterValueSlugs);
                        }
                        $attribute = Attribute::where('slug', $filterAttributeSlug)->first();
                        if (!$attribute) {
                            Log::warning("Attribute not found for slug: {$filterAttributeSlug}");
                            continue;
                        }
                        $valueIds = Attribute_values::whereIn('slug', $filterValueSlugs)->pluck('id')->toArray();
                        $productsQuery->whereHas('attributes', function ($query) use ($attribute, $valueIds) {
                            $query->where('attributes_id', $attribute->id)
                                ->whereHas('values', function ($q) use ($valueIds) {
                                    $q->whereIn('attributes_value_id', $valueIds);
                                });
                        });
                    }
                }
            }
            if ($request->has('sort')) {
                $sortOption = $request->get('sort');
                switch ($sortOption) {
                    case 'new-arrivals':
                        $productsQuery->orderBy('created_at', 'desc');
                        break;
                    case 'price-low-to-high':
                        $productsQuery->orderByRaw('ISNULL(inventories.offer_rate), inventories.offer_rate ASC');
                        break;
                    case 'price-high-to-low':
                        $productsQuery->orderByRaw('ISNULL(inventories.offer_rate), inventories.offer_rate DESC');
                        break;
                    case 'a-to-z-order':
                        $productsQuery->orderBy('products.title', 'asc');
                        break;
                    default:
                        $productsQuery->orderBy('products.id', 'desc');
                        break;
                }
            } else {
                $productsQuery->orderBy('created_at', 'desc');
            }
            $products = $productsQuery->with([
                'category:id,title,slug',
                'images' => function ($query) {
                    $query->select('id', 'product_id', 'image_path', 'sort_order')
                        ->orderBy('sort_order');
                },
                'ProductAttributesValues' => function ($query) {
                    $query->select('id', 'product_id', 'product_attribute_id', 'attributes_value_id')
                        ->with([
                            'attributeValue:id,slug', 'productAttribute:id,attributes_id'
                        ])
                        ->orderBy('id');
                },
            ])
            ->leftJoin('inventories', function ($join) {
                $join->on('products.id', '=', 'inventories.product_id')
                    ->whereRaw('inventories.mrp = (SELECT MIN(mrp) FROM inventories WHERE product_id = products.id)');
            })
            ->select(
                'products.id',
                'products.title',
                'products.slug',
                'products.category_id',
                'products.created_at',

                'inventories.mrp',
                'inventories.offer_rate',
                'inventories.purchase_rate',
                'inventories.sku',
                'inventories.stock_quantity'
            )
            ->paginate(32)
            ->through(function ($product) {
                $attributes_value = null;
                if($product->ProductAttributesValues->isNotEmpty()){
                    $attributes_value = optional($product->ProductAttributesValues->first()->attributeValue)->slug;
                }
                return [
                    'id' => $product->id,
                    'title' => $product->title,
                    'slug' => $product->slug,                    
                    'mrp' => $product->mrp,
                    'offer_price' => $product->offer_rate,
                    'sku' => $product->sku,
                    'stock_quantity' => $product->stock_quantity,
                    'image' => isset($product->images[0])
                        ? asset('storage/images/product/thumb/'.$product->images[0]->image_path)
                        : null,
                    'attributes_value_slug' =>$attributes_value
                ];
            });
            /* Fetch attributes with values for the filter list (mapped attributes and counts) */
            $attributes_with_values_for_filter_list = $category->attributes()
                ->select(
                    'attributes.id',
                    'attributes.title',
                    'attributes.slug'
                )
                ->with(['AttributesValues' => function ($query) use ($category, $attribute_top, $attributeValue) {
                    $query->select(
                        'id',
                        'attributes_id',
                        'name',
                        'slug'
                    )
                    ->whereHas('map_attributes_value_to_categories', function ($q) use ($category) {
                        $q->where('category_id', $category->id);
                    })
                        ->withCount(['productAttributesValues' => function ($q) use ($category, $attribute_top, $attributeValue) {
                            $q->whereHas('product', function ($q) use ($category, $attribute_top, $attributeValue) {
                                $q->where('category_id', $category->id)
                                    ->whereHas('attributes', function ($query) use ($attribute_top, $attributeValue) {
                                        $query->where('attributes_id', $attribute_top->id)
                                            ->whereHas('values', function ($q) use ($attributeValue) {
                                                $q->where('attributes_value_id', $attributeValue->id);
                                            });
                                    });
                            });
                        }])
                        ->having('product_attributes_values_count', '>', 0)
                        ->orderBy('name');
                }])
            ->orderBy('title')
            ->get()
            ->map(function ($attribute) {
                return [
                    'id' => $attribute->id,
                    'title' => $attribute->title,
                    'slug' => $attribute->slug,
                    'values' => $attribute->AttributesValues->map(function ($value) {
                        return [
                            'id' => $value->id,
                            'name' => $value->name,
                            'slug' => $value->slug
                        ];
                    })
                ];
            });
            $siteName = "Wooden Souvenir";
            $meta = [
            'title'=>$attributeValue->name.' '.$category->title.' | '.$siteName,
            'description'=>'Buy '.$attributeValue->name.' '.$category->title.' from '.$siteName.'. Explore premium quality '.$category->title.' crafted with the best '.$attributeValue->name.'.',
            'keywords'=>$siteName.', '.$category->title.', '.$attributeValue->name.' '.$category->title
        ];
        return response()->json([
            'success'=>true,
            'message'=>'Products retrieved successfully',
            'data'=>[
                'meta'=>$meta,
                'category'=>$category,
                'attribute'=>$attribute_top,
                'attribute_value'=>$attributeValue,
                'products'=>$products,
                'product_filters'=>$attributes_with_values_for_filter_list
            ]
        ]);
            
        } catch (\Exception $e) {
            Log::error('Error fetching product catalog: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
			return response()->json([
				'error' => $e->getMessage(),
				'file' => $e->getFile(),
				'line' => $e->getLine(),
			], 500);
        }
    }

    public function productCatalog_with_cache(Request $request, $categorySlug, $attributeSlug, $valueSlug){
        try {   
            $cacheKey = "catalog_{$categorySlug}_{$attributeSlug}_{$valueSlug}_".md5(json_encode($request->query())); 
                $data = Cache::remember($cacheKey, now()->addMinutes(30), function () use ($request, $categorySlug, $attributeSlug, $valueSlug) {       
                $category = Category::select('id','title','slug')
                ->where('slug', $categorySlug)
                ->first();
                if (!$category) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Category not found'
                    ], 404);
                }
                $attribute_top = Attribute::select('id','title','slug')
                ->where('slug', $attributeSlug)
                ->first();
                if (!$attribute_top) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Attribute not found'
                    ], 404);
                }

                $attributeValue = Attribute_values::select('id','name','slug')
                ->where('slug', $valueSlug)
                ->first();
                if (!$attributeValue) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Attribute value not found'
                    ], 404);
                }
                $productsQuery = Product::where('category_id', $category->id)
                ->where('product_status', 1);
                $productsQuery->whereHas('attributes', function ($query) use ($attribute_top, $attributeValue) {
                    $query->where('attributes_id', $attribute_top->id)
                        ->whereHas('values', function ($q) use ($attributeValue) {
                            $q->where('attributes_value_id', $attributeValue->id);
                        });
                });
                /* Apply additional filters from the request */
                if ($request->has('filter')) {
                    Log::info('Filters attributes value catalog: ' . json_encode($request->query()));
                    $filters = $request->except(['filter', 'sort', 'page']);
                    foreach ($filters as $filterAttributeSlug => $filterValueSlugs) {
                        if ($filterAttributeSlug !== $attribute_top->slug) {
                            if (is_string($filterValueSlugs)) {
                                $filterValueSlugs = explode(',', $filterValueSlugs);
                            }
                            $attribute = Attribute::where('slug', $filterAttributeSlug)->first();
                            if (!$attribute) {
                                Log::warning("Attribute not found for slug: {$filterAttributeSlug}");
                                continue;
                            }
                            $valueIds = Attribute_values::whereIn('slug', $filterValueSlugs)->pluck('id')->toArray();
                            $productsQuery->whereHas('attributes', function ($query) use ($attribute, $valueIds) {
                                $query->where('attributes_id', $attribute->id)
                                    ->whereHas('values', function ($q) use ($valueIds) {
                                        $q->whereIn('attributes_value_id', $valueIds);
                                    });
                            });
                        }
                    }
                }
                if ($request->has('sort')) {
                    $sortOption = $request->get('sort');
                    switch ($sortOption) {
                        case 'new-arrivals':
                            $productsQuery->orderBy('created_at', 'desc');
                            break;
                        case 'price-low-to-high':
                            $productsQuery->orderByRaw('ISNULL(inventories.offer_rate), inventories.offer_rate ASC');
                            break;
                        case 'price-high-to-low':
                            $productsQuery->orderByRaw('ISNULL(inventories.offer_rate), inventories.offer_rate DESC');
                            break;
                        case 'a-to-z-order':
                            $productsQuery->orderBy('products.title', 'asc');
                            break;
                        default:
                            $productsQuery->orderBy('products.id', 'desc');
                            break;
                    }
                } else {
                    $productsQuery->orderBy('created_at', 'desc');
                }
                $products = $productsQuery->with([
                    'category:id,title,slug',
                    'images' => function ($query) {
                        $query->select('id', 'product_id', 'image_path', 'sort_order')
                            ->orderBy('sort_order');
                    },
                    'ProductAttributesValues' => function ($query) {
                        $query->select('id', 'product_id', 'product_attribute_id', 'attributes_value_id')
                            ->with([
                                'attributeValue:id,slug', 'productAttribute:id,attributes_id'
                            ])
                            ->orderBy('id');
                    },
                ])
                ->leftJoin('inventories', function ($join) {
                    $join->on('products.id', '=', 'inventories.product_id')
                        ->whereRaw('inventories.mrp = (SELECT MIN(mrp) FROM inventories WHERE product_id = products.id)');
                })
                ->select(
                    'products.id',
                    'products.title',
                    'products.slug',
                    'products.category_id',
                    'products.created_at',

                    'inventories.mrp',
                    'inventories.offer_rate',
                    'inventories.purchase_rate',
                    'inventories.sku',
                    'inventories.stock_quantity'
                )
                ->paginate(32)
                ->through(function ($product) {
                    $attributes_value = null;
                    if($product->ProductAttributesValues->isNotEmpty()){
                        $attributes_value = optional($product->ProductAttributesValues->first()->attributeValue)->slug;
                    }
                    return [
                        'id' => $product->id,
                        'title' => $product->title,
                        'slug' => $product->slug,
                        'price' => [
                            'mrp' => $product->mrp,
                            'offer_price' => $product->offer_rate
                        ],
                        'sku' => $product->sku,
                        'stock_quantity' => $product->stock_quantity,
                        'image' => isset($product->images[0])
                            ? asset('storage/images/product/thumb/'.$product->images[0]->image_path)
                            : null,
                        'attributes_value_slug' =>$attributes_value
                    ];
                });
                /* Fetch attributes with values for the filter list (mapped attributes and counts) */
                $attributes_with_values_for_filter_list = $category->attributes()
                    ->select(
                        'attributes.id',
                        'attributes.title',
                        'attributes.slug'
                    )
                    ->with(['AttributesValues' => function ($query) use ($category, $attribute_top, $attributeValue) {
                        $query->select(
                            'id',
                            'attributes_id',
                            'name',
                            'slug'
                        )
                        ->whereHas('map_attributes_value_to_categories', function ($q) use ($category) {
                            $q->where('category_id', $category->id);
                        })
                            ->withCount(['productAttributesValues' => function ($q) use ($category, $attribute_top, $attributeValue) {
                                $q->whereHas('product', function ($q) use ($category, $attribute_top, $attributeValue) {
                                    $q->where('category_id', $category->id)
                                        ->whereHas('attributes', function ($query) use ($attribute_top, $attributeValue) {
                                            $query->where('attributes_id', $attribute_top->id)
                                                ->whereHas('values', function ($q) use ($attributeValue) {
                                                    $q->where('attributes_value_id', $attributeValue->id);
                                                });
                                        });
                                });
                            }])
                            ->having('product_attributes_values_count', '>', 0)
                            ->orderBy('name');
                    }])
                ->orderBy('title')
                ->get()
                ->map(function ($attribute) {
                    return [
                        'id' => $attribute->id,
                        'title' => $attribute->title,
                        'slug' => $attribute->slug,
                        'values' => $attribute->AttributesValues->map(function ($value) {
                            return [
                                'id' => $value->id,
                                'name' => $value->name,
                                'slug' => $value->slug
                            ];
                        })
                    ];
                });
                $siteName = "Wooden Souvenir";
                $meta = [
                    'title' => $attributeValue->name.' '.$category->title.' | '.$siteName,
                    'description' =>
                        'Buy '.$attributeValue->name.' '.$category->title.
                        ' from '.$siteName.
                        '. Explore premium quality '.$category->title.
                        ' crafted with the best '.$attributeValue->name.'.',
                    'keywords' =>
                        $siteName.', '.$category->title.', '.$attributeValue->name.' '.$category->title

                ];
                return [
                    'meta' => $meta,
                    'category' => $category,
                    'attribute' => $attribute_top,
                    'attribute_value' => $attributeValue,
                    'products' => $products,
                    'product_filters' => $attributes_with_values_for_filter_list
                ];	
            });
            return response()->json([
                'success' => true,
                'message' => 'Products retrieved successfully',
                'data' => $data,
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error fetching product catalog: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
			return response()->json([
				'error' => $e->getMessage(),
				'file' => $e->getFile(),
				'line' => $e->getLine(),
			], 500);
        }
    }

}
