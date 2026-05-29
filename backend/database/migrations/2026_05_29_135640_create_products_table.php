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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->foreignId('partner_id')->constrained()->cascadeOnDelete();
            $table->foreignId('feed_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('provider_product_id')->nullable();
            $table->string('sku')->nullable();
            $table->string('ean')->nullable();
            $table->string('brand')->nullable();
            $table->string('title');
            $table->string('slug');
            $table->longText('description')->nullable();
            $table->text('image_url')->nullable();
            $table->text('product_url')->nullable();
            $table->text('affiliate_url');
            $table->decimal('price', 12, 2)->nullable();
            $table->decimal('old_price', 12, 2)->nullable();
            $table->string('currency', 3)->default('EUR');
            $table->string('availability')->nullable();
            $table->string('condition')->default('new');
            $table->json('metadata')->nullable();
            $table->timestamp('imported_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['site_id', 'slug']);
            $table->unique(['site_id', 'partner_id', 'provider_product_id'], 'products_provider_identity_unique');
            $table->index(['site_id', 'category_id', 'is_active']);
            $table->index(['site_id', 'brand']);
            $table->index(['price', 'old_price']);
        });

        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            Schema::table('products', function (Blueprint $table) {
                $table->fullText(['title', 'description', 'brand']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
