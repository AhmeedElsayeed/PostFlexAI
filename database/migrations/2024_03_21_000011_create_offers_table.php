<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('type', ['discount', 'freebie', 'bundle', 'other'])->default('discount');
            $table->decimal('value', 8, 2)->nullable(); // نسبة أو قيمة ثابتة
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('target_personas')->nullable(); // audience_personas
            $table->json('target_segments')->nullable(); // audience_clusters
            $table->json('terms_conditions')->nullable();
            $table->integer('max_usage_per_client')->nullable();
            $table->integer('total_usage_limit')->nullable();
            $table->boolean('is_auto_generated')->default(false);
            $table->json('ai_recommendations')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('offers');
    }
}; 