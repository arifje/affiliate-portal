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
        if (Schema::hasColumn('feed_import_batches', 'feed_mapping_profile_id')) {
            Schema::table('feed_import_batches', function (Blueprint $table) {
                $table->dropConstrainedForeignId('feed_mapping_profile_id');
            });
        }

        if (Schema::hasColumn('feeds', 'mapping_profile_id')) {
            Schema::table('feeds', function (Blueprint $table) {
                $table->dropConstrainedForeignId('mapping_profile_id');
            });
        }

        Schema::dropIfExists('feed_field_mappings');
        Schema::dropIfExists('feed_mapping_profiles');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('feed_mapping_profiles')) {
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
                $table->json('available_elements')->nullable();
                $table->json('sample_fields')->nullable();
                $table->json('sample_payload')->nullable();
                $table->timestamp('last_analyzed_at')->nullable();
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

        if (! Schema::hasColumn('feeds', 'mapping_profile_id')) {
            Schema::table('feeds', function (Blueprint $table) {
                $table->foreignId('mapping_profile_id')
                    ->nullable()
                    ->constrained('feed_mapping_profiles')
                    ->nullOnDelete();
            });
        }

        if (! Schema::hasTable('feed_field_mappings')) {
            Schema::create('feed_field_mappings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('feed_mapping_profile_id')->constrained()->cascadeOnDelete();
                $table->foreignId('canonical_field_id')->constrained()->cascadeOnDelete();
                $table->string('mapping_action')->default('map');
                $table->string('source_field')->nullable();
                $table->text('source_path')->nullable();
                $table->text('source_sample')->nullable();
                $table->json('fallback_fields')->nullable();
                $table->text('default_value')->nullable();
                $table->string('transform_type')->default('copy');
                $table->json('transform_config')->nullable();
                $table->boolean('is_required')->default(false);
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->unique(
                    ['feed_mapping_profile_id', 'canonical_field_id'],
                    'feed_field_mappings_profile_field_unique'
                );
                $table->index(['mapping_action']);
                $table->index(['source_field']);
                $table->index(['transform_type']);
                $table->index(['sort_order']);
            });
        }

        if (! Schema::hasColumn('feed_import_batches', 'feed_mapping_profile_id')) {
            Schema::table('feed_import_batches', function (Blueprint $table) {
                $table->foreignId('feed_mapping_profile_id')
                    ->nullable()
                    ->after('feed_id')
                    ->constrained()
                    ->nullOnDelete();
            });
        }
    }
};
