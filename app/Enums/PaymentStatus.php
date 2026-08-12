<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Approved = 'approved';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
    case Expired = 'expired';
    case PartiallyRefunded = 'partially_refunded';
    case Refunded = 'refunded';
    case ChargedBack = 'charged_back';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Aguardando pagamento',
            self::Processing => 'Em processamento',
            self::Approved => 'Pago',
            self::Failed => 'Falhou',
            self::Cancelled => 'Cancelado',
            self::Expired => 'Expirado',
            self::PartiallyRefunded => 'Reembolsado parcialmente',
            self::Refunded => 'Reembolsado',
            self::ChargedBack => 'Contestado',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [
            self::Failed,
            self::Cancelled,
            self::Expired,
            self::Refunded,
            self::ChargedBack,
        ], true);
    }
}
