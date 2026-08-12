<?php

namespace App\Enums;

enum FulfillmentStatus: string
{
    case Pending = 'pending';
    case Preparing = 'preparing';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Aguardando separacao',
            self::Preparing => 'Em separacao',
            self::Shipped => 'Enviado',
            self::Delivered => 'Entregue',
            self::Cancelled => 'Cancelado',
        };
    }

    public function canTransitionTo(self $status): bool
    {
        return match ($this) {
            self::Pending => in_array($status, [self::Preparing, self::Cancelled], true),
            self::Preparing => in_array($status, [self::Shipped, self::Cancelled], true),
            self::Shipped => $status === self::Delivered,
            self::Delivered, self::Cancelled => false,
        };
    }

    public function next(): ?self
    {
        return match ($this) {
            self::Pending => self::Preparing,
            self::Preparing => self::Shipped,
            self::Shipped => self::Delivered,
            self::Delivered, self::Cancelled => null,
        };
    }
}
