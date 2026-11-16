<?php

declare(strict_types=1);

namespace Tests\Feature\Catalog;

use Database\Factories\ProductFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class UpdateProductTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_updates_every_editable_field(): void
    {
        $product = ProductFactory::new()->createOne(['sku' => 'RS-001']);

        $this->putJson("/api/v1/admin/products/{$product->id}", [
            'sku' => 'RS-002',
            'name' => 'Trail Running Shoes',
            'description' => 'Now with better arch support',
            'category' => 'Outdoors',
            'price' => '94.99',
            'stock' => 120,
            'weight_kg' => '0.4',
        ])
            ->assertOk()
            ->assertJsonPath('data.id', $product->id)
            ->assertJsonPath('data.sku', 'RS-002')
            ->assertJsonPath('data.name', 'Trail Running Shoes')
            ->assertJsonPath('data.description', 'Now with better arch support')
            ->assertJsonPath('data.category.name', 'Outdoors')
            ->assertJsonPath('data.price', '94.99')
            ->assertJsonPath('data.stock', 120)
            ->assertJsonPath('data.weight_kg', '0.400');
    }

    #[Test]
    public function it_keeps_its_own_sku_without_a_uniqueness_error(): void
    {
        $product = ProductFactory::new()->createOne(['sku' => 'RS-001', 'price' => '89.99']);

        $this->putJson("/api/v1/admin/products/{$product->id}", $this->payloadFor('RS-001', ['price' => '79.99']))
            ->assertOk()
            ->assertJsonPath('data.price', '79.99');
    }

    #[Test]
    public function it_rejects_the_sku_of_another_product(): void
    {
        ProductFactory::new()->createOne(['sku' => 'BS-021']);
        $product = ProductFactory::new()->createOne(['sku' => 'RS-001']);

        $this->putJson("/api/v1/admin/products/{$product->id}", $this->payloadFor('bs-021'))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('sku');
    }

    #[Test]
    public function it_rejects_invalid_values_with_the_same_rules_as_creation(): void
    {
        $product = ProductFactory::new()->createOne(['sku' => 'RS-001', 'stock' => 10]);

        $this->putJson("/api/v1/admin/products/{$product->id}", $this->payloadFor('RS-001', ['stock' => -5]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('stock');

        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 10]);
    }

    #[Test]
    public function it_returns_not_found_for_a_deleted_product(): void
    {
        $product = ProductFactory::new()->createOne(['sku' => 'RS-001']);
        $product->delete();

        $this->putJson("/api/v1/admin/products/{$product->id}", $this->payloadFor('RS-001'))
            ->assertNotFound();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payloadFor(string $sku, array $overrides = []): array
    {
        return array_merge([
            'sku' => $sku,
            'name' => 'Running Shoes',
            'description' => null,
            'category' => 'Footwear',
            'price' => '89.99',
            'stock' => 150,
            'weight_kg' => '0.35',
        ], $overrides);
    }
}
