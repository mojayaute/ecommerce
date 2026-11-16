<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Database\ConnectionInterface;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

final class HealthCheckTest extends TestCase
{
    #[Test]
    public function it_reports_healthy_when_the_database_is_reachable(): void
    {
        $this->getJson('/api/health')
            ->assertOk()
            ->assertExactJson(['status' => 'ok', 'database' => 'ok']);
    }

    #[Test]
    public function it_reports_unavailable_when_the_database_is_unreachable(): void
    {
        $this->mock(ConnectionInterface::class, function ($mock): void {
            $mock->shouldReceive('select')->andThrow(new RuntimeException('connection refused'));
        });

        $this->getJson('/api/health')
            ->assertServiceUnavailable()
            ->assertExactJson(['status' => 'degraded', 'database' => 'unreachable']);
    }
}
