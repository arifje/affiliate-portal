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
            $table->json('request_headers')->nullable()->after('credentials');
            $table->json('request_query_params')->nullable()->after('request_headers');
            $table->string('source_format')->default('csv')->after('source_type');
            $table->string('source_encoding')->default('utf-8')->after('source_format');
            $table->string('delimiter', 8)->nullable()->after('source_encoding');
            $table->string('enclosure', 8)->nullable()->after('delimiter');
            $table->string('decimal_separator', 4)->default('.')->after('enclosure');
            $table->string('thousands_separator', 4)->nullable()->after('decimal_separator');
            $table->string('row_selector')->nullable()->after('thousands_separator');
            $table->boolean('first_row_is_header')->default(true)->after('row_selector');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('feeds', function (Blueprint $table) {
            $table->dropColumn([
                'request_headers',
                'request_query_params',
                'source_format',
                'source_encoding',
                'delimiter',
                'enclosure',
                'decimal_separator',
                'thousands_separator',
                'row_selector',
                'first_row_is_header',
            ]);
        });
    }
};
