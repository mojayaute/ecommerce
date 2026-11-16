<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $unit_price
 * @property string $line_total
 */
final class OrderItem extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'product_id',
        'product_sku',
        'product_name',
        'unit_price',
        'quantity',
        'line_total',
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'quantity' => 'integer',
            'line_total' => 'decimal:2',
        ];
    }
}
