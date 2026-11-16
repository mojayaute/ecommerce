<?php

declare(strict_types=1);

namespace Tests\Feature\Catalog;

use Database\Factories\CategoryFactory;
use Database\Factories\ProductFactory;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class CatalogDatabaseConstraintsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    #[DataProvider('valuesTheDatabaseRejects')]
    public function the_database_rejects_invalid_values_even_when_validation_is_bypassed(string $column, int|string $value): void
    {
        $product = ProductFactory::new()->createOne();

        $this->expectException(QueryException::class);

        DB::table('products')->where('id', $product->id)->update([$column => $value]);
    }

    /** @return iterable<string, array{string, int|string}> */
    public static function valuesTheDatabaseRejects(): iterable
    {
        yield 'negative stock' => ['stock', -1];
        yield 'zero price' => ['price', '0'];
        yield 'zero weight' => ['weight_kg', '0'];
    }

    #[Test]
    public function the_database_rejects_a_duplicated_sku(): void
    {
        ProductFactory::new()->createOne(['sku' => 'RS-001']);

        $this->expectException(UniqueConstraintViolationException::class);

        ProductFactory::new()->createOne(['sku' => 'RS-001']);
    }

    #[Test]
    public function the_database_rejects_a_category_that_differs_only_in_letter_case(): void
    {
        CategoryFactory::new()->createOne(['name' => 'Footwear']);

        $this->expectException(UniqueConstraintViolationException::class);

        CategoryFactory::new()->createOne(['name' => 'FOOTWEAR']);
    }
}
