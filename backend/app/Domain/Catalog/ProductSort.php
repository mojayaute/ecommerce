<?php

declare(strict_types=1);

namespace App\Domain\Catalog;

enum ProductSort: string
{
    case Relevance = 'relevance';
    case Name = 'name';
    case PriceAsc = 'price_asc';
    case PriceDesc = 'price_desc';
}
