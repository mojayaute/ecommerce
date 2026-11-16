<?php

declare(strict_types=1);

namespace Tests\Feature\Catalog;

use Database\Factories\CategoryFactory;
use Database\Factories\ProductFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ListProductsTest extends TestCase
{
    use RefreshDatabase;

    private const ENDPOINT = '/api/v1/admin/products';

    #[Test]
    public function it_lists_products_ordered_by_name_with_their_category(): void
    {
        $electronics = CategoryFactory::new()->createOne(['name' => 'Electronics']);
        ProductFactory::new()->for($electronics)->createOne(['name' => 'Wireless Mouse']);
        ProductFactory::new()->for($electronics)->createOne(['name' => 'Bluetooth Speaker']);
        ProductFactory::new()->for($electronics)->createOne(['name' => 'USB Hub']);

        $this->getJson(self::ENDPOINT)
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Bluetooth Speaker')
            ->assertJsonPath('data.1.name', 'USB Hub')
            ->assertJsonPath('data.2.name', 'Wireless Mouse')
            ->assertJsonPath('data.0.category.name', 'Electronics');
    }

    #[Test]
    public function it_excludes_deleted_products(): void
    {
        ProductFactory::new()->createOne(['name' => 'Kept']);
        ProductFactory::new()->createOne(['name' => 'Removed'])->delete();

        $this->getJson(self::ENDPOINT)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Kept');
    }

    #[Test]
    public function it_paginates_twenty_products_per_page(): void
    {
        ProductFactory::new()->count(21)->create();

        $this->getJson(self::ENDPOINT)
            ->assertOk()
            ->assertJsonCount(20, 'data')
            ->assertJsonPath('meta.total', 21)
            ->assertJsonPath('meta.last_page', 2);

        $this->getJson(self::ENDPOINT.'?page=2')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    #[Test]
    public function it_shows_a_single_product_with_its_category(): void
    {
        $category = CategoryFactory::new()->createOne(['name' => 'Footwear']);
        $product = ProductFactory::new()->for($category)->createOne(['sku' => 'RS-001', 'price' => '89.99']);

        $this->getJson(self::ENDPOINT."/{$product->id}")
            ->assertOk()
            ->assertJsonPath('data.sku', 'RS-001')
            ->assertJsonPath('data.price', '89.99')
            ->assertJsonPath('data.category.name', 'Footwear');
    }
}
