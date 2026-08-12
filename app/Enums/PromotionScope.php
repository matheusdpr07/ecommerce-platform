<?php

namespace App\Enums;

enum PromotionScope: string
{
    case AllProducts = 'all_products';
    case Category = 'category';
    case Brand = 'brand';
    case Product = 'product';
}
