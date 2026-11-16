<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Http\JsonResponse;
use Throwable;

final class HealthController
{
    public function __invoke(ConnectionInterface $database): JsonResponse
    {
        try {
            $database->select('select 1');
        } catch (Throwable) {
            return new JsonResponse(['status' => 'degraded', 'database' => 'unreachable'], JsonResponse::HTTP_SERVICE_UNAVAILABLE);
        }

        return new JsonResponse(['status' => 'ok', 'database' => 'ok']);
    }
}
