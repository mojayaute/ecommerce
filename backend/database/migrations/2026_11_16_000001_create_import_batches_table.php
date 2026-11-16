<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_batches', function (Blueprint $table): void {
            $table->id();
            $table->string('original_filename');
            $table->integer('total_rows')->default(0);
            $table->integer('created_count')->default(0);
            $table->integer('updated_count')->default(0);
            $table->integer('rejected_count')->default(0);
            $table->integer('skipped_count')->default(0);
            $table->timestamps();
        });

        Schema::create('import_rows', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('import_batch_id')->constrained()->cascadeOnDelete();
            $table->integer('line_number');
            $table->jsonb('raw_data');
            $table->string('status', 20);
            $table->jsonb('issues');
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();

            $table->unique(['import_batch_id', 'line_number']);
            $table->index(['import_batch_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_rows');
        Schema::dropIfExists('import_batches');
    }
};
