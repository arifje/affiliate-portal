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
        Schema::create('site_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->string('visitor_hash', 64);
            $table->string('ip_hash', 64)->nullable();
            $table->string('user_agent_hash', 64)->nullable();
            $table->date('visited_on');
            $table->timestamp('first_visited_at');
            $table->timestamp('last_seen_at');
            $table->text('landing_path')->nullable();
            $table->text('last_path')->nullable();
            $table->text('referer')->nullable();
            $table->unsignedInteger('visit_count')->default(1);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['site_id', 'visitor_hash', 'visited_on'], 'site_visits_site_visitor_date_unique');
            $table->index(['site_id', 'visited_on']);
            $table->index(['site_id', 'last_seen_at']);
            $table->index('visited_on');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('site_visits');
    }
};
