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
        Schema::create('canonical_fields', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('field_group');
            $table->string('label');
            $table->text('description')->nullable();
            $table->string('data_type');
            $table->string('target_column')->nullable();
            $table->string('metadata_path')->nullable();
            $table->boolean('is_required')->default(false);
            $table->boolean('is_searchable')->default(false);
            $table->boolean('is_filterable')->default(false);
            $table->boolean('is_variant')->default(false);
            $table->json('validation_rules')->nullable();
            $table->json('options')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['field_group', 'is_active']);
            $table->index(['target_column']);
            $table->index(['sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('canonical_fields');
    }
};
