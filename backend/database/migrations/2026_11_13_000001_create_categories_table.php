<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 100);
            $table->timestamps();
        });

        DB::statement('CREATE UNIQUE INDEX categories_name_lower_unique ON categories (lower(name))');
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
