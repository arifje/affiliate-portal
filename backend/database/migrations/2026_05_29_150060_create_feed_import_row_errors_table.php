<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('feed_import_row_errors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('feed_import_batch_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('row_number');
            $table->string('external_id')->nullable();
            $table->json('source_payload')->nullable();
            $table->json('mapped_payload')->nullable();
            $table->json('errors');
            $table->timestamp('created_at')->nullable();

            $table->index(['feed_import_batch_id', 'row_number'], 'feed_import_row_errors_batch_row_index');
            $table->index(['external_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feed_import_row_errors');
    }
};
