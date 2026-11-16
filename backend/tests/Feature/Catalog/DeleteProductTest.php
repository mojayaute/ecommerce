<?php

declare(strict_types=1);

namespace Tests\Feature\Catalog;

use Database\Factories\ProductFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class DeleteProductTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_soft_deletes_the_product_and_keeps_it_for_history(): void
    {
        $product = ProductFactory::new()->createOne();

        $this->deleteJson("/api/v1/admin/products/{$product->id}")
            ->assertNoContent();

        $this->assertSoftDeleted('products', ['id' => $product->id]);
        $this->getJson("/api/v1/admin/products/{$product->id}")->assertNotFound();
    }

    #[Test]
    public function it_returns_not_found_for_an_unknown_product(): void
    {
        $this->deleteJson('/api/v1/admin/products/999999')
            ->assertNotFound();
    }

    #[Test]
    public function it_returns_not_found_for_a_non_numeric_identifier(): void
    {
        $this->deleteJson('/api/v1/admin/products/abc')
            ->assertNotFound();
    }
}
