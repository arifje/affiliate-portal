<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('feeds', function (Blueprint $table) {
            $table->json('available_elements')->nullable()->after('first_row_is_header');
            $table->json('sample_fields')->nullable()->after('available_elements');
            $table->json('sample_payload')->nullable()->after('sample_fields');
            $table->timestamp('last_analyzed_at')->nullable()->after('sample_payload');
        });

        Schema::create('feed_product_field_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('feed_id')->constrained()->cascadeOnDelete();
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
                ['feed_id', 'canonical_field_id'],
                'feed_product_field_mappings_feed_field_unique'
            );
            $table->index(['mapping_action']);
            $table->index(['source_field']);
            $table->index(['transform_type']);
            $table->index(['sort_order']);
        });

        $this->backfillFromLegacyProfiles();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feed_product_field_mappings');

        Schema::table('feeds', function (Blueprint $table) {
            $table->dropColumn([
                'available_elements',
                'sample_fields',
                'sample_payload',
                'last_analyzed_at',
            ]);
        });
    }

    private function backfillFromLegacyProfiles(): void
    {
        $feeds = DB::table('feeds')
            ->whereNotNull('mapping_profile_id')
            ->get([
                'id',
                'mapping_profile_id',
            ]);

        foreach ($feeds as $feed) {
            $profile = DB::table('feed_mapping_profiles')
                ->where('id', $feed->mapping_profile_id)
                ->first([
                    'available_elements',
                    'sample_fields',
                    'sample_payload',
                    'last_analyzed_at',
                ]);

            if ($profile) {
                DB::table('feeds')
                    ->where('id', $feed->id)
                    ->update([
                        'available_elements' => $profile->available_elements,
                        'sample_fields' => $profile->sample_fields,
                        'sample_payload' => $profile->sample_payload,
                        'last_analyzed_at' => $profile->last_analyzed_at,
                    ]);
            }

            $mappings = DB::table('feed_field_mappings')
                ->where('feed_mapping_profile_id', $feed->mapping_profile_id)
                ->get();

            foreach ($mappings as $mapping) {
                DB::table('feed_product_field_mappings')->updateOrInsert(
                    [
                        'feed_id' => $feed->id,
                        'canonical_field_id' => $mapping->canonical_field_id,
                    ],
                    [
                        'mapping_action' => $mapping->mapping_action ?? 'map',
                        'source_field' => $mapping->source_field,
                        'source_path' => $mapping->source_path,
                        'source_sample' => $mapping->source_sample ?? null,
                        'fallback_fields' => $mapping->fallback_fields,
                        'default_value' => $mapping->default_value,
                        'transform_type' => $mapping->transform_type ?? 'copy',
                        'transform_config' => $mapping->transform_config,
                        'is_required' => (bool) $mapping->is_required,
                        'sort_order' => (int) $mapping->sort_order,
                        'notes' => $mapping->notes ?? null,
                        'created_at' => $mapping->created_at,
                        'updated_at' => $mapping->updated_at,
                    ]
                );
            }
        }
    }
};
