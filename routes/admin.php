<?php

use App\Http\Controllers\Admin\BrandController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CouponController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\PromotionController;
use App\Http\Controllers\Admin\ShippingMethodController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
Route::get('orders/{order}', [OrderController::class, 'show'])->name('orders.show');
Route::post('orders/{order}/refund', [OrderController::class, 'refund'])->name('orders.refund');
Route::patch('orders/{order}/fulfillment', [OrderController::class, 'updateFulfillment'])
    ->name('orders.fulfillment.update');
Route::patch('orders/{order}/notes', [OrderController::class, 'updateNotes'])
    ->name('orders.notes.update');

Route::get('inventory', [InventoryController::class, 'index'])->name('inventory.index');
Route::get('inventory/{variant}', [InventoryController::class, 'show'])->name('inventory.show');
Route::patch('inventory/{variant}', [InventoryController::class, 'update'])->name('inventory.update');
Route::post('inventory/{variant}/adjustments', [InventoryController::class, 'adjust'])->name('inventory.adjust');

Route::resource('categories', CategoryController::class)->except(['show']);
Route::resource('brands', BrandController::class)->except(['show']);
Route::resource('products', ProductController::class)->except(['show']);
Route::resource('coupons', CouponController::class)->except(['show']);
Route::resource('promotions', PromotionController::class)->except(['show']);
Route::resource('shipping-methods', ShippingMethodController::class)->except(['show']);
