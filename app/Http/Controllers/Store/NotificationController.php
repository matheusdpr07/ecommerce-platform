<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Services\CustomerNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    public function __construct(
        private readonly CustomerNotificationService $notificationService,
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render('Store/Notifications/Index', [
            'customerNotifications' => $this->notificationService->inbox($request->user()),
        ]);
    }

    public function update(Request $request, string $notification): RedirectResponse
    {
        $actionUrl = $this->notificationService->markAsRead(
            $request->user(),
            $notification,
        );

        return $actionUrl
            ? redirect()->to($actionUrl)
            : back()->with('success', 'Notificação marcada como lida.');
    }

    public function markAllAsRead(Request $request): RedirectResponse
    {
        $this->notificationService->markAllAsRead($request->user());

        return back()->with('success', 'Todas as notificações foram marcadas como lidas.');
    }
}
