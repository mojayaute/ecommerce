<?php

declare(strict_types=1);

use App\Domain\DomainException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(api: __DIR__.'/../routes/api.php')
    ->withMiddleware()
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->dontReport(DomainException::class);

        $exceptions->render(fn (DomainException $exception): JsonResponse => new JsonResponse(array_filter([
            'message' => $exception->getMessage(),
            'code' => $exception->errorCode(),
            'details' => $exception->details(),
        ]), $exception->status()));

        $exceptions->render(fn (UniqueConstraintViolationException $exception): JsonResponse => new JsonResponse([
            'message' => 'Another request changed the same data at the same time. Please try again.',
            'code' => 'conflict',
        ], 409));
    })->create();
