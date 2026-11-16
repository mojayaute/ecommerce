<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Domain\Catalog\ProductData;
use App\Domain\Catalog\ProductRules;
use App\Domain\Catalog\Sku;
use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SaveProductRequest extends FormRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $rules = ProductRules::fields();
        $rules['sku'][] = Rule::unique('products', 'sku')->ignore($this->currentProductId());

        return $rules;
    }

    public function toData(): ProductData
    {
        return new ProductData(
            sku: $this->string('sku')->toString(),
            name: $this->string('name')->toString(),
            description: $this->filled('description') ? $this->string('description')->toString() : null,
            categoryName: $this->string('category')->toString(),
            price: $this->string('price')->toString(),
            stock: $this->integer('stock'),
            weightKg: $this->string('weight_kg')->toString(),
        );
    }

    protected function prepareForValidation(): void
    {
        $sku = $this->input('sku');

        if (is_string($sku)) {
            $this->merge(['sku' => Sku::normalize($sku)]);
        }
    }

    private function currentProductId(): ?int
    {
        $product = $this->route('product');

        return $product instanceof Product ? $product->id : null;
    }
}
