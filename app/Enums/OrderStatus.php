<?php

namespace App\Enums;

enum OrderStatus: string
{
    case PendingPayment = 'pending_payment';
    case Paid = 'paid';
    case PaymentFailed = 'payment_failed';
    case Cancelled = 'cancelled';
    case PartiallyRefunded = 'partially_refunded';
    case Refunded = 'refunded';
    case ChargedBack = 'charged_back';

    public function label(): string
    {
        return match ($this) {
            self::PendingPayment => 'Aguardando pagamento',
            self::Paid => 'Pago',
            self::PaymentFailed => 'Pagamento recusado',
            self::Cancelled => 'Cancelado',
            self::PartiallyRefunded => 'Reembolsado parcialmente',
            self::Refunded => 'Reembolsado',
            self::ChargedBack => 'Contestado',
        };
    }

    public function canReceivePayment(): bool
    {
        return $this === self::PendingPayment;
    }
}
