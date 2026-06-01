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
        Schema::table('feeds', function (Blueprint $table) {
            $table->string('unique_identifier_field')->nullable()->after('mapping_profile_id');
            $table->boolean('import_create_new')->default(true)->after('unique_identifier_field');
            $table->boolean('import_update_existing')->default(true)->after('import_create_new');
            $table->boolean('import_disable_missing_globally')->default(false)->after('import_update_existing');
            $table->boolean('import_disable_missing_for_site')->default(false)->after('import_disable_missing_globally');
            $table->boolean('import_delete_missing')->default(false)->after('import_disable_missing_for_site');
            $table->boolean('import_update_search_indexes')->default(true)->after('import_delete_missing');
            $table->text('import_strategy_notes')->nullable()->after('import_update_search_indexes');
        });

        Schema::table('feed_mapping_profiles', function (Blueprint $table) {
            $table->json('available_elements')->nullable()->after('row_selector');
            $table->json('sample_fields')->nullable()->after('available_elements');
            $table->json('sample_payload')->nullable()->after('sample_fields');
            $table->timestamp('last_analyzed_at')->nullable()->after('sample_payload');
        });

        Schema::table('feed_field_mappings', function (Blueprint $table) {
            $table->string('mapping_action')->default('map')->after('canonical_field_id');
            $table->text('source_sample')->nullable()->after('source_path');
            $table->text('notes')->nullable()->after('transform_config');

            $table->index(['mapping_action']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('feed_field_mappings', function (Blueprint $table) {
            $table->dropIndex(['mapping_action']);
            $table->dropColumn([
                'mapping_action',
                'source_sample',
                'notes',
            ]);
        });

        Schema::table('feed_mapping_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'available_elements',
                'sample_fields',
                'sample_payload',
                'last_analyzed_at',
            ]);
        });

        Schema::table('feeds', function (Blueprint $table) {
            $table->dropColumn([
                'unique_identifier_field',
                'import_create_new',
                'import_update_existing',
                'import_disable_missing_globally',
                'import_disable_missing_for_site',
                'import_delete_missing',
                'import_update_search_indexes',
                'import_strategy_notes',
            ]);
        });
    }
};
