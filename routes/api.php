<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\MenuController;
use App\Http\Controllers\Api\HomeController;
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('menu', [MenuController::class, 'menu']);
Route::get('/home/new-arrivals', [HomeController::class, 'newArrivals']);
Route::get('/home/trending-products', [HomeController::class, 'trendingProducts']);
Route::get('/home/banner', [HomeController::class, 'banner']);
Route::get('/home/client', [HomeController::class, 'client']);