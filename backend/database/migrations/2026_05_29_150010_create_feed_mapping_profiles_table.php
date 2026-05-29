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
        Schema::create('feed_mapping_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('partner_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('provider');
            $table->string('source_format')->default('csv');
            $table->string('source_encoding')->default('utf-8');
            $table->string('delimiter', 8)->nullable();
            $table->string('enclosure', 8)->nullable();
            $table->string('decimal_separator', 4)->default('.');
            $table->string('thousands_separator', 4)->nullable();
            $table->string('currency', 3)->default('EUR');
            $table->string('locale', 12)->default('nl_NL');
            $table->string('timezone')->default('Europe/Amsterdam');
            $table->string('row_selector')->nullable();
            $table->boolean('first_row_is_header')->default(true);
            $table->boolean('is_template')->default(false);
            $table->boolean('is_active')->default(true);
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'slug'], 'feed_mapping_profiles_provider_slug_unique');
            $table->index(['provider', 'source_format']);
            $table->index(['site_id', 'partner_id']);
            $table->index(['is_template', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feed_mapping_profiles');
    }
};
