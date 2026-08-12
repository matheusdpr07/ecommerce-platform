<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\MercadoPagoWebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MercadoPagoWebhookController extends Controller
{
    public function __construct(
        private readonly MercadoPagoWebhookService $webhookService,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $this->webhookService->handle($request);

        return response()->json(['received' => true]);
    }
}
