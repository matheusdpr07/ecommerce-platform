<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\CheckoutRedirectService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmailVerificationPromptController extends Controller
{
    public function __construct(
        private readonly CheckoutRedirectService $checkoutRedirectService,
    ) {}

    /**
     * Display the email verification prompt.
     */
    public function __invoke(Request $request): RedirectResponse|Response
    {
        return $request->user()->hasVerifiedEmail()
                    ? redirect()->intended(
                        $this->checkoutRedirectService->destinationAfterVerification($request->user()),
                    )
                    : Inertia::render('Auth/VerifyEmail', [
                        'status' => session('status'),
                        'checkoutIntent' => $this->checkoutRedirectService->shouldResumeCheckout($request),
                    ]);
    }
}
