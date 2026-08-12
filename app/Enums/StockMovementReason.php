<?php

namespace App\Enums;

enum StockMovementReason: string
{
    case Initial = 'initial';
    case ManualAdjustment = 'manual_adjustment';
    case Restock = 'restock';
    case Sale = 'sale';
    case OrderReversal = 'order_reversal';

    public function label(): string
    {
        return match ($this) {
            self::Initial => 'Estoque inicial',
            self::ManualAdjustment => 'Ajuste manual',
            self::Restock => 'Reposicao',
            self::Sale => 'Venda',
            self::OrderReversal => 'Reversao de pedido',
        };
    }
}
