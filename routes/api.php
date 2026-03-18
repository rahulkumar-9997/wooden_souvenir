<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\MenuController;
use App\Http\Controllers\Api\HomeController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ProductReviewsController;
use App\Http\Controllers\Api\BlogController;
use App\Http\Controllers\Api\CustomerAuthController;
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('menu', [MenuController::class, 'menu']);
Route::get('/home/new-arrivals', [HomeController::class, 'newArrivals']);
Route::get('/home/trending-products', [HomeController::class, 'trendingProducts']);
Route::get('/home/banner', [HomeController::class, 'banner']);
Route::get('/home/client', [HomeController::class, 'client']);
Route::get('/home/testimonials', [HomeController::class, 'testimonials']);
Route::get('blog', [BlogController::class, 'blogList']);
Route::get('blog/{slug}', [BlogController::class, 'blogDetails']);

Route::get('product-catalog/{category}/{attribute}/{value}', [ProductController::class, 'productCatalog']);
Route::get('products/{product_slug}/{attributes_value}', [ProductController::class, 'productDetails']);








Route::prefix('customer')->group(function () {
    Route::post('/send-otp', [CustomerAuthController::class, 'sendOtp']);
    Route::post('/verify-otp', [CustomerAuthController::class, 'verifyOtpAndLogin']);
    Route::post('/resend-otp', [CustomerAuthController::class, 'resendOtp']);
    Route::post('/check-contact', [CustomerAuthController::class, 'checkContactExists']);
    Route::post('/login', [CustomerAuthController::class, 'loginOrCreateAccountWithOtp']);
});
