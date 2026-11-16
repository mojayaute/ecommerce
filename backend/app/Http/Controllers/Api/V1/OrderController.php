<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Ordering\PlaceOrder;
use App\Http\Requests\PlaceOrderRequest;
use App\Http\Resources\OrderResource;

final class OrderController
{
    public function store(PlaceOrderRequest $request, PlaceOrder $placeOrder): OrderResource
    {
        return new OrderResource($placeOrder->handle($request->toData()));
    }
}
