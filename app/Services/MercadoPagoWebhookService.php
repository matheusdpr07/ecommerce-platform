<?php

namespace App\Services;

use App\Contracts\PaymentGateway;
use App\Enums\WebhookEventStatus;
use App\Models\Payment;
use App\Models\WebhookEvent;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class MercadoPagoWebhookService
{
    public function __construct(
        private readonly PaymentGateway $gateway,
        private readonly PaymentService $paymentService,
    ) {}

    public function handle(Request $request): WebhookEvent
    {
        $resourceId = $request->query('data.id') ?? $request->query('data_id');

        if (! is_string($resourceId) || $resourceId === '') {
            throw new HttpException(400, 'O identificador do recurso nao foi informado.');
        }

        $this->assertValidSignature($request, $resourceId);

        $payload = $request->json()->all();
        $providerEventId = $payload['id'] ?? null;

        if (! is_string($providerEventId) && ! is_int($providerEventId)) {
            throw new HttpException(400, 'O identificador do evento nao foi informado.');
        }

        $event = WebhookEvent::query()->firstOrCreate(
            [
                'provider' => 'mercado_pago',
                'provider_event_id' => (string) $providerEventId,
            ],
            [
                'event_type' => (string) ($payload['type'] ?? ''),
                'action' => isset($payload['action']) ? (string) $payload['action'] : null,
                'resource_id' => $resourceId,
                'request_id' => $request->header('X-Request-Id'),
                'payload' => $payload,
                'status' => WebhookEventStatus::Pending,
            ],
        );

        if (in_array($event->status, [
            WebhookEventStatus::Processed,
            WebhookEventStatus::Ignored,
        ], true)) {
            return $event;
        }

        if (($payload['type'] ?? null) !== 'order'
            || data_get($payload, 'data.id') !== $resourceId
        ) {
            $event->update([
                'status' => WebhookEventStatus::Ignored,
                'processed_at' => now(),
                'error' => null,
            ]);

            return $event->fresh();
        }

        $payment = Payment::query()
            ->where('provider', 'mercado_pago')
            ->where('provider_order_id', $resourceId)
            ->first();

        if ($payment === null) {
            $event->update([
                'status' => WebhookEventStatus::Ignored,
                'processed_at' => now(),
                'error' => null,
            ]);

            return $event->fresh();
        }

        try {
            $providerOrder = $this->gateway->findOrder($resourceId);
            $this->paymentService->syncFromProvider($payment, $providerOrder);

            $event->update([
                'status' => WebhookEventStatus::Processed,
                'processed_at' => now(),
                'error' => null,
            ]);
        } catch (Throwable $exception) {
            $event->update([
                'status' => WebhookEventStatus::Failed,
                'processed_at' => null,
                'error' => 'Falha ao sincronizar o recurso no provedor.',
            ]);

            throw $exception;
        }

        return $event->fresh();
    }

    private function assertValidSignature(Request $request, string $resourceId): void
    {
        $secret = config('services.mercado_pago.webhook_secret');

        if (! is_string($secret) || $secret === '') {
            throw new HttpException(503, 'O webhook do Mercado Pago nao esta configurado.');
        }

        $signatureHeader = $request->header('X-Signature');
        $requestId = $request->header('X-Request-Id');

        if (! is_string($signatureHeader) || ! is_string($requestId) || $requestId === '') {
            throw new HttpException(401, 'Assinatura de webhook invalida.');
        }

        $signatureParts = [];

        foreach (explode(',', $signatureHeader) as $part) {
            [$key, $value] = array_pad(explode('=', trim($part), 2), 2, null);

            if (is_string($key) && is_string($value)) {
                $signatureParts[$key] = $value;
            }
        }

        $timestamp = $signatureParts['ts'] ?? null;
        $providedSignature = $signatureParts['v1'] ?? null;

        if (! is_string($timestamp) || ! ctype_digit($timestamp) || ! is_string($providedSignature)) {
            throw new HttpException(401, 'Assinatura de webhook invalida.');
        }

        $timestampSeconds = strlen($timestamp) > 10
            ? intdiv((int) $timestamp, 1000)
            : (int) $timestamp;
        $tolerance = max((int) config('services.mercado_pago.webhook_tolerance_seconds'), 0);

        if ($tolerance > 0 && abs(now()->timestamp - $timestampSeconds) > $tolerance) {
            throw new HttpException(401, 'Assinatura de webhook expirada.');
        }

        $manifest = sprintf(
            'id:%s;request-id:%s;ts:%s;',
            strtolower($resourceId),
            $requestId,
            $timestamp,
        );
        $expectedSignature = hash_hmac('sha256', $manifest, $secret);

        if (! hash_equals($expectedSignature, $providedSignature)) {
            throw new HttpException(401, 'Assinatura de webhook invalida.');
        }
    }
}
