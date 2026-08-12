<?php

namespace App\Services;

use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\ReviewStatus;
use App\Models\Order;
use App\Models\Review;
use App\Models\User;
use App\Notifications\CustomerActivityNotification;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Pagination\LengthAwarePaginator;

class CustomerNotificationService
{
    /**
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function inbox(User $user): LengthAwarePaginator
    {
        return $user->notifications()
            ->latest()
            ->paginate(20)
            ->through(fn (DatabaseNotification $notification) => [
                'id' => $notification->id,
                'type' => $notification->data['type'] ?? 'activity.updated',
                'title' => $notification->data['title'] ?? 'Atualização',
                'message' => $notification->data['message'] ?? '',
                'action_url' => $notification->data['action_url'] ?? null,
                'action_label' => $notification->data['action_label'] ?? null,
                'tone' => $notification->data['tone'] ?? 'neutral',
                'read_at' => $notification->read_at?->toIso8601String(),
                'created_at' => $notification->created_at?->toIso8601String(),
            ]);
    }

    public function unreadCount(User $user): int
    {
        return $user->unreadNotifications()->count();
    }

    public function markAsRead(User $user, string $notificationId): ?string
    {
        /** @var DatabaseNotification $notification */
        $notification = $user->notifications()->whereKey($notificationId)->firstOrFail();
        $notification->markAsRead();

        return $notification->data['action_url'] ?? null;
    }

    public function markAllAsRead(User $user): void
    {
        $user->unreadNotifications()->update(['read_at' => now()]);
    }

    public function orderPlaced(Order $order): void
    {
        $this->send(
            $order,
            'order.placed',
            'Pedido recebido',
            "O pedido {$order->number} foi criado. Agora falta concluir o pagamento.",
            'Ver pedido',
            'neutral',
        );
    }

    public function orderStatusChanged(Order $order): void
    {
        [$type, $title, $message, $tone] = match ($order->status) {
            OrderStatus::Paid => [
                'payment.approved',
                'Pagamento confirmado',
                "Recebemos o pagamento do pedido {$order->number}. A preparação começa em breve.",
                'success',
            ],
            OrderStatus::PaymentFailed => [
                'payment.failed',
                'Pagamento não aprovado',
                "Não foi possível aprovar o pagamento do pedido {$order->number}.",
                'danger',
            ],
            OrderStatus::Cancelled => [
                'order.cancelled',
                'Pedido cancelado',
                "O pedido {$order->number} foi cancelado e os itens voltaram ao estoque.",
                'danger',
            ],
            OrderStatus::PartiallyRefunded => [
                'payment.partially_refunded',
                'Reembolso parcial processado',
                "Parte do valor do pedido {$order->number} foi reembolsada.",
                'neutral',
            ],
            OrderStatus::Refunded => [
                'payment.refunded',
                'Reembolso concluído',
                "O valor do pedido {$order->number} foi reembolsado.",
                'neutral',
            ],
            OrderStatus::ChargedBack => [
                'payment.charged_back',
                'Pagamento contestado',
                "O pagamento do pedido {$order->number} foi contestado. Consulte os detalhes.",
                'danger',
            ],
            default => [
                'order.updated',
                'Pedido atualizado',
                "Há uma atualização no pedido {$order->number}.",
                'neutral',
            ],
        };

        $this->send($order, $type, $title, $message, 'Ver pedido', $tone);
    }

    public function fulfillmentChanged(Order $order): void
    {
        [$type, $title, $message, $tone] = match ($order->fulfillment_status) {
            FulfillmentStatus::Preparing => [
                'fulfillment.preparing',
                'Seu pedido está em preparação',
                "Estamos separando os itens do pedido {$order->number} com todo cuidado.",
                'neutral',
            ],
            FulfillmentStatus::Shipped => [
                'fulfillment.shipped',
                'Seu pedido está a caminho',
                "O pedido {$order->number} foi enviado. Acompanhe a entrega pela sua conta.",
                'success',
            ],
            FulfillmentStatus::Delivered => [
                'fulfillment.delivered',
                'Pedido entregue',
                "O pedido {$order->number} foi entregue. Esperamos que você ame cada detalhe.",
                'success',
            ],
            FulfillmentStatus::Cancelled => [
                'fulfillment.cancelled',
                'Entrega cancelada',
                "A operação de entrega do pedido {$order->number} foi cancelada.",
                'danger',
            ],
            default => [
                'fulfillment.updated',
                'Entrega atualizada',
                "Há uma atualização na entrega do pedido {$order->number}.",
                'neutral',
            ],
        };

        $this->send($order, $type, $title, $message, 'Acompanhar pedido', $tone);
    }

    public function reviewModerated(Review $review): void
    {
        $review->loadMissing(['user:id,name,email', 'product:id,name,slug']);
        $approved = $review->status === ReviewStatus::Approved;

        $review->user->notify(new CustomerActivityNotification([
            'type' => $approved ? 'review.approved' : 'review.rejected',
            'title' => $approved ? 'Sua avaliação foi publicada' : 'Sua avaliação precisa de ajustes',
            'message' => $approved
                ? "Sua experiência com {$review->product->name} já está visível para outros clientes."
                : "Revise sua avaliação de {$review->product->name} e envie novamente para moderação.",
            'action_url' => route('store.products.show', $review->product->slug, absolute: false).'#avaliacoes',
            'action_label' => $approved ? 'Ver avaliação' : 'Editar avaliação',
            'tone' => $approved ? 'success' : 'danger',
        ]));
    }

    private function send(
        Order $order,
        string $type,
        string $title,
        string $message,
        string $actionLabel,
        string $tone,
    ): void {
        $order->loadMissing('user:id,name,email');

        $order->user->notify(new CustomerActivityNotification([
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'action_url' => route('store.orders.show', $order, absolute: false),
            'action_label' => $actionLabel,
            'tone' => $tone,
        ]));
    }
}
