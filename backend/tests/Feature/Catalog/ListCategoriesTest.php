<?php

declare(strict_types=1);

namespace Tests\Feature\Catalog;

use Database\Factories\CategoryFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ListCategoriesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_every_category_alphabetically(): void
    {
        $sports = CategoryFactory::new()->createOne(['name' => 'Sports']);
        CategoryFactory::new()->createOne(['name' => 'Electronics']);
        CategoryFactory::new()->createOne(['name' => 'Books']);

        $this->getJson('/api/v1/categories')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.name', 'Books')
            ->assertJsonPath('data.1.name', 'Electronics')
            ->assertJsonPath('data.2', ['id' => $sports->id, 'name' => 'Sports']);
    }
}
