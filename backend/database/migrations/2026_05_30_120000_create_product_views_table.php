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
        Schema::create('product_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('visitor_hash', 64);
            $table->string('ip_hash', 64)->nullable();
            $table->string('user_agent_hash', 64)->nullable();
            $table->text('referer')->nullable();
            $table->date('viewed_on');
            $table->timestamp('first_viewed_at');
            $table->timestamp('last_viewed_at');
            $table->unsignedInteger('view_count')->default(1);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'visitor_hash', 'viewed_on'], 'product_views_product_visitor_date_unique');
            $table->index(['site_id', 'viewed_on']);
            $table->index(['product_id', 'viewed_on']);
            $table->index('viewed_on');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_views');
    }
};
