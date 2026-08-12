<?php

namespace App\Http\Middleware;

use App\Services\CartService;
use App\Services\CustomerNotificationService;
use App\Services\WishlistService;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'email_verified_at' => $user->email_verified_at,
                    'role' => $user->role->value,
                ] : null,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'store' => [
                'name' => config('store.name'),
                'eyebrow' => config('store.eyebrow'),
                'tagline' => config('store.tagline'),
                'support_email' => config('store.support_email'),
            ],
            'cart' => fn () => app(CartService::class)->getSummary($request),
            'wishlist' => fn () => $request->user()
                ? app(WishlistService::class)->getSummary($request->user())
                : ['item_count' => 0],
            'notifications' => fn () => $request->user()
                ? ['unread_count' => app(CustomerNotificationService::class)->unreadCount($request->user())]
                : ['unread_count' => 0],
        ];
    }
}
