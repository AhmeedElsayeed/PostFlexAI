<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('ad_insight_recommendations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ad_id')->constrained()->onDelete('cascade');
            $table->foreignId('ad_account_id')->constrained()->onDelete('cascade');
            $table->string('type'); // budget_optimization, targeting_improvement, creative_enhancement, etc.
            $table->string('priority'); // low, medium, high
            $table->text('title');
            $table->text('description');
            $table->json('metrics_impact')->nullable(); // Expected impact on metrics
            $table->json('implementation_steps')->nullable(); // Steps to implement the recommendation
            $table->boolean('is_implemented')->default(false);
            $table->timestamp('implemented_at')->nullable();
            $table->json('results')->nullable(); // Results after implementation
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('ad_insight_recommendations');
    }
}; 