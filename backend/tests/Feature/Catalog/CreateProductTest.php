<?php

declare(strict_types=1);

namespace Tests\Feature\Catalog;

use App\Models\Product;
use Database\Factories\CategoryFactory;
use Database\Factories\ProductFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class CreateProductTest extends TestCase
{
    use RefreshDatabase;

    private const ENDPOINT = '/api/v1/admin/products';

    #[Test]
    public function it_creates_a_product_and_returns_it_with_its_category(): void
    {
        $this->postJson(self::ENDPOINT, $this->payload())
            ->assertCreated()
            ->assertJsonPath('data.sku', 'RS-001')
            ->assertJsonPath('data.name', 'Running Shoes')
            ->assertJsonPath('data.description', 'Lightweight running shoes for daily training')
            ->assertJsonPath('data.category.name', 'Footwear')
            ->assertJsonPath('data.price', '89.99')
            ->assertJsonPath('data.stock', 150)
            ->assertJsonPath('data.weight_kg', '0.350');

        $this->assertDatabaseHas('products', ['sku' => 'RS-001', 'stock' => 150]);
    }

    #[Test]
    public function it_accepts_a_product_without_description(): void
    {
        $this->postJson(self::ENDPOINT, $this->payload(['description' => null]))
            ->assertCreated()
            ->assertJsonPath('data.description', null);
    }

    #[Test]
    public function it_stores_the_sku_in_uppercase_without_surrounding_spaces(): void
    {
        $this->postJson(self::ENDPOINT, $this->payload(['sku' => '  rs-001 ']))
            ->assertCreated()
            ->assertJsonPath('data.sku', 'RS-001');
    }

    #[Test]
    public function it_reuses_an_existing_category_regardless_of_letter_case(): void
    {
        $footwear = CategoryFactory::new()->createOne(['name' => 'Footwear']);

        $this->postJson(self::ENDPOINT, $this->payload(['category' => 'FOOTWEAR']))
            ->assertCreated()
            ->assertJsonPath('data.category.id', $footwear->id)
            ->assertJsonPath('data.category.name', 'Footwear');

        $this->assertDatabaseCount('categories', 1);
    }

    #[Test]
    public function it_creates_the_category_when_it_does_not_exist_yet(): void
    {
        $this->postJson(self::ENDPOINT, $this->payload(['category' => '  Pets  ']))
            ->assertCreated()
            ->assertJsonPath('data.category.name', 'Pets');

        $this->assertDatabaseHas('categories', ['name' => 'Pets']);
    }

    #[Test]
    public function it_rejects_a_sku_that_is_already_in_use(): void
    {
        ProductFactory::new()->createOne(['sku' => 'RS-001']);

        $this->postJson(self::ENDPOINT, $this->payload(['sku' => 'rs-001']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('sku');

        $this->assertDatabaseCount('products', 1);
    }

    #[Test]
    public function it_rejects_a_sku_that_belongs_to_a_deleted_product(): void
    {
        ProductFactory::new()->createOne(['sku' => 'RS-001'])->delete();

        $this->postJson(self::ENDPOINT, $this->payload())
            ->assertUnprocessable()
            ->assertJsonValidationErrors('sku');
    }

    #[Test]
    public function it_reports_a_conflict_when_another_request_takes_the_sku_first(): void
    {
        $category = CategoryFactory::new()->createOne(['name' => 'Footwear']);

        Product::creating(function () use ($category): void {
            Product::withoutEvents(fn () => ProductFactory::new()->for($category)->createOne(['sku' => 'RS-001']));
        });

        $this->postJson(self::ENDPOINT, $this->payload())
            ->assertConflict()
            ->assertJsonPath('code', 'conflict');
    }

    /** @param array<string, mixed> $overrides */
    #[Test]
    #[DataProvider('invalidPayloads')]
    public function it_rejects_invalid_product_data(array $overrides, string $field): void
    {
        $this->postJson(self::ENDPOINT, $this->payload($overrides))
            ->assertUnprocessable()
            ->assertJsonValidationErrors($field);

        $this->assertDatabaseCount('products', 0);
        $this->assertDatabaseCount('categories', 0);
    }

    /** @return iterable<string, array{array<string, mixed>, string}> */
    public static function invalidPayloads(): iterable
    {
        yield 'missing name' => [['name' => null], 'name'];
        yield 'whitespace-only name' => [['name' => '     '], 'name'];
        yield 'name longer than 255 characters' => [['name' => str_repeat('a', 256)], 'name'];
        yield 'name with html markup' => [['name' => "<script>alert('xss')</script>"], 'name'];
        yield 'name with sql injection' => [['name' => "Robert'); DROP TABLE products;--"], 'name'];
        yield 'description with html markup' => [['description' => '<img src=x onerror=alert(1)>'], 'description'];
        yield 'missing sku' => [['sku' => null], 'sku'];
        yield 'sku with inner spaces' => [['sku' => 'RS 001'], 'sku'];
        yield 'sku with symbols' => [['sku' => 'RS_001!'], 'sku'];
        yield 'missing category' => [['category' => ''], 'category'];
        yield 'missing price' => [['price' => null], 'price'];
        yield 'price written as text' => [['price' => 'free'], 'price'];
        yield 'price with currency symbol' => [['price' => '$29.99'], 'price'];
        yield 'zero price' => [['price' => 0], 'price'];
        yield 'negative price' => [['price' => -1], 'price'];
        yield 'price with three decimals' => [['price' => '9.999'], 'price'];
        yield 'missing stock' => [['stock' => null], 'stock'];
        yield 'negative stock' => [['stock' => -5], 'stock'];
        yield 'fractional stock' => [['stock' => 1.5], 'stock'];
        yield 'missing weight' => [['weight_kg' => null], 'weight_kg'];
        yield 'zero weight' => [['weight_kg' => 0], 'weight_kg'];
        yield 'weight with four decimals' => [['weight_kg' => '0.1234'], 'weight_kg'];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'sku' => 'RS-001',
            'name' => 'Running Shoes',
            'description' => 'Lightweight running shoes for daily training',
            'category' => 'Footwear',
            'price' => '89.99',
            'stock' => 150,
            'weight_kg' => '0.35',
        ], $overrides);
    }
}
