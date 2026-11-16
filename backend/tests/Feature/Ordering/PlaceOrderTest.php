<?php

declare(strict_types=1);

namespace Tests\Feature\Ordering;

use App\Domain\Payment\Charge;
use App\Domain\Payment\PaymentGateway;
use App\Domain\Payment\PaymentReceipt;
use App\Models\Product;
use Database\Factories\ProductFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class PlaceOrderTest extends TestCase
{
    use RefreshDatabase;

    private const ENDPOINT = '/api/v1/orders';

    private Product $shoes;

    private Product $mug;

    protected function setUp(): void
    {
        parent::setUp();

        $this->shoes = ProductFactory::new()->createOne(['sku' => 'RS-001', 'name' => 'Running Shoes', 'price' => '89.99', 'stock' => 5]);
        $this->mug = ProductFactory::new()->createOne(['sku' => 'TM-029', 'name' => 'Travel Mug', 'price' => '16.99', 'stock' => 3]);
    }

    #[Test]
    public function it_places_an_order_charges_the_server_total_and_reduces_stock(): void
    {
        $gateway = $this->recordingGateway();

        $this->checkout()
            ->assertCreated()
            ->assertJsonPath('data.status', 'paid')
            ->assertJsonPath('data.total', '196.97')
            ->assertJsonPath('data.customer.name', 'Ada Lovelace')
            ->assertJsonPath('data.customer.email', 'ada@example.com')
            ->assertJsonPath('data.payment_reference', 'test-payment-1')
            ->assertJsonPath('data.items.0.sku', 'RS-001')
            ->assertJsonPath('data.items.0.name', 'Running Shoes')
            ->assertJsonPath('data.items.0.unit_price', '89.99')
            ->assertJsonPath('data.items.0.quantity', 2)
            ->assertJsonPath('data.items.0.line_total', '179.98')
            ->assertJsonPath('data.items.1.sku', 'TM-029')
            ->assertJsonPath('data.reference', fn (string $reference): bool => preg_match('/^ORD-\d{8}-[A-Z0-9]{6}$/', $reference) === 1);

        $this->assertCount(1, $gateway->charges);
        $this->assertSame('196.97', $gateway->charges[0]->amount);
        $this->assertSame(3, $this->shoes->fresh()?->stock);
        $this->assertSame(2, $this->mug->fresh()?->stock);
    }

    #[Test]
    public function it_keeps_what_the_customer_bought_even_if_the_product_changes_later(): void
    {
        $this->checkout()->assertCreated();

        $this->shoes->update(['name' => 'Running Shoes V2', 'price' => '120.00']);

        $this->assertDatabaseHas('order_items', ['product_sku' => 'RS-001', 'product_name' => 'Running Shoes', 'unit_price' => '89.99']);
    }

    #[Test]
    public function it_adds_money_without_floating_point_errors(): void
    {
        $cheap = ProductFactory::new()->createOne(['price' => '0.10', 'stock' => 10]);
        $cheaper = ProductFactory::new()->createOne(['price' => '0.20', 'stock' => 10]);

        $this->checkout([
            'items' => [['product_id' => $cheap->id, 'quantity' => 1], ['product_id' => $cheaper->id, 'quantity' => 1]],
            'expected_total' => '0.30',
        ])
            ->assertCreated()
            ->assertJsonPath('data.total', '0.30');
    }

    #[Test]
    public function it_returns_the_original_order_when_a_request_is_retried_with_the_same_key(): void
    {
        $key = (string) Str::uuid();

        $first = $this->checkout(key: $key)->assertCreated();

        $this->checkout(key: $key)
            ->assertOk()
            ->assertJsonPath('data.reference', $first->json('data.reference'));

        $this->assertDatabaseCount('orders', 1);
        $this->assertSame(3, $this->shoes->fresh()?->stock);
    }

    #[Test]
    #[DataProvider('invalidKeys')]
    public function it_requires_a_valid_idempotency_key(?string $key): void
    {
        $this->postJson(self::ENDPOINT, $this->payload(), $key === null ? [] : ['Idempotency-Key' => $key])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('idempotency_key');
    }

    /** @return iterable<string, array{string|null}> */
    public static function invalidKeys(): iterable
    {
        yield 'missing' => [null];
        yield 'not a uuid' => ['retry-1'];
    }

    #[Test]
    public function it_rejects_the_order_when_there_is_not_enough_stock(): void
    {
        $this->checkout(['items' => [['product_id' => $this->shoes->id, 'quantity' => 6]], 'expected_total' => '539.94'])
            ->assertConflict()
            ->assertJsonPath('code', 'insufficient_stock')
            ->assertJsonPath('details.sku', 'RS-001')
            ->assertJsonPath('details.available', 5);

        $this->assertNothingWasOrdered();
    }

    #[Test]
    public function it_rejects_the_order_when_a_product_is_no_longer_available(): void
    {
        $this->mug->delete();

        $this->checkout()
            ->assertUnprocessable()
            ->assertJsonPath('code', 'product_unavailable')
            ->assertJsonPath('details.product_ids', [$this->mug->id]);

        $this->assertNothingWasOrdered();
    }

    #[Test]
    public function it_rejects_the_order_when_the_total_changed_since_the_customer_saw_it(): void
    {
        $gateway = $this->recordingGateway();

        $this->checkout(['expected_total' => '180.00'])
            ->assertConflict()
            ->assertJsonPath('code', 'price_changed')
            ->assertJsonPath('details.current_total', '196.97');

        $this->assertSame([], $gateway->charges);
        $this->assertNothingWasOrdered();
    }

    #[Test]
    public function it_saves_nothing_when_the_payment_is_declined(): void
    {
        $this->checkout(['customer' => ['name' => 'Declined Customer', 'email' => 'decline@example.com']])
            ->assertStatus(402)
            ->assertJsonPath('code', 'payment_declined');

        $this->assertNothingWasOrdered();
    }

    /** @param array<string, mixed> $overrides */
    #[Test]
    #[DataProvider('invalidPayloads')]
    public function it_rejects_an_invalid_checkout(array $overrides, string $field): void
    {
        $this->checkout($overrides)
            ->assertUnprocessable()
            ->assertJsonValidationErrors($field);

        $this->assertNothingWasOrdered();
    }

    /** @return iterable<string, array{array<string, mixed>, string}> */
    public static function invalidPayloads(): iterable
    {
        yield 'missing customer name' => [['customer' => ['email' => 'ada@example.com']], 'customer.name'];
        yield 'invalid email' => [['customer' => ['name' => 'Ada', 'email' => 'not-an-email']], 'customer.email'];
        yield 'empty cart' => [['items' => []], 'items'];
        yield 'zero quantity' => [['items' => [['product_id' => 1, 'quantity' => 0]]], 'items.0.quantity'];
        yield 'fractional quantity' => [['items' => [['product_id' => 1, 'quantity' => 1.5]]], 'items.0.quantity'];
        yield 'product id as text' => [['items' => [['product_id' => 'abc', 'quantity' => 1]]], 'items.0.product_id'];
        yield 'same product twice' => [['items' => [['product_id' => 1, 'quantity' => 1], ['product_id' => 1, 'quantity' => 2]]], 'items.0.product_id'];
        yield 'missing expected total' => [['expected_total' => null], 'expected_total'];
        yield 'client tries to set the price' => [['items' => [['product_id' => 1, 'quantity' => 1, 'unit_price' => '0.01']]], 'items.0'];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return TestResponse<JsonResponse>
     */
    private function checkout(array $overrides = [], ?string $key = null): TestResponse
    {
        return $this->postJson(self::ENDPOINT, $this->payload($overrides), ['Idempotency-Key' => $key ?? (string) Str::uuid()]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'customer' => ['name' => 'Ada Lovelace', 'email' => 'ada@example.com'],
            'items' => [
                ['product_id' => $this->shoes->id, 'quantity' => 2],
                ['product_id' => $this->mug->id, 'quantity' => 1],
            ],
            'expected_total' => '196.97',
        ], $overrides);
    }

    private function recordingGateway(): RecordingGateway
    {
        $gateway = new RecordingGateway;
        $this->app->instance(PaymentGateway::class, $gateway);

        return $gateway;
    }

    private function assertNothingWasOrdered(): void
    {
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
        $this->assertSame(5, $this->shoes->fresh()?->stock);
        $this->assertSame(3, $this->mug->fresh()?->stock);
    }
}

final class RecordingGateway implements PaymentGateway
{
    /** @var list<Charge> */
    public array $charges = [];

    public function charge(Charge $charge): PaymentReceipt
    {
        $this->charges[] = $charge;

        return new PaymentReceipt('test-payment-'.count($this->charges));
    }
}
