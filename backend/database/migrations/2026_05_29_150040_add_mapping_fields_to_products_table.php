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
        Schema::table('products', function (Blueprint $table) {
            $table->string('mpn')->nullable();
            $table->string('item_group_id')->nullable();
            $table->string('merchant_category')->nullable();
            $table->string('product_type')->nullable();
            $table->text('tracking_url')->nullable();
            $table->json('additional_image_urls')->nullable();
            $table->decimal('shipping_cost', 12, 2)->nullable();
            $table->unsignedInteger('stock_quantity')->nullable();
            $table->string('delivery_time')->nullable();
            $table->string('color')->nullable();
            $table->string('size')->nullable();
            $table->string('gender')->nullable();
            $table->string('material')->nullable();
            $table->string('pattern')->nullable();
            $table->string('age_group')->nullable();
            $table->json('raw_payload')->nullable();

            $table->index(['site_id', 'product_type'], 'products_site_product_type_index');
            $table->index(['site_id', 'merchant_category'], 'products_site_merchant_category_index');
            $table->index(['site_id', 'color', 'size'], 'products_variant_lookup_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_site_product_type_index');
            $table->dropIndex('products_site_merchant_category_index');
            $table->dropIndex('products_variant_lookup_index');
            $table->dropColumn([
                'mpn',
                'item_group_id',
                'merchant_category',
                'product_type',
                'tracking_url',
                'additional_image_urls',
                'shipping_cost',
                'stock_quantity',
                'delivery_time',
                'color',
                'size',
                'gender',
                'material',
                'pattern',
                'age_group',
                'raw_payload',
            ]);
        });
    }
};
