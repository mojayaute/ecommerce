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
        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->string('reference', 30)->unique();
            $table->uuid('idempotency_key')->unique();
            $table->string('customer_name');
            $table->string('customer_email');
            $table->string('status', 20);
            $table->decimal('total', 12, 2);
            $table->string('payment_reference', 100);
            $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->index()->constrained()->restrictOnDelete();
            $table->string('product_sku', 50);
            $table->string('product_name');
            $table->decimal('unit_price', 10, 2);
            $table->integer('quantity');
            $table->decimal('line_total', 12, 2);
        });

        DB::statement('ALTER TABLE orders ADD CONSTRAINT orders_total_positive CHECK (total > 0)');
        DB::statement('ALTER TABLE order_items ADD CONSTRAINT order_items_quantity_positive CHECK (quantity > 0)');
        DB::statement('ALTER TABLE order_items ADD CONSTRAINT order_items_line_total_matches CHECK (line_total = unit_price * quantity)');
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
