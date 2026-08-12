<?php

use App\Http\Controllers\Admin\BrandController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CouponController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\PromotionController;
use App\Http\Controllers\Admin\ShippingMethodController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

Route::resource('categories', CategoryController::class)->except(['show']);
Route::resource('brands', BrandController::class)->except(['show']);
Route::resource('products', ProductController::class)->except(['show']);
Route::resource('coupons', CouponController::class)->except(['show']);
Route::resource('promotions', PromotionController::class)->except(['show']);
Route::resource('shipping-methods', ShippingMethodController::class)->except(['show']);
