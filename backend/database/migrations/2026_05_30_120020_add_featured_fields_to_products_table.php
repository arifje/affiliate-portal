<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('featured_sort_order')->default(0);

            $table->index(['site_id', 'is_featured', 'featured_sort_order'], 'products_site_featured_index');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_site_featured_index');
            $table->dropColumn(['is_featured', 'featured_sort_order']);
        });
    }
};
