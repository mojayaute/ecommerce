<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Ordering\OrderStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property OrderStatus $status
 * @property string $total
 */
final class Order extends Model
{
    protected $fillable = [
        'reference',
        'idempotency_key',
        'customer_name',
        'customer_email',
        'status',
        'total',
        'payment_reference',
    ];

    /** @return HasMany<OrderItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'total' => 'decimal:2',
        ];
    }
}
