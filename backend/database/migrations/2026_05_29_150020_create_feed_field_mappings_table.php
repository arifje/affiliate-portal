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
        Schema::create('feed_field_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('feed_mapping_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('canonical_field_id')->constrained()->cascadeOnDelete();
            $table->string('source_field')->nullable();
            $table->text('source_path')->nullable();
            $table->json('fallback_fields')->nullable();
            $table->text('default_value')->nullable();
            $table->string('transform_type')->default('copy');
            $table->json('transform_config')->nullable();
            $table->boolean('is_required')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(
                ['feed_mapping_profile_id', 'canonical_field_id'],
                'feed_field_mappings_profile_field_unique'
            );
            $table->index(['source_field']);
            $table->index(['transform_type']);
            $table->index(['sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feed_field_mappings');
    }
};
