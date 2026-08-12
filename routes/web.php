<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Store\CartController;
use App\Http\Controllers\Store\ProductController as StoreProductController;
use App\Http\Controllers\Store\WishlistController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', [StoreProductController::class, 'index'])->name('store.home');
Route::get('/categories/{category:slug}', [StoreProductController::class, 'index'])->name('store.categories.show');
Route::get('/products/{slug}', [StoreProductController::class, 'show'])->name('store.products.show');

Route::get('/cart', [CartController::class, 'index'])->name('store.cart.index');
Route::post('/cart/items', [CartController::class, 'store'])->name('store.cart.items.store');
Route::patch('/cart/items/{cartItem}', [CartController::class, 'update'])->name('store.cart.items.update');
Route::delete('/cart/items/{cartItem}', [CartController::class, 'destroy'])->name('store.cart.items.destroy');
Route::delete('/cart', [CartController::class, 'clear'])->name('store.cart.clear');

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/wishlist', [WishlistController::class, 'index'])->name('store.wishlist.index');
    Route::post('/wishlist/items', [WishlistController::class, 'store'])->name('store.wishlist.items.store');
    Route::delete('/wishlist/items/{wishlistItem}', [WishlistController::class, 'destroy'])->name('store.wishlist.items.destroy');
    Route::post('/wishlist/items/{wishlistItem}/move-to-cart', [WishlistController::class, 'moveToCart'])->name('store.wishlist.items.move-to-cart');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
