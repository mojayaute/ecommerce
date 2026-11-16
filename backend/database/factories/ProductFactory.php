<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Product> */
final class ProductFactory extends Factory
{
    private static int $sequence = 0;

    protected $model = Product::class;

    public function definition(): array
    {
        $number = ++self::$sequence;

        return [
            'sku' => sprintf('SKU-%05d', $number),
            'name' => "Product {$number}",
            'description' => null,
            'category_id' => CategoryFactory::new(),
            'price' => '10.00',
            'stock' => 10,
            'weight_kg' => '1.000',
        ];
    }
}
