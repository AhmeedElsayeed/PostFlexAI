<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('audience_personas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->onDelete('cascade');
            $table->foreignId('social_account_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description');
            $table->json('demographics');
            $table->json('interests');
            $table->json('behaviors');
            $table->json('content_preferences');
            $table->json('engagement_patterns');
            $table->json('pain_points');
            $table->json('goals');
            $table->json('brand_interactions');
            $table->float('engagement_rate');
            $table->integer('estimated_size');
            $table->json('recommendations');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('audience_personas');
    }
}; 