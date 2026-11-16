<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Payment\FakePaymentGateway;
use App\Domain\Payment\PaymentGateway;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    public array $bindings = [
        PaymentGateway::class => FakePaymentGateway::class,
    ];

    public function boot(): void
    {
        Model::shouldBeStrict(! $this->app->isProduction());
    }
}
