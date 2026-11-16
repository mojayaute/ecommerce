<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class OrderResource extends JsonResource
{
    public function __construct(Order $order)
    {
        parent::__construct($order);
    }

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $order = $this->resource;

        return [
            'id' => $order->id,
            'reference' => $order->reference,
            'status' => $order->status->value,
            'customer' => ['name' => $order->customer_name, 'email' => $order->customer_email],
            'total' => $order->total,
            'payment_reference' => $order->payment_reference,
            'items' => $order->items->sortBy('id')->values()->map(static fn (OrderItem $item): array => [
                'product_id' => $item->product_id,
                'sku' => $item->product_sku,
                'name' => $item->product_name,
                'unit_price' => $item->unit_price,
                'quantity' => $item->quantity,
                'line_total' => $item->line_total,
            ]),
            'created_at' => $order->created_at?->toIso8601String(),
        ];
    }
}
