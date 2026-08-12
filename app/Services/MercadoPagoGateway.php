<?php

namespace App\Services;

use App\Contracts\PaymentGateway;
use App\Exceptions\PaymentGatewayException;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class MercadoPagoGateway implements PaymentGateway
{
    public function isConfigured(): bool
    {
        return filled(config('services.mercado_pago.access_token'));
    }

    /**
     * @return array<string, mixed>
     */
    public function createPix(Order $order, Payment $payment): array
    {
        $amount = $this->formatAmount($payment->amount_cents);

        return $this->send(function () use ($order, $payment, $amount) {
            return $this->request($payment->idempotency_key)->post('/v1/orders', [
                'type' => 'online',
                'total_amount' => $amount,
                'external_reference' => $order->number,
                'processing_mode' => 'automatic',
                'description' => "Pedido {$order->number}",
                'transactions' => [
                    'payments' => [[
                        'amount' => $amount,
                        'payment_method' => [
                            'id' => 'pix',
                            'type' => 'bank_transfer',
                        ],
                        'expiration_time' => config('services.mercado_pago.pix_expiration'),
                    ]],
                ],
                'payer' => [
                    'email' => $order->user->email,
                ],
            ]);
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function findOrder(string $providerOrderId): array
    {
        return $this->send(
            fn () => $this->request()->get('/v1/orders/'.rawurlencode($providerOrderId)),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function refundOrder(string $providerOrderId, string $idempotencyKey): array
    {
        return $this->send(
            fn () => $this->request($idempotencyKey)
                ->send('POST', '/v1/orders/'.rawurlencode($providerOrderId).'/refund'),
        );
    }

    private function request(?string $idempotencyKey = null): PendingRequest
    {
        $accessToken = config('services.mercado_pago.access_token');

        if (! is_string($accessToken) || $accessToken === '') {
            throw new PaymentGatewayException('O Mercado Pago nao esta configurado.');
        }

        $request = Http::baseUrl((string) config('services.mercado_pago.base_url'))
            ->acceptJson()
            ->asJson()
            ->withToken($accessToken)
            ->timeout(10);

        if ($idempotencyKey !== null) {
            $request->withHeader('X-Idempotency-Key', $idempotencyKey);
        }

        return $request;
    }

    /**
     * @param  callable(): Response  $callback
     * @return array<string, mixed>
     */
    private function send(callable $callback): array
    {
        try {
            $response = $callback();
            $response->throw();
        } catch (ConnectionException|RequestException $exception) {
            throw new PaymentGatewayException(
                'Nao foi possivel concluir a operacao no Mercado Pago.',
                previous: $exception,
            );
        }

        $payload = $response->json();

        if (! is_array($payload)) {
            throw new PaymentGatewayException('O Mercado Pago retornou uma resposta invalida.');
        }

        return $payload;
    }

    private function formatAmount(int $amountCents): string
    {
        return sprintf('%d.%02d', intdiv($amountCents, 100), $amountCents % 100);
    }
}
