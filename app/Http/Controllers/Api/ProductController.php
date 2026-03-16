<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Category;
use App\Models\Product;
use App\Models\Attribute_values;
use App\Models\Attribute;
use App\Models\RelatedProduct;
use App\Models\ProductReview;
use App\Models\ProductReviewFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
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
            ->paginate(5)
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
        $pagination = [
            'current_page' => $products->currentPage(),
            'total_pages' => $products->lastPage(),
            'per_page' => $products->perPage(),
            'total_products' => $products->total(),
            'next_page_url' => $products->nextPageUrl(),
            'previous_page_url' => $products->previousPageUrl(),
            'has_next_page' => $products->hasMorePages(),
            'has_previous_page' => $products->currentPage() > 1
        ];
        return response()->json([
            'success'=>true,
            'message'=>'Products retrieved successfully',
            'data'=>[
                'meta'=>$meta,
                'category'=>$category,
                'attribute'=>$attribute_top,
                'attribute_value'=>$attributeValue,
                'products'=>$products->items(),
                'pagination' => $pagination,
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
                ->paginate(5)
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

    public function productDetails(Request $request, $product_slug, $attribute_value)
    {
        try {
            DB::enableQueryLog(); 
            if (empty($product_slug) || empty($attribute_value)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product slug and attribute value are required'
                ], 400);
            }
            $product = Product::where('slug', $product_slug)->first();
            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found'
                ], 404);
            }
            $product->increment('visitor_count');
            $attributeValue = Attribute_values::where('slug', $attribute_value)->first();
            if (!$attributeValue) {
                return response()->json([
                    'success' => false,
                    'message' => 'Attribute value not found'
                ], 404);
            }
            $product_details = Product::with([
                'images:id,product_id,image_path,sort_order',
                'category:id,title,slug',
                'attributes.attribute:id,title,slug',
                'attributes.values.attributeValue:id,name,slug,attributes_id',
                'additionalFeatures.feature:id,name',
                'reviews.reviewFiles',
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
                'products.product_description',
                'products.product_specification',
                'products.meta_title',
                'products.meta_description',
                'products.video_id',
                'inventories.mrp', 
                'inventories.offer_rate', 
                'inventories.purchase_rate', 
                'inventories.sku', 
                'inventories.stock_quantity'
            )
            ->where('products.slug', $product_slug)
            ->first();
                
            if (!$product_details) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product details not found'
                ], 404);
            }
            $product_details->setRelation(
                'images',
                $product_details->images->map(function ($img) {
                    return asset('storage/images/product/thumb/'.$img->image_path);
                })->values()
            );
            // $reviews = $product_details->reviews ?? collect();
            // $totalReviews = $reviews->count();
            // $review_stats = [
            //     'average_rating' => $totalReviews > 0 ? round($reviews->avg('rating_star_value'), 1) : 0,
            //     'total_reviews' => $totalReviews,
            //     'star_counts' => [
            //         5 => $reviews->where('rating_star_value', 5)->count(),
            //         4 => $reviews->where('rating_star_value', 4)->count(),
            //         3 => $reviews->where('rating_star_value', 3)->count(),
            //         2 => $reviews->where('rating_star_value', 2)->count(),
            //         1 => $reviews->where('rating_star_value', 1)->count(),
            //     ]
            // ];
            // $reviews_paginated = $product_details->reviews()->with('reviewFiles')->paginate(10);
            $categoryId = $product_details->category->id ?? null;
            $related_products_query = Product::with([
                'category:id,title,slug',
                'firstSortedImage:id,product_id,image_path',
                'ProductAttributesValues' => function ($query) use ($attributeValue) {
                    $query->select('id', 'product_id', 'product_attribute_id', 'attributes_value_id')
                        ->where('attributes_value_id', $attributeValue->id)
                        ->with([
                            'attributeValue:id,slug'
                        ])
                        ->orderBy('id');
                }
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
            );
            if ($categoryId) {
                $related_products_query->where('products.category_id', $categoryId);
            }
            $related_products = $related_products_query
                ->where('products.id', '!=', $product->id) 
                ->whereHas('productAttributesValues', function ($query) use ($attributeValue) {
                    $query->where('attributes_value_id', $attributeValue->id);
                })
                ->inRandomOrder()
                ->limit(10)
                ->get()
            ->map(function ($product) {
                $attribute_slug = optional(
                    $product->ProductAttributesValues->first()
                )->attributeValue->slug ?? null;

                return [
                    'id' => $product->id,
                    'title' => $product->title,
                    'slug' => $product->slug,
                    'attribute_value' => $attribute_slug,
                    'category_title' => $product->category->title ?? null,
                    'image' => $product->firstSortedImage ?         $product->firstSortedImage->getThumbImages() : null,
                    ];
            })->values();
            
            $variantIds = RelatedProduct::where('product_id', $product->id)->pluck('variant_id');
            $other_related_products_query = RelatedProduct::with([
                'product' => function ($query) use ($attributeValue) {
                    $query->select('id', 'title', 'slug')
                        ->with([
                            'images' => function($q) {
                                $q->orderBy('sort_order')->limit(1);
                            },
                            'ProductAttributesValues' => function ($q) use ($attributeValue) {
                                $q->select('id', 'product_id', 'product_attribute_id', 'attributes_value_id')
                                    ->where('attributes_value_id', $attributeValue->id)
                                    ->with([
                                        'attributeValue:id,slug'
                                    ])
                                    ->orderBy('id');
                            }
                        ]);
                }
            ]);

            if ($variantIds->isNotEmpty()) {
                $other_related_products = $other_related_products_query
                    ->whereIn('variant_id', $variantIds)
                    ->select('product_id', 'variant_id', 'title', 'group_title', 'description')
                    ->get()
                    ->groupBy('group_title');
            } else {
                $other_related_products = collect();
            }
            return response()->json([
                'success' => true,
                'message' => 'Product details fetched successfully',
                'data' => [
                    'attributes_value_name' => [
                        'id' => $attributeValue->id,
                        'title' => $attributeValue->name,
                        'slug' => $attributeValue->slug
                    ],
                    'product_details' => $product_details,
                    // 'review_stats' => $review_stats,
                    // 'reviews' => $reviews_paginated,
                    'related_products' => $related_products,
                    'other_related_products' => $other_related_products,
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Product Details API Error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'sql' => DB::getQueryLog()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching product details',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
                'file' => config('app.debug') ? $e->getFile() : null,
                'line' => config('app.debug') ? $e->getLine() : null
            ], 500);
        }
    }
}
