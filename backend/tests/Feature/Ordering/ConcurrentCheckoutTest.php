<?php

declare(strict_types=1);

namespace Tests\Feature\Ordering;

use Database\Factories\ProductFactory;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Process\Process;
use Tests\TestCase;

final class ConcurrentCheckoutTest extends TestCase
{
    use DatabaseTruncation;

    private const BUYERS = 4;

    protected function tearDown(): void
    {
        $this->truncateTablesForAllConnections();

        parent::tearDown();
    }

    #[Test]
    public function it_sells_the_last_unit_only_once_when_customers_pay_at_the_same_time(): void
    {
        $product = ProductFactory::new()->createOne(['price' => '10.00', 'stock' => 1]);
        $startAt = (string) (microtime(true) + 3);

        $buyers = [];

        for ($i = 0; $i < self::BUYERS; $i++) {
            $buyer = new Process([PHP_BINARY, base_path('tests/Support/place-order.php'), (string) $product->id, $startAt]);
            $buyer->start();
            $buyers[] = $buyer;
        }

        $outcomes = [];

        foreach ($buyers as $buyer) {
            $buyer->wait();
            $outcomes[] = trim($buyer->getOutput()).trim($buyer->getErrorOutput());
        }

        sort($outcomes);

        $this->assertSame(['insufficient_stock', 'insufficient_stock', 'insufficient_stock', 'placed'], $outcomes);
        $this->assertSame(0, $product->fresh()?->stock);
        $this->assertDatabaseCount('orders', 1);
    }
}
