<?php

declare(strict_types=1);

namespace App\Domain\Ordering;

use App\Domain\Payment\Charge;
use App\Domain\Payment\PaymentGateway;
use App\Models\Order;
use App\Models\Product;
use BcMath\Number;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Str;

final readonly class PlaceOrder
{
    private const CURRENCY = 'USD';

    public function __construct(
        private ConnectionInterface $database,
        private PaymentGateway $payments,
    ) {}

    public function handle(CheckoutData $checkout): Order
    {
        return $this->database->transaction(function () use ($checkout): Order {
            $this->database->select('SELECT pg_advisory_xact_lock(hashtextextended(?, 0))', [$checkout->idempotencyKey]);

            $existing = Order::query()->where('idempotency_key', $checkout->idempotencyKey)->first();

            if ($existing !== null) {
                return $existing->load('items');
            }

            $products = $this->lockProducts($checkout->items);
            $lines = [];
            $total = new Number('0');

            foreach ($checkout->items as $item) {
                $product = $products[$item->productId];

                if ($product->stock < $item->quantity) {
                    throw new InsufficientStock($product, $item->quantity);
                }

                $lineTotal = (new Number($product->price))->mul($item->quantity);
                $total = $total->add($lineTotal);

                $lines[] = [
                    'product_id' => $product->id,
                    'product_sku' => $product->sku,
                    'product_name' => $product->name,
                    'unit_price' => $product->price,
                    'quantity' => $item->quantity,
                    'line_total' => (string) $lineTotal,
                ];
            }

            if ($total->compare($checkout->expectedTotal) !== 0) {
                throw new PriceChanged((string) $total);
            }

            $receipt = $this->payments->charge(
                new Charge((string) $total, self::CURRENCY, $checkout->customerEmail, $checkout->idempotencyKey),
            );

            $order = Order::query()->create([
                'reference' => 'ORD-'.now()->format('Ymd').'-'.Str::upper(Str::random(6)),
                'idempotency_key' => $checkout->idempotencyKey,
                'customer_name' => $checkout->customerName,
                'customer_email' => $checkout->customerEmail,
                'status' => OrderStatus::Paid,
                'total' => (string) $total,
                'payment_reference' => $receipt->reference,
            ]);

            $order->items()->createMany($lines);

            foreach ($checkout->items as $item) {
                $products[$item->productId]->decrement('stock', $item->quantity);
            }

            return $order->load('items');
        });
    }

    /**
     * @param  list<CheckoutItem>  $items
     * @return array<int, Product>
     */
    private function lockProducts(array $items): array
    {
        $ids = array_map(static fn (CheckoutItem $item): int => $item->productId, $items);

        $products = [];

        foreach (Product::query()->whereKey($ids)->orderBy('id')->lockForUpdate()->get() as $product) {
            $products[$product->id] = $product;
        }

        $missing = array_values(array_diff($ids, array_keys($products)));

        if ($missing !== []) {
            throw new ProductUnavailable($missing);
        }

        return $products;
    }
}
