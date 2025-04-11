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
        Schema::create('content_recycles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained('posts')->onDelete('cascade');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->enum('type', ['auto', 'manual'])->default('manual');
            $table->enum('strategy', ['performance_improvement', 'time_change', 'similar_content_reuse'])->default('performance_improvement');
            $table->text('new_caption')->nullable();
            $table->timestamp('new_schedule')->nullable();
            $table->decimal('ai_score', 5, 2)->nullable();
            $table->boolean('is_approved')->default(false);
            $table->json('performance_metrics')->nullable();
            $table->json('ai_suggestions')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('content_recycles');
    }
}; 