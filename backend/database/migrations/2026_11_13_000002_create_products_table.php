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
        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->string('sku', 50)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('category_id')->index()->constrained()->restrictOnDelete();
            $table->decimal('price', 10, 2);
            $table->integer('stock');
            $table->decimal('weight_kg', 8, 3);
            $table->timestamps();
            $table->softDeletes();
        });

        DB::statement('ALTER TABLE products ADD CONSTRAINT products_price_positive CHECK (price > 0)');
        DB::statement('ALTER TABLE products ADD CONSTRAINT products_stock_non_negative CHECK (stock >= 0)');
        DB::statement('ALTER TABLE products ADD CONSTRAINT products_weight_positive CHECK (weight_kg > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
