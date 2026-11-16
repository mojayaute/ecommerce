<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Domain\Catalog\PlainText;
use App\Domain\Ordering\CheckoutData;
use App\Domain\Ordering\CheckoutItem;
use BcMath\Number;
use Illuminate\Foundation\Http\FormRequest;
use LogicException;

final class PlaceOrderRequest extends FormRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'idempotency_key' => ['bail', 'required', 'uuid'],
            'customer' => ['required', 'array:name,email'],
            'customer.name' => ['bail', 'required', 'string', 'max:255', new PlainText],
            'customer.email' => ['bail', 'required', 'string', 'max:255', 'email:rfc'],
            'items' => ['bail', 'required', 'array', 'min:1', 'max:50'],
            'items.*' => ['array:product_id,quantity'],
            'items.*.product_id' => ['bail', 'required', 'integer', 'min:1', 'distinct'],
            'items.*.quantity' => ['bail', 'required', 'integer', 'min:1', 'max:100'],
            'expected_total' => ['bail', 'required', 'numeric', 'decimal:0,2', 'gt:0'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['idempotency_key' => 'Idempotency-Key header'];
    }

    public function toData(): CheckoutData
    {
        $items = [];

        foreach (array_keys($this->array('items')) as $index) {
            $items[] = new CheckoutItem($this->integer("items.{$index}.product_id"), $this->integer("items.{$index}.quantity"));
        }

        $expectedTotal = $this->string('expected_total')->toString();

        if (! is_numeric($expectedTotal)) {
            throw new LogicException('The request was validated without a numeric total.');
        }

        return new CheckoutData(
            customerName: $this->string('customer.name')->trim()->toString(),
            customerEmail: $this->string('customer.email')->trim()->toString(),
            items: $items,
            expectedTotal: new Number($expectedTotal),
            idempotencyKey: $this->string('idempotency_key')->toString(),
        );
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['idempotency_key' => $this->header('Idempotency-Key')]);
    }
}
