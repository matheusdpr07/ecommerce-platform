<?php

namespace App\Enums;

enum StockMovementReason: string
{
    case Initial = 'initial';
    case ManualAdjustment = 'manual_adjustment';
    case Restock = 'restock';
    case Sale = 'sale';
}
