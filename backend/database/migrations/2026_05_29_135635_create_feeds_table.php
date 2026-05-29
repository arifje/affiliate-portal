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
        Schema::create('feeds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->foreignId('partner_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('provider');
            $table->string('source_type')->default('url');
            $table->text('source_url')->nullable();
            $table->json('credentials')->nullable();
            $table->json('mapping')->nullable();
            $table->string('schedule')->nullable();
            $table->timestamp('last_import_started_at')->nullable();
            $table->timestamp('last_import_finished_at')->nullable();
            $table->string('last_import_status')->nullable();
            $table->text('last_import_message')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['site_id', 'slug']);
            $table->index(['provider', 'is_active']);
            $table->index(['site_id', 'partner_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feeds');
    }
};
