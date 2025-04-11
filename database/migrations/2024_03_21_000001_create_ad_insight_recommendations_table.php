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
        Schema::create('ad_insight_recommendations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ad_id')->constrained('ads')->onDelete('cascade');
            $table->foreignId('ad_account_id')->constrained('ad_accounts')->onDelete('cascade');
            $table->string('type'); // budget_optimization, targeting_improvement, creative_enhancement
            $table->string('priority'); // high, medium, low
            $table->text('description');
            $table->json('suggested_changes');
            $table->json('metrics_improvement')->nullable();
            $table->text('implementation_notes')->nullable();
            $table->timestamp('implemented_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ad_insight_recommendations');
    }
}; 