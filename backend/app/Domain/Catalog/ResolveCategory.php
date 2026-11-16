<?php

declare(strict_types=1);

namespace App\Domain\Catalog;

use App\Models\Category;
use Illuminate\Database\ConnectionInterface;

final readonly class ResolveCategory
{
    public function __construct(private ConnectionInterface $database) {}

    public function handle(string $name): Category
    {
        $name = trim($name);

        $this->database->insert(
            'INSERT INTO categories (name, created_at, updated_at) VALUES (?, now(), now()) ON CONFLICT ((lower(name))) DO NOTHING',
            [$name],
        );

        return Category::query()->whereRaw('lower(name) = lower(?)', [$name])->firstOrFail();
    }
}
