<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('ai_model_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->onDelete('cascade');
            $table->string('model_type'); // audience_analysis, content_suggestions, etc.
            $table->string('feedback_type'); // positive, negative, neutral
            $table->text('feedback_text');
            $table->json('context_data')->nullable();
            $table->json('suggested_improvements')->nullable();
            $table->boolean('is_resolved')->default(false);
            $table->text('resolution_notes')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('ai_model_feedback');
    }
}; 