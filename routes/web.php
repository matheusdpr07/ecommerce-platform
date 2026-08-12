<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Store\ProductController as StoreProductController;
use Illuminate\Support\Facades\Route;

Route::get('/', [StoreProductController::class, 'index'])->name('store.home');
Route::get('/categories/{category:slug}', [StoreProductController::class, 'index'])->name('store.categories.show');
Route::get('/products/{slug}', [StoreProductController::class, 'show'])->name('store.products.show');

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
