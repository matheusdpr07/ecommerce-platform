<?php

namespace App\Providers;

use App\Models\Address;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\ShippingMethod;
use App\Models\User;
use App\Policies\AddressPolicy;
use App\Policies\BrandPolicy;
use App\Policies\CategoryPolicy;
use App\Policies\CouponPolicy;
use App\Policies\ProductPolicy;
use App\Policies\PromotionPolicy;
use App\Policies\ShippingMethodPolicy;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Category::class, CategoryPolicy::class);
        Gate::policy(Brand::class, BrandPolicy::class);
        Gate::policy(Product::class, ProductPolicy::class);
        Gate::policy(Coupon::class, CouponPolicy::class);
        Gate::policy(Promotion::class, PromotionPolicy::class);
        Gate::policy(Address::class, AddressPolicy::class);
        Gate::policy(ShippingMethod::class, ShippingMethodPolicy::class);

        Vite::prefetch(concurrency: 3);
    }
}
