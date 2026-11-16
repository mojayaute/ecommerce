<?php

declare(strict_types=1);

use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\V1\Admin\ImportController;
use App\Http\Controllers\Api\V1\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\ProductController;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthController::class);

Route::prefix('v1')->group(function (): void {
    Route::get('products', [ProductController::class, 'index']);
    Route::get('products/{product}', [ProductController::class, 'show'])->whereNumber('product');
    Route::get('categories', [CategoryController::class, 'index']);
    Route::post('orders', [OrderController::class, 'store']);

    Route::prefix('admin')->group(function (): void {
        Route::apiResource('products', AdminProductController::class)
            ->where(['product' => '[0-9]+']);

        Route::apiResource('imports', ImportController::class)
            ->only(['index', 'store', 'show'])
            ->where(['import' => '[0-9]+']);
        Route::get('imports/{import}/rows', [ImportController::class, 'rows'])->whereNumber('import');
    });
});
