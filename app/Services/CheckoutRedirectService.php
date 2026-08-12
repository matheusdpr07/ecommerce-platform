<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;

class CheckoutRedirectService
{
    public function isCheckoutIntended(Request $request): bool
    {
        $intendedUrl = $request->session()->get('url.intended');

        if (! is_string($intendedUrl)) {
            return false;
        }

        $intendedPath = parse_url($intendedUrl, PHP_URL_PATH);
        $checkoutPath = parse_url(route('store.checkout.index', absolute: false), PHP_URL_PATH);

        return is_string($intendedPath)
            && is_string($checkoutPath)
            && rtrim($intendedPath, '/') === rtrim($checkoutPath, '/');
    }

    public function shouldResumeCheckout(Request $request): bool
    {
        return $this->isCheckoutIntended($request)
            || ($request->user() !== null && $this->userHasCartItems($request->user()));
    }

    public function destinationAfterVerification(User $user): string
    {
        if ($this->userHasCartItems($user)) {
            return route('store.checkout.index', absolute: false);
        }

        return route('dashboard', absolute: false).'?verified=1';
    }

    private function userHasCartItems(User $user): bool
    {
        return $user->cart()
            ->whereHas('items')
            ->exists();
    }
}
