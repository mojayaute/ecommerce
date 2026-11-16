<?php

declare(strict_types=1);

use App\Domain\DomainException;
use App\Domain\Ordering\CheckoutData;
use App\Domain\Ordering\CheckoutItem;
use App\Domain\Ordering\PlaceOrder;
use App\Models\Product;
use BcMath\Number;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Str;

require __DIR__.'/../../vendor/autoload.php';

$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$product = Product::query()->findOrFail((int) $_SERVER['argv'][1]);

time_sleep_until((float) $_SERVER['argv'][2]);

try {
    $app->make(PlaceOrder::class)->handle(new CheckoutData(
        customerName: 'Concurrent Customer',
        customerEmail: 'concurrent@example.com',
        items: [new CheckoutItem($product->id, 1)],
        expectedTotal: new Number($product->price),
        idempotencyKey: (string) Str::uuid(),
    ));

    echo 'placed';
} catch (DomainException $exception) {
    echo $exception->errorCode();
}
